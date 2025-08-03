<?php

namespace Drupal\eth_user_wallet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulaire pour déchiffrer le wallet Ethereum.
 */
final class DecryptWalletForm extends FormBase {

  /**
   * Temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Constructs the form.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eth_user_wallet_decrypt_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Exemple d'utilisation du tempstore.
    $store = $this->tempStoreFactory->get('eth_user_wallet');
    $previous = $store->get('decrypted') ?? '';

    $form['decrypted'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Previously decrypted'),
      '#default_value' => $previous,
      '#disabled' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Decrypt'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Implémentation de décryptage fictive.
    $store = $this->tempStoreFactory->get('eth_user_wallet');
    $store->set('decrypted', 'secret-value');

    $this->messenger()->addStatus($this->t('Wallet decrypted.'));
  }

}
