<?php

namespace Drupal\azure_ad_delta_sync\Handler;

use Drupal\azure_ad_delta_sync\UserManager;
use ItkDev\AzureAdDeltaSync\Handler\HandlerInterface;

/**
 * A handler.
 */
class Handler implements HandlerInterface {
  /**
   * The user data.
   *
   * @var \Drupal\azure_ad_delta_sync\UserManager
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
  public function collectUsersForDeletionList(): void {
    $this->userManager->markUsersForDeletion();
  }

  /**
   * {@inheritdoc}
   */
  public function removeUsersFromDeletionList(array $users): void {
    $this->userManager->retainUsers($users);
  }

  /**
   * {@inheritdoc}
   */
  public function commitDeletionList(): void {
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
