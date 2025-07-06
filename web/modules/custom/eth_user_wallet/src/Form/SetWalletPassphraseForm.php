<?php

declare(strict_types=1);

namespace Drupal\eth_user_wallet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form pour que l’utilisateur définisse son passphrase wallet.
 */
class SetWalletPassphraseForm extends FormBase {

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
    return 'eth_user_wallet_set_passphrase';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL): array {
    $form['passphrase'] = [
      '#type' => 'password_confirm',
      '#size' => 64,
      '#required' => TRUE,
      '#password_confirm_label' => $this->t('Confirmer la passphrase'),
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enregistrer'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue('passphrase') !== $form_state->getValue('passphrase_confirm')) {
      $form_state->setErrorByName('passphrase_confirm', $this->t('Les mots de passe ne correspondent pas.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->currentUser()->getAccount();
    $passphrase = $form_state->getValue('passphrase');

    // 1) Charger ciphertext serveur + profile.
    $cipher_srv = $user->get('field_eth_ciphertext_server')->value;
    $profile = \Drupal\encrypt\Entity\EncryptionProfile::load('eth_wallet');
    $private = \Drupal::service('encryption')->decrypt($cipher_srv, $profile);

    // 2) Dérivation clé user via sodium.
    $salt = random_bytes(16);
    $key  = sodium_crypto_pwhash(
      SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
      $passphrase,
      $salt,
      SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
      SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
    );

    // 3) Re-chiffrage user.
    $nonce = random_bytes(24);
    $cipher_user = base64_encode($nonce . sodium_crypto_secretbox($private, $nonce, $key));

    // 4) Sauvegarde.
    $user
      ->set('field_eth_user_salt',       base64_encode($salt))
      ->set('field_eth_ciphertext_user', $cipher_user);
    $user->save();

    $this->messenger()->addStatus($this->t('Passphrase enregistrée.'));
    $form_state->setRedirect('eth_user_wallet.dashboard', ['user' => $user->id()]);
  }

}
