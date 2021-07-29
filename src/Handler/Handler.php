<?php

namespace Drupal\adgangsstyring\Handler;

use Drupal\adgangsstyring\UserManager;
use ItkDev\Adgangsstyring\Handler\HandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * A handler.
 */
class Handler implements HandlerInterface {
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
  public function start(): void {
    $this->logger->info(__METHOD__);
    $this->userManager->markUsersForDeletion();
  }

  /**
   * {@inheritdoc}
   */
  public function retainUsers(array $users): void {
    $this->logger->info(sprintf('%s; #users: %d', __METHOD__, count($users)));
    $this->userManager->retainUsers($users);
  }

  /**
   * {@inheritdoc}
   */
  public function commit(): void {
    $this->logger->info(__METHOD__);
    $this->userManager->deleteUsers();
  }

  /**
   * Set options.
   *
   * @param array $options
   *   The options.
   */
  public function setOptions(array $options) {
    $this->userManager->setOptions($options);
  }

}
