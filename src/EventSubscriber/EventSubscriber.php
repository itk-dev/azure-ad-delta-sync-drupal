<?php

namespace Drupal\adgangsstyring\EventSubscriber;

use Drupal\adgangsstyring\UserManager;
use ItkDev\Adgangsstyring\Event\CommitEvent;
use ItkDev\Adgangsstyring\Event\StartEvent;
use ItkDev\Adgangsstyring\Event\UserDataEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {
  /**
   * The user data.
   *
   * @var \Drupal\adgangsstyring\UserManager
   */
  private $userManager;

  /**
   * The messenger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * EventSubscriber constructor.
   */
  public function __construct(UserManager $userManager, LoggerInterface $logger) {
    $this->userManager = $userManager;
    $this->logger = $logger;
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
    $this->logger->info(__METHOD__);
    $this->userManager->markUsersForDeletion();
  }

  /**
   * Handle user data.
   */
  public function userData(UserDataEvent $event) {
    $users = $event->getData();
    $this->logger->info(sprintf('%s; #users: %d', __METHOD__, count($users)));
    $this->userManager->retainUsers($users);
  }

  /**
   * Commit.
   */
  public function commit(CommitEvent $event) {
    $this->logger->info(__METHOD__);
    $this->userManager->deleteUsers();
  }

}
