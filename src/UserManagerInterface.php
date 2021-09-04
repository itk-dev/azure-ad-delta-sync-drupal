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
   * Load managed user ids.
   *
   * @return int[]
   *   The managed user ids.
   */
  public function loadManagedUserIds(): array;

}
