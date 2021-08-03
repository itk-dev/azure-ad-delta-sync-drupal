<?php

namespace Drupal\adgangsstyring\Form;

use Drupal\adgangsstyring\UserManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The user manager.
   *
   * @var \Drupal\adgangsstyring\UserManager
   */
  private $userManager;

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\adgangsstyring\UserManager $userManager
   *   The user manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManager $entityTypeManager, ModuleHandlerInterface $moduleHandler, UserManager $userManager) {
    parent::__construct($configFactory);
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
    $this->moduleHandler = $moduleHandler;
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('adgangsstyring.user_manager')
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

    $form['info'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => [
          $this->formatPlural(
            count($this->userManager->loadUserIds()),
            'With the current (saved) settings, one user is considered for cancellation by “adgangsstyring”',
            'With the current (saved) settings, @count users are considered for cancellation by “adgangsstyring”',
          ),
        ],
      ],
      '#status_headings' => [
        'status' => $this->t('Information'),
      ],
    ];

    $defaultsValues = $config->get('general');
    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#tree' => TRUE,
    ];

    $form['general']['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('When cancelling an account'),
      '#default_value' => $defaultsValues['user_cancel_method'] ?? NULL,
      '#required' => TRUE,
    ] + user_cancel_methods();

    $form['general']['user_id_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal user id field'),
      '#default_value' => $defaultsValues['user_id_field'] ?? 'name',
      '#description' => $this->t('The Drupal user id field (matching Azure user id claim).'),
      '#required' => TRUE,
    ];

    $defaultsValues = $config->get('api');
    $form['api'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('API settings'),
      '#tree' => TRUE,
    ];

    $form['api']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#default_value' => $defaultsValues['client_id'] ?? NULL,
      '#description' => $this->t("The client id. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['client_id'] = '…';</code>"),
      '#required' => TRUE,
    ];

    $form['api']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $defaultsValues['client_secret'] ?? NULL,
      '#description' => $this->t("The client secret. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['client_secret'] = '…';</code>"),
      '#required' => TRUE,
    ];

    $form['api']['group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group id'),
      '#default_value' => $defaultsValues['group_id'] ?? NULL,
      '#description' => $this->t("The group id. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['group_id'] = '…';</code>"),
      '#required' => TRUE,
    ];

    $form['api']['tenant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tenant id'),
      '#default_value' => $defaultsValues['tenant_id'] ?? NULL,
      '#description' => $this->t("The tenant id. Should be set in <code>settings.local.php</code>: <code>\$config['adgangsstyring.settings']['tenant_id'] = '…';</code>"),
      '#required' => TRUE,
    ];

    $form['api']['user_id_claim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure user id claim'),
      '#default_value' => $defaultsValues['user_id_claim'] ?? 'userPrincipalName',
      '#description' => $this->t('The Azure user id claim (matching Drupal user id field).'),
      '#required' => TRUE,
    ];

    $defaultsValues = $config->get('modules');
    $form['modules'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Modules'),
      '#description' => $this->t('Limit to users authenticated by one of the selected modules. If none are selected all users not excluded otherwise are included.'),
      '#tree' => TRUE,
    ];

    $form['modules']['openid_connect'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('OpenId Connect'),
      '#default_value' => $defaultsValues['openid_connect'] ?? NULL,
      '#disabled' => !$this->moduleHandler->moduleExists('openid_connect'),
    ];
    $form['modules']['samlauth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('SAML Authentication'),
      '#default_value' => $defaultsValues['samlauth'] ?? NULL,
      '#disabled' => !$this->moduleHandler->moduleExists('samlauth'),
    ];

    $defaultsValues = $config->get('exclusions') ?? [];
    $form['exclusions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclusions'),
      '#tree' => TRUE,
    ];

    $options = [];
    foreach ($this->roleStorage->loadMultiple() as $role) {
      if (in_array($role->id(), ['anonymous', 'authenticated'], TRUE)) {
        continue;
      }
      $options[$role->id()] = $role->label();
    }
    $form['exclusions']['roles'] = [
      '#title' => $this->t('Excluded roles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $defaultsValues['roles'] ?: [],
    ];

    $form['exclusions']['users'] = [
      '#title' => $this->t('Excluded users'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      // By default we exclude user 1.
      '#default_value' => $this->userStorage->loadMultiple($defaultsValues['users'] ?? [1]),
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
    $config->set('general', $form_state->getValue('general'));
    $config->set('api', $form_state->getValue('api'));
    $config->set('modules', $form_state->getValue('modules'));
    $exclusions = $form_state->getValue('exclusions');
    // Extract user ids.
    $exclusions['users'] = array_column($exclusions['users'] ?? [], 'target_id');
    $config->set('exclusions', $exclusions);
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
