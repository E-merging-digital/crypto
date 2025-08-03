<?php

namespace Drupal\eth_proxy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Ethereum proxy node.
 */
final class EthProxySettingsForm extends ConfigFormBase {

  /**
   * Configuration key.
   */
  public const SETTINGS = 'eth_proxy.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eth_proxy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [static::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(static::SETTINGS);

    $form['node_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Ethereum node URL'),
      '#default_value' => $config->get('node_url') ?: '',
      '#description' => $this->t('URL du nœud Ethereum JSON-RPC'),
      '#required' => TRUE,
    ];

    $form['jwt_path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('JWT Token'),
      '#default_value' => $config->get('jwt_token') ?: '',
      '#description' => $this->t("JWT pour l'authentification auprès du nœud, si nécessaire."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('node_url', $form_state->getValue('node_url'))
      ->set('jwt_token', $form_state->getValue('jwt_path'))
      ->save();
  }

}
