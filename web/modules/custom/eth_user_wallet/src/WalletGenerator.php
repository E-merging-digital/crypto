<?php

namespace Drupal\eth_user_wallet;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Générateur de wallet Ethereum pour un utilisateur Drupal.
 *
 * NOTE : implémentation temporaire / de secours pour que le CI fonctionne sans
 * dépendances externes lourdes. Remplacer plus tard par une vraie génération
 * BIP39 + dérivation secp256k1 + adresse Ethereum via keccak256.
 */
final class WalletGenerator {

  /**
   * Service d'encryption.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface
   */
  private EncryptServiceInterface $encryptService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the generator.
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
   * Génère un wallet et le stocke pour l'utilisateur.
   *
   * @param \Drupal\user\UserInterface $account
   *   L’utilisateur Drupal.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   En cas d'échec de sauvegarde.
   */
  public function generate(UserInterface $account): void {
    // Idempotence : ne pas régénérer si déjà présent.
    if (!$account->get('field_eth_wallet_address')->isEmpty()) {
      return;
    }

    // ---- Ancienne implémentation avec web3p (retirée pour compatibilité PHP 8.3) ----
    // use Web3p\EthereumWallet\Wallet;
    // $wallet = new Wallet();
    // $wallet->generate(12);
    // $mnemonic = $wallet->mnemonic;
    // $privateKey = $wallet->privateKey;
    // $address = $wallet->address;
    // ---------------------------------------------------------------------------------

    // 1) Génération d'une phrase mnémonique factice (remplacer par BIP39 réel).
    $mnemonic = bin2hex(random_bytes(16)); // placeholder : 32 hex chars

    // 2) Génération d'une clé privée aléatoire (32 bytes).
    $privateKeyBin = random_bytes(32);
    $privateKeyHex = bin2hex($privateKeyBin);

    // 3) Dérivation simplifiée d'une "adresse" : sha256 de la clé privée, truncation.
    $hash = hash('sha256', $privateKeyBin);
    $address = '0x' . substr($hash, -40);
    $address = strtolower($address);

    // 4) Chiffrement de la clé privée (serveur).
    $profile = EncryptionProfile::load('eth_wallet');
    if (!$profile) {
      throw new \RuntimeException('Profil "eth_wallet" introuvable.');
    }

    $cipher = $this->encryptService->encrypt($privateKeyBin, $profile);

    // 5) Stockage dans les champs utilisateurs.
    $account
      ->set('field_eth_wallet_address', $address)
      ->set('field_eth_mnemonic', $mnemonic)
      ->set('field_eth_ciphertext_server', $cipher)
      ->set('field_eth_ciphertext_user', '')
      ->set('field_eth_user_salt', '');

    $this->entityTypeManager->getStorage('user')->save($account);
  }

}
`
