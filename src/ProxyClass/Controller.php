<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\azure_ad_delta_sync\Controller' "web/modules/contrib/azure_ad_delta_sync_drupal/src".
 */

namespace Drupal\azure_ad_delta_sync\ProxyClass {

  /**
   * Provides a proxy class for \Drupal\azure_ad_delta_sync\Controller.
   *
   * @see \Drupal\Component\ProxyBuilder
   */
  class Controller implements \Drupal\azure_ad_delta_sync\ControllerInterface
  {

    use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

    /**
     * The id of the original proxied service.
     *
     * @var string
     */
    protected $drupalProxyOriginalServiceId;

    /**
     * The real proxied service, after it was lazy loaded.
     *
     * @var \Drupal\azure_ad_delta_sync\Controller
     */
    protected $service;

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Constructs a ProxyClass Drupal proxy object.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *   The container.
     * @param string $drupal_proxy_original_service_id
     *   The service ID of the original service.
     */
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
    {
      $this->container = $container;
      $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
    }

    /**
     * Lazy loads the real service from the container.
     *
     * @return object
     *   Returns the constructed real service.
     */
    protected function lazyLoadItself()
    {
      if (!isset($this->service)) {
        $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
      }

      return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function run(\ItkDev\AzureAdDeltaSync\Handler\HandlerInterface $handler): void
    {
      $this->lazyLoadItself()->run($handler);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
    {
      $this->lazyLoadItself()->configureOptions($resolver);
    }

  }

}
