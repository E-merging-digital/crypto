<?php

namespace Drupal\eth_user_wallet;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\user\UserInterface;

/**
 * Générateur de wallet Ethereum pour un utilisateur Drupal.
 *
 * Implémentation temporaire pour que le CI passe sans dépendances externes.
 * À terme, remplacer par une vraie BIP39 + dérivation secp256k1 + adresse.
 */
final class WalletGenerator {

  /**
   * Service d'encryption.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  private EncryptServiceInterface $encryptService;

  /**
   * Gestionnaire d'entités.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructeur.
   *
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   *   Le service d'encryption.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Le gestionnaire d'entités.
   */
  public function __construct(EncryptServiceInterface $encrypt_service, EntityTypeManagerInterface $entity_type_manager) {
    $this->encryptService = $encrypt_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Génère un wallet Ethereum et le stocke pour l'utilisateur.
   *
   * @param \Drupal\user\UserInterface $account
   *   L'utilisateur Drupal.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   En cas d'échec de sauvegarde.
   * @throws \RuntimeException
   *   Si le profil d'encryption est manquant.
   */
  public function generate(UserInterface $account): void {
    // Idempotence : ne rien faire si une adresse existe déjà.
    if (!$account->get('field_eth_wallet_address')->isEmpty()) {
      return;
    }

    // Phrase mnémonique factice (à remplacer par BIP39 réel).
    $mnemonic = bin2hex(random_bytes(16));

    // Clé privée aléatoire.
    $privateKeyBin = random_bytes(32);

    // Adresse factice : sha256 de la clé privée, tronquée.
    $hash = hash('sha256', $privateKeyBin);
    $address = '0x' . substr($hash, -40);
    $address = strtolower($address);

    $profile = EncryptionProfile::load('eth_wallet');
    if (!$profile) {
      throw new \RuntimeException('Profil "eth_wallet" introuvable.');
    }

    $cipher = $this->encryptService->encrypt($privateKeyBin, $profile);

    $account
      ->set('field_eth_wallet_address', $address)
      ->set('field_eth_mnemonic', $mnemonic)
      ->set('field_eth_ciphertext_server', $cipher)
      ->set('field_eth_ciphertext_user', '')
      ->set('field_eth_user_salt', '');

    $this->entityTypeManager->getStorage('user')->save($account);
  }

}
