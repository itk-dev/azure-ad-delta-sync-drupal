<?php

namespace Drupal\azure_ad_delta_sync;

use Drupal\azure_ad_delta_sync\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use ItkDev\AzureAdDeltaSync\Controller as BaseController;
use Psr\Http\Client\ClientInterface;

/**
 * The controller implementation.
 */
class Controller extends BaseController implements ControllerInterface {

  /**
   * Constructor.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $configFactory) {
    $moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $options = [
      'client_id' => $moduleConfig->get('azure.client_id'),
      'client_secret' => $moduleConfig->get('azure.client_secret'),
      'group_id' => $moduleConfig->get('azure.group_id'),
      'tenant_id' => $moduleConfig->get('azure.tenant_id'),
    ];
    parent::__construct($client, $options);
  }

}
