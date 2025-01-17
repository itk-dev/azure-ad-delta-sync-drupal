<?php

namespace Drupal\azure_ad_delta_sync\Drush\Commands;

use Drupal\azure_ad_delta_sync\ControllerInterface;
use Drupal\azure_ad_delta_sync\UserManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Output\OutputInterface;
use Drush\Attributes as CLI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands.
 */
class Commands extends DrushCommands {
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('azure_ad_delta_sync.controller'),
      $container->get('azure_ad_delta_sync.user_manager'),
    );
  }


  /**
   * Run.
   *
   * @command azure_ad_delta_sync:run
   * @usage azure_ad_delta_sync:run
   *   Remove inactive users.
   */
  #[CLI\Command(name: 'azure_ad_delta_sync:run')]
  #[CLI\Option(name: 'dry-run', description: "Don't do anything, but show what will be done.")]
  #[CLI\Option(name: 'force', description: 'Delete inactive users.')]
  public function run(
    array $options = [
      'dry-run' => NULL,
      'force' => NULL,
    ],
  ): void {
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
