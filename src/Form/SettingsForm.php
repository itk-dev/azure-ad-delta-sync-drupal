<?php

namespace Drupal\adgangsstyring\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form.
 */
class SettingsForm extends ConfigFormBase {
  public const SETTINGS = 'adgangsstyring.settings';

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   */
  private $userStorage;

  /**
   * The user role storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|mixed|object
   */
  private $roleStorage;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManager $entityTypeManager) {
    parent::__construct($configFactory);
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adgangsstyring_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config(self::SETTINGS);

    $form['api_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API settings'),

      'client_id' => [
        '#type' => 'textfield',
        '#title' => $this->t('Client id'),
        '#default_value' => $config->get('client_id'),
        '#description' => $this->t("The client id. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['client_id'] = '…';</code>"),
        '#required' => TRUE,
      ],

      'client_secret' => [
        '#type' => 'textfield',
        '#title' => $this->t('Client secret'),
        '#default_value' => $config->get('client_secret'),
        '#description' => $this->t("The client secret. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['client_secret'] = '…';</code>"),
        '#required' => TRUE,
      ],

      'group_id' => [
        '#type' => 'textfield',
        '#title' => $this->t('Group id'),
        '#default_value' => $config->get('group_id'),
        '#description' => $this->t("The group id. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['group_id'] = '…';</code>"),
        '#required' => TRUE,
      ],

      'tenant_id' => [
        '#type' => 'textfield',
        '#title' => $this->t('Tenant id'),
        '#default_value' => $config->get('tenant_id'),
        '#description' => $this->t("The tenant id. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['tenant_id'] = '…';</code>"),
        '#required' => TRUE,
      ],
    ];

    $form['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('When cancelling an account'),
      '#default_value' => $config->get('user_cancel_method'),
      '#required' => TRUE,
    ] + user_cancel_methods();

    $form['exclusions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclusions'),
    ];

    $options = [];
    foreach ($this->roleStorage->loadMultiple() as $role) {
      $options[$role->id()] = $role->label();
    }
    $form['exclusions']['excluded_roles'] = [
      '#title' => $this->t('Excluded roles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $config->get('excluded_roles') ?: [],
    ];

    $form['exclusions']['excluded_users'] = [
      '#title' => $this->t('Excluded users'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => $this->userStorage->loadMultiple($config->get('excluded_users') ?: [-1]),
      '#tags' => TRUE,
      '#description' => $this->t('Separate by comma.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::SETTINGS);
    $config->set('client_id', $form_state->getValue('client_id'));
    $config->set('client_secret', $form_state->getValue('client_secret'));
    $config->set('group_id', $form_state->getValue('group_id'));
    $config->set('tenant_id', $form_state->getValue('tenant_id'));
    $config->set('user_cancel_method', $form_state->getValue('user_cancel_method'));
    $config->set('excluded_roles', $form_state->getValue('excluded_roles'));
    $config->set('excluded_users', array_column($form_state->getValue('excluded_users') ?? [], 'target_id'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGS,
    ];
  }

}
