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
   *
   * @phpstan-param array<mixed, mixed> $options
   */
  public function setOptions(array $options): void;

  /**
   * Load managed user ids.
   *
   * @return int[]
   *   The managed user ids.
   */
  public function loadManagedUserIds(): array;

  /**
   * Get active OIDC providers.
   *
   * @phpstan-return array<mixed, mixed>
   */
  public function getActiveOpenIdConnectProviders(): array;

}
