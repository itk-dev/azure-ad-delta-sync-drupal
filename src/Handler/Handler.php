<?php

namespace Drupal\adgangsstyring\Handler;

use Drupal\adgangsstyring\UserManager;
use ItkDev\Adgangsstyring\Handler\HandlerInterface;

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
   * EventSubscriber constructor.
   */
  public function __construct(UserManager $userManager) {
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public function start(): void {
    $this->userManager->markUsersForDeletion();
  }

  /**
   * {@inheritdoc}
   */
  public function retainUsers(array $users): void {
    $this->userManager->retainUsers($users);
  }

  /**
   * {@inheritdoc}
   */
  public function commit(): void {
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
