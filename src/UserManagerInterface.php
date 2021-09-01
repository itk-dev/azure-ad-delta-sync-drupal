<?php

namespace Drupal\azure_ad_delta_sync;

use ItkDev\AzureAdDeltaSync\Handler\HandlerInterface;

/**
 * User manager.
 */
interface UserManagerInterface extends HandlerInterface {

  /**
   * Set options.
   *
   * @param array $options
   *   The options.
   */
  public function setOptions(array $options);

  /**
   * Get user ids.
   *
   * @return int[]
   *   The user ids.
   */
  public function loadUserIds(): array;

}
