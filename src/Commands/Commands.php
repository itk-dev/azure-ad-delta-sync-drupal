<?php

namespace Drupal\adgangsstyring\Commands;

use Drupal\adgangsstyring\Form\SettingsForm;
use Drupal\adgangsstyring\UserManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use ItkDev\Adgangsstyring\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The user manager.
   *
   * @var \Drupal\adgangsstyring\UserManager
   */
  private $userManager;

  /**
   * Commands constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EventDispatcherInterface $eventDispatcher, ClientInterface $client, UserManager $userManager) {
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->eventDispatcher = $eventDispatcher;
    $this->client = $client;
    $this->userManager = $userManager;
  }

  /**
   * The run command.
   *
   * @command adgangsstyring:run
   * @option dry-run
   * @usage adgangsstyring:run
   */
  public function run(array $options = ['dry-run' => FALSE]) {
    $this->userManager->setOptions([
      'dry-run' => $options['dry-run'],
    ]);
    $controller = new Controller(
      $this->eventDispatcher,
      $this->client,
      [
        'clientId' => $this->moduleConfig->get('client_id'),
        'clientSecret' => $this->moduleConfig->get('client_secret'),
        'groupId' => $this->moduleConfig->get('group_id'),
        'tenantId' => $this->moduleConfig->get('tenant_id'),
      ]
    );
    $controller->run();
  }

}
