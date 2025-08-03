<?php

namespace Drupal\eth_user_wallet\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulaire de définition de la passphrase du wallet.
 */
final class SetWalletPassphraseForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Service d'encryption.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  private EncryptServiceInterface $encryption;

  /**
   * Gestionnaire de types d'entité.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EncryptServiceInterface $encryption, EntityTypeManagerInterface $entityTypeManager) {
    $this->encryption = $encryption;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('encryption'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'eth_user_wallet_set_passphrase_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['passphrase'] = [
      '#type' => 'password',
      '#title' => $this->t('Passphrase'),
      '#required' => TRUE,
    ];

    $form['passphrase_confirm'] = [
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
      $form_state->setErrorByName('passphrase_confirm', $this->t('La passphrase et sa confirmation ne correspondent pas.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\user\UserInterface|null $user */
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
    if (!$user) {
      $this->messenger()->addError($this->t('Utilisateur introuvable.'));
      return;
    }

    $passphrase = $form_state->getValue('passphrase');

    $cipher_srv = $user->get('field_eth_ciphertext_server')->value;
    $profile = EncryptionProfile::load('eth_wallet');
    if (!$profile) {
      $this->messenger()->addError($this->t('Profil d’encryption introuvable.'));
      return;
    }

    // Décryptage avec le service injecté :contentReference[oaicite:5]{index=5}.
    $private = $this->encryption->decrypt($cipher_srv, $profile);

    // Dérivation de la clé utilisateur via sodium.
    $salt = random_bytes(16);
    $key = sodium_crypto_pwhash(
      SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
      $passphrase,
      $salt,
      SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
      SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
    );

    // Re-chiffrage user.
    $nonce = random_bytes(24);
    $cipher_user = base64_encode($nonce . sodium_crypto_secretbox($private, $nonce, $key));

    // Sauvegarde.
    $user
      ->set('field_eth_user_salt', base64_encode($salt))
      ->set('field_eth_ciphertext_user', $cipher_user);
    $user->save();

    $this->messenger()->addStatus($this->t('Passphrase enregistrée.'));
    $form_state->setRedirect('eth_user_wallet.dashboard', ['user' => $user->id()]);
  }

}
