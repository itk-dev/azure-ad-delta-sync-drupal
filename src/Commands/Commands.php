<?php

namespace Drupal\azure_ad_delta_sync\Commands;

use Drupal\azure_ad_delta_sync\ControllerInterface;
use Drupal\azure_ad_delta_sync\UserManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drush commands.
 */
class Commands extends DrushCommands {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $moduleConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * The controller.
   *
   * @var \Drupal\azure_ad_delta_sync\ControllerInterface
   */
  private $controller;

  /**
   * The user manager.
   *
   * @var \Drupal\azure_ad_delta_sync\UserManagerInterface
   */
  private $userManager;

  /**
   * Commands constructor.
   */
  public function __construct(ControllerInterface $controller, UserManagerInterface $userManager) {
    $this->controller = $controller;
    $this->userManager = $userManager;
  }

  /**
   * The run command.
   *
   * @command azure_ad_delta_sync:run
   * @option dry-run
   *   Don't do anything, but show what will be done.
   * @option force
   *   Delete inactive users.
   * @usage azure_ad_delta_sync:run
   */
  public function run(array $options = ['dry-run' => FALSE, 'force' => FALSE]) {
    $dryRun = $options['dry-run'];
    $force = $options['force'];
    $this->userManager->setOptions([
      'dry-run' => $dryRun,
      'debug' => $this->output->isDebug(),
    ]);
    if ($dryRun) {
      $this->output->setVerbosity($this->output()->getVerbosity() | OutputInterface::VERBOSITY_VERBOSE);
    }

    if (!$dryRun && !$force) {
      throw new CommandFailedException('Please specify either --dry-run or --force option.');
    }

    $this->controller->run($this->userManager);
  }

}
