<?php

namespace Drupal\azure_ad_delta_sync\Helpers;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\azure_ad_delta_sync\Form\SettingsForm;

/**
 * Config helper.
 */
class ConfigHelper {
  public const MODULE = 'itkdev_openid_connect_drupal';

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $moduleConfig;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
  }

  /**
   * Get providers.
   */
  public function getProviders() {
    $include = $this->getConfiguration('include');
    if (isset($include['providers'])) {
      $providers = $include['providers'];
      var_dump($providers);
      if (is_array($providers)) {
        return array_filter($providers);
      }
    }
    return FALSE;
  }

  /**
   * Get roles.
   */
  public function getRoles() {
    $exclude = $this->getConfiguration('exclude');
    if (isset($exclude['roles'])) {
      return array_filter($exclude['roles']);

    }
    return FALSE;
  }

  /**
   * Get user cancel method.
   */
  public function getUserCancelMethod() {
    // user_cancel_block: Account will be blocked and will no longer be able
    // to log in. All of the content will remain attributed to the username.
    return $this->moduleConfig->get('drupal')['user_cancel_method'] ?? 'user_cancel_block';
  }

  /**
   * get users.
   */
  public function getUsers() {
    $exclude = $this->getConfiguration('exclude');
    if (isset($exclude['users'])) {
      return $exclude['users'];
    }
    return FALSE;
  }

  /**
   * Set configuration
   */
  public function setConfiguration(array $config, string $configName): void {
    $config[$configName] = $config;
  }

  /**
   * Get configuration
   */
  public function getConfiguration(string $configName): array|string {
    return $this->moduleConfig->get($configName);
  }

  /**
   * Escape provider id.
   */
  public function escapeProviderId(string $input) {
    return str_replace(".", "__dot__", $input);
  }

  /**
   * Unescape provider id.
   */
  public function unescapeProviderId(string $input) {
    return str_replace("__dot__", ".", $input);
  }

  /**
   * Get active user providers.
   *
   * @phpstan-return array<mixed, mixed>
   */
  private function getAllUserProviders(): array {
    // Stolen from here: https://git.drupalcode.org/project/openid_connect/-/blob/3.x/src/Form/OpenIDConnectLoginForm.php?ref_type=heads#L78
    // @todo maybe do this in a more elegant way.
    $clients = $this->oidcStorage->loadByProperties(['status' => TRUE]);
    $providerIds = [];
    $openIdConnectPrefix = 'openid_connect';
    foreach ($clients as $client_id => $client) {
      $providerIds[$openIdConnectPrefix . "." . $client_id] = $client->label();
    }

    return $providerIds;
  }

}
