<?php

namespace Drupal\azure_ad_delta_sync\Form;

use Drupal\azure_ad_delta_sync\UserManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\azure_ad_delta_sync\Helpers\ConfigHelper;

/**
 * Settings form.
 */
final class SettingsForm extends ConfigFormBase {
  public const string SETTINGS = 'azure_ad_delta_sync.settings';

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\azure_ad_delta_sync\UserManagerInterface $userManager
   *   The user manager.
   * @param \Drupal\azure_ad_delta_sync\Helpers\ConfigHelper $configHelper
   *   The configuration helper.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    EntityTypeManagerInterface $entityTypeManager,
    private readonly UserManagerInterface $userManager,
    private readonly ConfigHelper $configHelper,
  ) {
    parent::__construct($configFactory);
    $this->userStorage = $entityTypeManager->getStorage('user');
    $this->roleStorage = $entityTypeManager->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('azure_ad_delta_sync.user_manager'),
      $container->get('azure_ad_delta_sync.config_helper'),
    );
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFormId() {
    return 'azure_ad_delta_sync_config';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed, mixed> $form
   * @phpstan-return array<mixed, mixed>
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config(self::SETTINGS);

    // Using the user manager will throw an exception if it's not configured
    // correctly (which is done by this form), so we try-catch use of the
    // manager.
    try {
      $form['info'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'status' => [
            $this->formatPlural(
              count($this->userManager->loadManagedUserIds()),
              'With the current (saved) settings, one user is managed by “Azure AD Delta Sync”',
              'With the current (saved) settings, @count users are managed by “Azure AD Delta Sync”',
            ),
          ],
        ],
        '#status_headings' => [
          'status' => $this->t('Information'),
        ],
      ];
    }
    catch (\Exception $exception) {
      $form['info'] = [
        '#theme' => 'status_messages',
        '#message_list' => [
          'warning' => [
            $this->t('Some settings are not valid (@message)', ['@message' => $exception->getMessage()]),
          ],
        ],
        '#status_headings' => [
          'warning' => $this->t('Warning'),
        ],
      ];
    }

    $defaultValues = $config->get('drupal');
    $form['drupal'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal user settings'),
      '#tree' => TRUE,
    ];

    $form['drupal']['user_cancel_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Cancel account method'),
      '#default_value' => $defaultValues['user_cancel_method'] ?? NULL,
      '#required' => TRUE,
      '#description' => $this->t('Method used to cancel a Drupal user account.'),
    ] + user_cancel_methods();

    $form['drupal']['user_id_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal user id field'),
      '#default_value' => $defaultValues['user_id_field'] ?? 'name',
      '#description' => $this->t('The Drupal user id field used to match with an Azure user id (cf. Azure user id claim).'),
      '#required' => TRUE,
    ];

    $defaultValues = $config->get('azure');
    $form['azure'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Azure API settings'),
      '#tree' => TRUE,
    ];

    $form['azure']['description'] = [
      '#markup' => $this->t('<p>Settings for connection to the Azure API to get users. Your IdP provider can provide the actual values needed and, for security reasons, these should be set in <code>settings.local.php</code>.</p>'),
    ];

    $form['azure']['security_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure security key'),
      '#default_value' => $defaultValues['security_key'] ?? NULL,
      '#description' => $this->t("The Azure security key. Should be set in <code>settings.local.php</code>: <code>\$config['azure_ad_delta_sync.settings']['azure']['security_key'] = '…';</code>."),
      '#required' => TRUE,
    ];

    $form['azure']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure client secret'),
      '#default_value' => $defaultValues['client_secret'] ?? NULL,
      '#description' => $this->t("The Azure client secret. Should be set in <code>settings.local.php</code>: <code>\$config['azure_ad_delta_sync.settings']['azure']['client_secret'] = '…';</code>."),
      '#required' => TRUE,
    ];

    $form['azure']['uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure uri'),
      '#default_value' => $defaultValues['uri'] ?? NULL,
      '#description' => $this->t("The Azure uri. Should be set in <code>settings.local.php</code>: <code>\$config['azure_ad_delta_sync.settings']['azure']['uri'] = '…';</code>."),
      '#required' => TRUE,
    ];

    $form['azure']['user_id_claim'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Azure user id claim'),
      '#default_value' => $defaultValues['user_id_claim'] ?? 'userPrincipalName',
      '#description' => $this->t('The Azure user id claim matching a Drupal user id (cf. Drupal user id field).'),
      '#required' => TRUE,
    ];

    $defaultValues = $config->get('include');
    $form['include'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Include users'),
      '#tree' => TRUE,
    ];

    $options = $this->configHelper->getAllUserProviders();

    $form['include']['providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('providers'),
      '#options' => $options,
      '#default_value' => $defaultValues['providers'] ?? [],
      '#description' => $this->t('Manage only Drupal users authenticated by one of the selected providers. If no providers are selected all Drupal users are managed unless excluded (cf. “Exclude users”).'),
    ];

    $defaultValues = $config->get('exclude') ?? [];
    $form['exclude'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclude users'),
      '#tree' => TRUE,
      '#description' => $this->t('Exclude some users from being deleted by Azure AD Delta Sync.'),
    ];

    $options = [];
    foreach ($this->roleStorage->loadMultiple() as $role) {
      if (in_array($role->id(), ['anonymous', 'authenticated'], TRUE)) {
        continue;
      }
      $options[$role->id()] = $role->label();
    }
    $form['exclude']['roles'] = [
      '#title' => $this->t('Excluded roles'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $defaultValues['roles'] ?? [],
      '#description' => $this->t('Select Drupal user roles to exclude.'),
    ];

    $form['exclude']['users'] = [
      '#title' => $this->t('Excluded users'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      // By default we exclude user 1.
      '#default_value' => $this->userStorage->loadMultiple($defaultValues['users'] ?? [1]),
      '#tags' => TRUE,
      '#description' => $this->t('Select Drupal users to exclude (separate by comma).'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<mixed, mixed> $form
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configHelper->setConfiguration('drupal', $form_state->getValue('drupal'));
    $this->configHelper->setConfiguration('azure', $form_state->getValue('azure'));
    $this->configHelper->setConfiguration('include', $form_state->getValue('include'));
    $exclude = $this->configHelper->getConfiguration('exclude');
    // Extract user ids.
    $exclude['users'] = array_column($exclude['users'] ?? [], 'target_id');
    $this->configHelper->setConfiguration('exclude', $exclude);
    $this->configHelper->saveConfig();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<mixed, mixed>
   */
  #[\Override]
  protected function getEditableConfigNames(): array {
    return [
      self::SETTINGS,
    ];
  }

}
