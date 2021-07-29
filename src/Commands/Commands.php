<?php

namespace Drupal\adgangsstyring\Commands;

use Drupal\adgangsstyring\Form\SettingsForm;
use Drupal\adgangsstyring\Handler\Handler;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use ItkDev\Adgangsstyring\Controller;

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
   * @var \Drupal\adgangsstyring\Handler\Handler
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
   * @command adgangsstyring:run
   * @option dry-run
   * @usage adgangsstyring:run
   */
  public function run(array $options = ['dry-run' => FALSE]) {
    $this->handler->setOptions([
      'dry-run' => $options['dry-run'],
    ]);
    $controller = new Controller(
      $this->client,
      [
        'client_id' => $this->moduleConfig->get('client_id'),
        'client_secret' => $this->moduleConfig->get('client_secret'),
        'group_id' => $this->moduleConfig->get('group_id'),
        'tenant_id' => $this->moduleConfig->get('tenant_id'),
      ]
    );
    $controller->run($this->handler);
  }

}
