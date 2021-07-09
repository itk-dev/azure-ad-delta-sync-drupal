<?php

namespace Drupal\adgangsstyring;

use Drupal\adgangsstyring\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * User manager.
 */
class UserManager {
  private const MODULE = 'adgangsstyring';
  private const MARKER = 'delete';

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  private $userData;

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
   * UserManager constructor.
   *
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data.
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
  public function __construct(UserDataInterface $userData, EntityTypeManager $entityTypeManager, ConfigFactoryInterface $configFactory, Connection $database, RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, LoggerInterface $logger) {
    $this->userData = $userData;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->database = $database;
    $this->requestStack = $requestStack;
    $this->moduleHandler = $moduleHandler;
    $this->logger = $logger;
  }

  /**
   * Get user ids.
   */
  public function getUserIds() {
    $userIds = &drupal_static(__FUNCTION__);
    if (!isset($userIds)) {
      $userIds = $this->userStorage->getQuery()->execute();

      // Handle modules.
      $modules = array_flip(array_filter($this->moduleConfig->get('modules')));
      foreach ($modules as $module) {
        $moduleUsersIds = $this->getModuleUsersIds($module);
        if (is_array($moduleUsersIds)) {
          $userIds = array_intersect($userIds, $moduleUsersIds);
        }
      }

      // Handle exclusions.
      $exclusions = $this->moduleConfig->get('exclusions');
      // Handle excluded roles.
      $excludedRoles = $exclusions['roles'];
      if (is_array($excludedRoles) && !empty($excludedRoles)) {
        $query = $this->userStorage->getQuery();
        $group = $query->orConditionGroup();
        foreach ($excludedRoles as $role) {
          $group->condition('roles', $role);
        }
        $roleUserIds = $query
          ->condition($group)
          ->execute();
        foreach ($roleUserIds as $userId) {
          unset($userIds[$userId]);
        }
      }

      // Handle excluded users.
      $excludedUsers = $exclusions['users'];
      if (is_array($excludedUsers)) {
        foreach ($excludedUsers as $userId) {
          unset($userIds[$userId]);
        }
      }
    }

    return $userIds;
  }

  /**
   * Mark users for deletion.
   */
  public function markUsersForDeletion() {
    $userIds = $this->getUserIds();
    $now = (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM);
    foreach ($userIds as $userId) {
      $this->userData->set(self::MODULE, $userId, self::MARKER, $now);
    }
  }

  /**
   * Retain users.
   *
   * @param array $users
   *   The users to retain.
   */
  public function retainUsers(array $users) {
    foreach ($users as $user) {
      $username = $user['userPrincipalName'] ?? NULL;
      $user = $this->loadUserByProperties(['name' => $username]);
      if (NULL !== $user) {
        $this->logger->info(sprintf('#users: %s', $user->getAccountName()));
        $this->userData->delete(self::MODULE, $user->id(), self::MARKER);
      }
    }
  }

  /**
   * Delete users.
   */
  public function deleteUsers() {
    $method = $this->moduleConfig->get('user_cancel_method');
    $deletedUserIds = [];
    $userIds = $this->getUserIds();
    foreach ($userIds as $userId) {
      $marker = $this->userData->get(self::MODULE, $userId, self::MARKER);
      if (NULL !== $marker) {
        user_cancel([], $userId, $method);
        $deletedUserIds[] = $userId;
      }
    }

    $this->logger->info(sprintf('#users to be deleted: %s', count($deletedUserIds)));

    if (!empty($deletedUserIds)) {
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

  /**
   * Load user by properties.
   *
   * @param array $properties
   *   The properties.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user if any.
   */
  private function loadUserByProperties(array $properties = []): ?UserInterface {
    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->userStorage->loadByProperties($properties);

    return reset($users) ?: NULL;
  }

  /**
   * Get module user ids.
   *
   * @param string $module
   *   The module.
   *
   * @return int[]|null
   *   The user ids.
   */
  private function getModuleUsersIds(string $module): ?array {
    if (!$this->moduleHandler->moduleExists($module)) {
      return NULL;
    }
    switch ($module) {
      case 'openid_connect':
        return $this->database
          ->select('users_data')
          ->fields('users_data', ['uid'])
          ->condition('users_data.module', 'openid_connect')
          ->condition('users_data.name', 'oidc_name')
          ->execute()
          ->fetchCol();

      case 'samlauth':
        return $this->database
          ->select('authmap')
          ->fields('authmap', ['uid'])
          ->condition('authmap.provider', 'samlauth')
          ->execute()
          ->fetchCol();

      default:
        throw new \RuntimeException(sprintf('Unknown module: %s', $module));
    }
  }

}
