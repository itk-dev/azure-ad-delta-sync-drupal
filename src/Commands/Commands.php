<?php

namespace Drupal\adgangsstyring\Commands;

use Drush\Commands\DrushCommands;
use ItkDev\Adgangsstyring\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Drush commands.
 */
class Commands extends DrushCommands {
  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Commands constructor.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * The run command.
   *
   * @command adgangsstyring:run
   * @usage adgangsstyring:run
   */
  public function run(array $options = []) {
    $controller = new Controller(
      $this->eventDispatcher,
      [
        'clientId' => '',
        'clientSecret' => '',
        'groupId' => '',
        'tenantId' => '',
      ]
    );
    $controller->run();
  }

}
