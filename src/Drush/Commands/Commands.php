<?php

namespace Drupal\azure_ad_delta_sync\Drush\Commands;

use Drush\Attributes\Option;
use Drush\Attributes\Command;
use Drupal\azure_ad_delta_sync\ControllerInterface;
use Drupal\azure_ad_delta_sync\UserManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Output\OutputInterface;
use Drush\Commands\AutowireTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Drush commands.
 */
final class Commands extends DrushCommands {

  use AutowireTrait;

  /**
   * Commands constructor.
   */
  public function __construct(
    #[Autowire(service: \Drupal\azure_ad_delta_sync\Controller::class)]
    private readonly ControllerInterface $controller,
    // #[Autowire(service: \Drupal\azure_ad_delta_sync\UserManager::class)]
    private readonly UserManagerInterface $userManager,
  ) {
  }

  /**
   * Run.
   *
   * @command azure_ad_delta_sync:run
   * @usage azure_ad_delta_sync:run
   *   Remove inactive users.
   */
  #[Command(name: 'azure_ad_delta_sync:run')]
  #[Option(name: 'dry-run', description: "Don't do anything, but show what will be done.")]
  #[Option(name: 'force', description: 'Delete inactive users.')]
  public function run(
    array $options = [
      'dry-run' => NULL,
      'force' => NULL,
      'test-command-runs' => NULL,
    ],
  ): void {
    $dryRun = $options['dry-run'];
    $force = $options['force'];
    $testCommandRuns = $options['test-command-runs'];

    if ($testCommandRuns) {
      return;
    }

    if (!$dryRun && !$force) {
      throw new CommandFailedException('Please specify either --dry-run or --force option.');
    }

    $this->userManager->setOptions([
      'dry-run' => $dryRun,
      'debug' => $this->output()->isDebug(),
    ]);

    if ($dryRun) {
      $this->output->setVerbosity($this->output()->getVerbosity() | OutputInterface::VERBOSITY_VERBOSE);
    }

    $this->controller->run($this->userManager);
  }

}
