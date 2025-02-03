<?php
// phpcs:ignoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\azure_ad_delta_sync\UserManager' "web/modules/contrib/azure_ad_delta_sync/src".
 */

namespace Drupal\azure_ad_delta_sync\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\azure_ad_delta_sync\UserManager.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class UserManager implements \Drupal\azure_ad_delta_sync\UserManagerInterface
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
         * @var \Drupal\azure_ad_delta_sync\UserManager
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
        public function setOptions(array $options): void
        {
            $this->lazyLoadItself()->setOptions($options);
        }

        /**
         * {@inheritdoc}
         */
        public function loadManagedUserIds(): array
        {
            return $this->lazyLoadItself()->loadManagedUserIds();
        }

        /**
         * {@inheritdoc}
         */
        public function collectUsersForDeletionList(): void
        {
            $this->lazyLoadItself()->collectUsersForDeletionList();
        }

        /**
         * {@inheritdoc}
         */
        public function removeUsersFromDeletionList(array $users): void
        {
            $this->lazyLoadItself()->removeUsersFromDeletionList($users);
        }

        /**
         * {@inheritdoc}
         */
        public function commitDeletionList(): void
        {
            $this->lazyLoadItself()->commitDeletionList();
        }

        /**
         * {@inheritdoc}
         */
        public function setStringTranslation(\Drupal\Core\StringTranslation\TranslationInterface $translation)
        {
            return $this->lazyLoadItself()->setStringTranslation($translation);
        }

    }

}
