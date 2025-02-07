<?php

namespace Drupal\azure_ad_delta_sync;

use ItkDev\AzureAdDeltaSync\Controller as BaseController;
use Psr\Http\Client\ClientInterface;
use Drupal\azure_ad_delta_sync\Helpers\ConfigHelper;

/**
 * The controller implementation.
 */
class Controller extends BaseController implements ControllerInterface {

  /**
   * Constructor.
   */
  public function __construct(
    ClientInterface $client,
    ConfigHelper $configHelper,
  ) {
    $options = [
      'security_key' => $configHelper->getConfiguration('azure.security_key'),
      'client_secret' => $configHelper->getConfiguration('azure.client_secret'),
      'uri' => $configHelper->getConfiguration('azure.uri'),
    ];
    parent::__construct($client, $options);
  }

}
