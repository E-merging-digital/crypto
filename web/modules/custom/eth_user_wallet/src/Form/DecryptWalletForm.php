<?php

declare(strict_types=1);

namespace Drupal\eth_user_wallet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form pour déchiffrer et afficher la clé privée/mnemonic.
 */
class DecryptWalletForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eth_user_wallet_decrypt_wallet';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL): array {
    $form['passphrase'] = [
      '#type' => 'password',
      '#size' => 64,
      '#required' => TRUE,
      '#title' => $this->t('Passphrase'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Déchiffrer'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $user       = $this->currentUser()->getAccount();
    $passphrase = $form_state->getValue('passphrase');

    // Récupération des données.
    $salt_b64   = $user->get('field_eth_user_salt')->value;
    $cipher_b64 = $user->get('field_eth_ciphertext_user')->value;
    $salt       = base64_decode($salt_b64);
    $data       = base64_decode($cipher_b64);
    $nonce      = substr($data, 0, 24);
    $cipher     = substr($data, 24);

    // Dérivation clé user + déchiffrement.
    $key     = sodium_crypto_pwhash(
      SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
      $passphrase,
      $salt,
      SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
      SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
    );
    $private = sodium_crypto_secretbox_open($cipher, $nonce, $key);
    if ($private === false) {
      $form_state->setErrorByName('passphrase', $this->t('Passphrase incorrecte.'));
      return;
    }

    // Stockage en session temporaire.
    $this->tempStoreFactory()->get('eth_user_wallet')->set('decrypted_private', $private);
    $form_state->setRedirect('eth_user_wallet.dashboard', ['user' => $user->id()]);
  }

}
