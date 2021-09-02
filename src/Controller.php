<?php

namespace Drupal\azure_ad_delta_sync;

use Drupal\azure_ad_delta_sync\Form\SettingsForm;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Http\Client\ClientInterface;
use ItkDev\AzureAdDeltaSync\Controller as BaseController;

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
      'client_id' => $moduleConfig->get('api.client_id'),
      'client_secret' => $moduleConfig->get('api.client_secret'),
      'group_id' => $moduleConfig->get('api.group_id'),
      'tenant_id' => $moduleConfig->get('api.tenant_id'),
    ];
    parent::__construct($client, $options);
  }

}
