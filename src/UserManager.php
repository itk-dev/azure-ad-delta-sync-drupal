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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * User manager.
 */
class UserManager implements UserManagerInterface {
  use StringTranslationTrait;

  private $userIds; 

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
   *
   * @phpstan-var array<mixed, mixed>
   */
  private array $options;

  /**
   * UserManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManager $entityTypeManager, ConfigFactoryInterface $configFactory, Connection $database, readonly RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, LoggerInterface $logger) {
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->database = $database;
    $this->moduleHandler = $moduleHandler;
    $this->logger = $logger;
    $this->userIds = array();
    $this->validateConfig();
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options): void {
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<mixed, mixed>
   */
  public function loadManagedUserIds(): array {
    $managedUserIds = &drupal_static(__FUNCTION__);
    if (!isset($managedUserIds)) {
      $query = $this->userStorage->getQuery()
        ->accessCheck(FALSE);
      // Never delete user 0 and 1.
      $query->condition('uid', [0, 1], 'NOT IN');

      $include = $this->moduleConfig->get('include');
      if (isset($include['providers'])) {
        $providers = $include['providers'];
        if (is_array($providers)) {
          $providers = array_filter($providers);
          if (!empty($providers)) {
            $orCondition = $query->orConditionGroup();
            foreach ($providers as $provider) {
              $providerUserIdQuery = $this->getProviderUserIdsQuery($provider);
              if (NULL !== $providerUserIdQuery) {
                $orCondition->condition('uid', $providerUserIdQuery, 'IN');
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

      $managedUserIds = $query->execute();
    }

    return $managedUserIds;
  }

  /**
   * {@inheritdoc}
   */
  public function collectUsersForDeletionList(): void {
    $managedUserIds = $this->loadManagedUserIds();
    if (0 !== count($managedUserIds)) {
      $this->userIds = $managedUserIds;
      $this->logger->info($this->formatPlural(
        count($this->userIds),
        '1 user marked for deletion.',
        '@count users marked for deletion.'
      ));
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed, mixed> $users
   */
  public function removeUsersFromDeletionList(array $users): void {
    $userIdClaim = $this->moduleConfig->get('azure.user_id_claim');
    $userIdField = $this->moduleConfig->get('drupal.user_id_field');

    $this->logger->info($this->formatPlural(
      count($users),
      'Retaining one user.',
      'Retaining @count users.'
    ));
    if (is_array($this->userIds)) {
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
        unset($this->userIds[$user->id()]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function commitDeletionList(): void {
    // user_cancel_block: Account will be blocked and will no longer be able
    // to log in. All of the content will remain attributed to the username.
    $method = $this->moduleConfig->get('drupal')['user_cancel_method'] ?? 'user_cancel_block';
    $deletedUserIds = [];

    foreach ($this->userIds as $userId) {
      user_cancel([], $userId, $method);
      $deletedUserIds[] = $userId;
    }

    if (0 !== count($deletedUserIds)) {
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
   * Get provider user ids select query.
   *
   * @param string $provider
   *   The provider.
   *
   * @return null|\Drupal\Core\Database\Query\SelectInterface
   *   The select query.
   */
  private function getProviderUserIdsQuery(string $provider): ?SelectInterface {
    switch ($provider) {
      case 'openid_connect.generic':
        return $this->database
          ->select('authmap')
          ->fields('authmap', ['uid'])
          ->condition('authmap.provider', $provider);

      default:
        throw new \RuntimeException(sprintf('Unknown provider: %s', $provider));
    }
  }

  /**
   * Validate config.
   */
  private function validateConfig(): void {
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
