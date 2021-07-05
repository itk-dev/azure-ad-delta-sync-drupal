<?php

namespace Drupal\adgangsstyring\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class SettingsForm extends ConfigFormBase {
  public const SETTINGS = 'adgangsstyring.settings';

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

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('The client id.'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('The client secret.'),
      '#required' => TRUE,
    ];

    $form['group_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group id'),
      '#default_value' => $config->get('group_id'),
      '#description' => $this->t('The group id.'),
      '#required' => TRUE,
    ];

    $form['tenant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tenant id'),
      '#default_value' => $config->get('tenant_id'),
      '#description' => $this->t('The tenant id.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
