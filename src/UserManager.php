<?php

namespace Drupal\azure_ad_delta_sync;

use Drupal\azure_ad_delta_sync\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\drupal_psr6_cache\Cache\CacheItem;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;

/**
 * User manager.
 */
class UserManager implements UserManagerInterface {
  use StringTranslationTrait;

  private const MODULE = 'azure_ad_delta_sync';
  private const MARKER = 'delete';
  private const CACHE_KEY_USER_IDS = 'azure_ad_delta_sync_user_ids';

  /**
   * The user data.
   *
   * @var \Psr\Cache\CacheItemPoolInterface
   */
  private $cacheItemPool;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $userStorage;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $moduleConfig;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * The options.
   *
   * @var array
   */
  private $options;

  /**
   * UserManager constructor.
   *
   * @param \Psr\Cache\CacheItemPoolInterface $cacheItemPool
   *   The user data.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(CacheItemPoolInterface $cacheItemPool, EntityTypeManager $entityTypeManager, ConfigFactoryInterface $configFactory, Connection $database, ModuleHandlerInterface $moduleHandler, LoggerInterface $logger) {
    $this->cacheItemPool = $cacheItemPool;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->database = $database;
    $this->moduleHandler = $moduleHandler;
    $this->logger = $logger;
    $this->validateConfig();
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadManagedUserIds(): array {
    $userIds = &drupal_static(__FUNCTION__);
    if (!isset($userIds)) {
      $query = $this->userStorage->getQuery()
        ->accessCheck(FALSE);
      // Never delete user 0 and 1.
      $query->condition('uid', [0, 1], 'NOT IN');

      $include = $this->moduleConfig->get('include');
      if (isset($include['modules'])) {
        $modules = $include['modules'];
        if (is_array($modules)) {
          $modules = array_filter($modules);
          if (!empty($modules)) {
            $orCondition = $query->orConditionGroup();
            foreach ($modules as $module) {
              $moduleUserIdQuery = $this->getModuleUserIdsQuery($module);
              if (NULL !== $moduleUserIdQuery) {
                $orCondition->condition('uid', $moduleUserIdQuery, 'IN');
              }
            }
            $query->condition($orCondition);
          }
        }
      }

      $exclude = $this->moduleConfig->get('exclude');
      if (isset($exclude['roles'])) {
        $roles = array_filter($exclude['roles']);
        if (!empty($roles)) {
          $query->condition('roles', $roles, 'NOT IN');
        }
      }

      if (isset($exclude['users'])) {
        $users = $exclude['users'];
        if (!empty($users)) {
          $query->condition('uid', $users, 'NOT IN');
        }
      }

      $userIds = $query->execute();
    }

    return $userIds;
  }

  /**
   * {@inheritdoc}
   */
  public function collectUsersForDeletionList(): void {
    $userIds = $this->loadManagedUserIds();
    $this->cacheUserIdsForDeletion($userIds);
    $this->logger->info($this->formatPlural(
      count($userIds),
      '1 user marked for deletion.',
      '@count users marked for deletion.'
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function removeUsersFromDeletionList(array $users): void {
    $userIdClaim = $this->moduleConfig->get('azure.user_id_claim');
    $userIdField = $this->moduleConfig->get('drupal.user_id_field');

    $this->logger->info($this->formatPlural(
      count($users),
      'Retaining one user.',
      'Retaining @count users.'
    ));
    $cachedUserIdsForDeletion = $this->getCachedUserIdsForDeletion();
    if (is_array($cachedUserIdsForDeletion)) {
      $userIdsToKeep = array_map(
        static function (array $user) use ($userIdClaim) {
          if (!isset($user[$userIdClaim])) {
            throw new \RuntimeException(sprintf('Cannot get user id (%s)', $userIdClaim));
          }
          return $user[$userIdClaim];
        },
        $users
      );

      $this->logger->debug(json_encode($users, JSON_PRETTY_PRINT));

      $users = $this->userStorage->loadByProperties([$userIdField => $userIdsToKeep]);
      foreach ($users as $user) {
        $this->logger->info($this->t('Retaining user @name.', ['@name' => $user->label()]));
        unset($cachedUserIdsForDeletion[$user->id()]);
      }
      $this->cacheUserIdsForDeletion($cachedUserIdsForDeletion);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function commitDeletionList(): void {
    $method = $this->moduleConfig->get('user_cancel_method');
    $deletedUserIds = [];
    $userIds = $this->getCachedUserIdsForDeletion();
    foreach ($userIds as $userId) {
      user_cancel([], $userId, $method);
      $deletedUserIds[] = $userId;
    }

    $this->logger->info($this->formatPlural(
      count($deletedUserIds),
      'One user to be deleted',
      '@count users to be deleted'
    ));
    if ($this->options['debug'] ?? FALSE) {
      $users = $this->userStorage->loadMultiple($deletedUserIds);
      foreach ($users as $user) {
        $this->logger->debug(sprintf('User to be deleted: %s (#%s)', $user->label(), $user->id()));
      }
    }

    if (!($this->options['dry-run'] ?? FALSE)) {
      if (!empty($deletedUserIds)) {
        $this->logger->info($this->formatPlural(
          count($deletedUserIds),
          'Deleting one user',
          'Deleting @count users'
        ));
        $this->requestStack->getCurrentRequest()
          // batch_process needs a route in the request (!)
          ->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('<none>'));

        // Process the batch created by deleteUser.
        $batch =& batch_get();
        $batch['progressive'] = FALSE;
        $batch['source_url'] = 'cron';

        batch_process();
      }
    }
  }

  /**
   * Get module user ids select query.
   *
   * @param string $module
   *   The module.
   *
   * @return null|\Drupal\Core\Database\Query\SelectInterface
   *   The select query.
   */
  private function getModuleUserIdsQuery(string $module): ?SelectInterface {
    if (!$this->moduleHandler->moduleExists($module)) {
      return NULL;
    }
    switch ($module) {
      case 'openid_connect':
        return $this->database
          ->select('users_data')
          ->fields('users_data', ['uid'])
          ->condition('users_data.module', 'openid_connect')
          ->condition('users_data.name', 'oidc_name');

      case 'samlauth':
        return $this->database
          ->select('authmap')
          ->fields('authmap', ['uid'])
          ->condition('authmap.provider', 'samlauth');

      default:
        throw new \RuntimeException(sprintf('Unknown module: %s', $module));
    }
  }

  /**
   * Set user ids.
   */
  private function cacheUserIdsForDeletion(array $userIds) {
    $item = new CacheItem(self::CACHE_KEY_USER_IDS, $userIds, TRUE);
    $this->cacheItemPool->save($item);
  }

  /**
   * Get user ids.
   */
  private function getCachedUserIdsForDeletion(): ?array {
    $item = $this->cacheItemPool->getItem(self::CACHE_KEY_USER_IDS);

    return $item->isHit() ? $item->get() : NULL;
  }

  /**
   * Validate config.
   */
  private function validateConfig() {
    $required = [
      'azure.user_id_claim',
      'drupal.user_id_field',
    ];
    foreach ($required as $name) {
      if (empty($this->moduleConfig->get($name))) {
        throw new \InvalidArgumentException(sprintf('Invalid or missing configuration in %s: %s', static::class, $name));
      }
    }
  }

}
