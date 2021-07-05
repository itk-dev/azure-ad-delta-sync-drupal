<?php

namespace Drupal\adgangsstyring\EventSubscriber;

use Drupal\adgangsstyring\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\UserDataInterface;
use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {
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
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * EventSubscriber constructor.
   */
  public function __construct(UserDataInterface $userData, EntityTypeManager $entityTypeManager, ConfigFactoryInterface $configFactory, MessengerInterface $messenger) {
    $this->userData = $userData;
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      StartEvent::class => 'start',
      UserDataEvent::class => 'userData',
      CommitEvent::class => 'commit',
    ];
  }

  /**
   * Start.
   */
  public function start(StartEvent $event) {
    $userIds = $this->getUserIds();
    $now = (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM);
    foreach ($userIds as $userId) {
      $this->userData->set(self::MODULE, $userId, self::MARKER, $now);
    }
    $this->messenger->addStatus(__METHOD__, TRUE);
  }

  /**
   * Handle user data.
   */
  public function userData(UserDataEvent $event) {
    $users = $event->getData();
    foreach ($users as $user) {
      $username = $user['userPrincipalName'] ?? NULL;
      /** @var \Drupal\user\Entity\User $user */
      $user = reset($this->userStorage->loadByProperties(['name' => $username])) ?: NULL;
      if (NULL !== $user) {
        $this->messenger->addStatus(sprintf('#users: %s', $user->getAccountName()));
        $this->userData->delete(self::MODULE, $user->id(), self::MARKER);
      }
    }
    $this->messenger->addStatus(sprintf('%s; #users: %d', __METHOD__, count($users)), TRUE);
  }

  /**
   * Commit.
   */
  public function commit(CommitEvent $event) {
    $userIds = $this->getUserIds();
    foreach ($userIds as $userId) {
      $marker = $this->userData->get(self::MODULE, $userId, self::MARKER);
      if (NULL !== $marker) {
        $user = $this->userStorage->load($userId);
        $user->delete();
      }
    }

    $this->messenger->addStatus(__METHOD__, TRUE);
  }

  /**
   * Get user ids.
   */
  private function getUserIds() {
    $userIds = $this->userStorage->getQuery()->execute();

    unset($userIds[0]);

    return $userIds;
  }

}
