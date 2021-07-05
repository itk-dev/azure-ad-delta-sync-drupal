<?php

namespace Drupal\adgangsstyring\Commands;

use Drupal\adgangsstyring\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drush\Commands\DrushCommands;
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
   * Commands constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EventDispatcherInterface $eventDispatcher) {
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
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
        'clientId' => $this->moduleConfig->get('client_id'),
        'clientSecret' => $this->moduleConfig->get('client_secret'),
        'groupId' => $this->moduleConfig->get('group_id'),
        'tenantId' => $this->moduleConfig->get('tenant_id'),
      ]
    );
    $controller->run();
  }

}
