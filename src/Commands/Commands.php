<?php

namespace Drupal\azure_ad_delta_sync\Commands;

use Drupal\azure_ad_delta_sync\Form\SettingsForm;
use Drupal\azure_ad_delta_sync\Handler\Handler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\CommandFailedException;
use ItkDev\AzureAdDeltaSync\Controller;
use Psr\Http\Client\ClientInterface;
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
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $client;

  /**
   * The handler.
   *
   * @var \Drupal\azure_ad_delta_sync\Handler\Handler
   */
  private $handler;

  /**
   * Commands constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $client, Handler $handler) {
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->client = $client;
    $this->handler = $handler;
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
    $this->handler->setOptions([
      'dry-run' => $dryRun,
    ]);
    if ($dryRun) {
      $this->output->setVerbosity($this->output()->getVerbosity() | OutputInterface::VERBOSITY_VERBOSE);
    }

    if (!$dryRun && !$force) {
      throw new CommandFailedException('Please specify either --dry-run or --force option.');
    }

    $controller = new Controller(
      $this->client,
      [
        'client_id' => $this->moduleConfig->get('api.client_id'),
        'client_secret' => $this->moduleConfig->get('api.client_secret'),
        'group_id' => $this->moduleConfig->get('api.group_id'),
        'tenant_id' => $this->moduleConfig->get('api.tenant_id'),
      ]
    );
    $controller->run($this->handler);
  }

}
