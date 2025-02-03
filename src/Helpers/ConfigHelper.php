<?php

namespace Drupal\azure_ad_delta_sync\Helpers;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\azure_ad_delta_sync\Form\SettingsForm;
use Drupal\Core\Entity\EntityTypeManager;

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
   * The oidc storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $oidcStorage;

  /**
   * Confighelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManager $entityTypeManager) {
    $this->moduleConfig = $configFactory->get(SettingsForm::SETTINGS);
    $this->oidcStorage = $entityTypeManager->getStorage('openid_connect_client');
  }

  /**
   * Get unescaped providers.
   */
  public function getProviders() {
    $include = $this->getConfiguration('include');
    $unescapedProviders = [];
    if (isset($include['providers'])) {
      $providers = $include['providers'];
      if (is_array($providers)) {
        return array_filter($providers);
      }
      foreach ($providers as $key => $value) {
        $unescapedKey = $this->unescapeProviderId($key);
        $unescapedProviders[$unescapedKey] = $value;
      }
      return $unescapedProviders;

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
   * Get users.
   */
  public function getUsers() {
    $exclude = $this->getConfiguration('exclude');
    if (isset($exclude['users'])) {
      return $exclude['users'];
    }
    return FALSE;
  }

  /**
   * Save.
   */
  public function saveConfig() {
    $this->moduleConfig->save();
  }

  /**
   * Set configuration.
   */
  public function setConfiguration(string $configName, array $config): void {
    $config[$configName] = $config;
  }

  /**
   * Get configuration.
   */
  public function getConfiguration(string $configName): array|string {
    return $this->moduleConfig->get($configName);
  }

  /**
   * Get active user providers.
   *
   * @phpstan-return array<mixed, mixed>
   */
  public function getAllUserProviders(): array {
    // @todo maybe do this in a more elegant way.
    // Stolen from here: https://git.drupalcode.org/project/openid_connect/-/blob/3.x/src/Form/OpenIDConnectLoginForm.php?ref_type=heads#L78
    $clients = $this->oidcStorage->loadByProperties(['status' => TRUE]);
    $providerIds = [];
    $openIdConnectPrefix = 'openid_connect.';
    foreach ($clients as $client_id => $client) {
      $providerId = $openIdConnectPrefix . $client_id;
      $providerIds[$providerId] = $client->label();
    }

    return $providerIds;
  }

  /**
   * Unescape provider id.
   */
  private function unescapeProviderId(string $input) {
    // Drupal will not accept a . in configuration keys.
    // https://www.drupal.org/node/2297311
    return str_replace("__dot__", ".", $input);
  }

}
