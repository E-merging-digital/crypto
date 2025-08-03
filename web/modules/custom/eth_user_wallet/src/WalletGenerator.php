<?php

declare(strict_types=1);

namespace Drupal\eth_user_wallet;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\user\UserInterface;
use Web3p\EthereumWallet\Wallet;

/**
 * Service de génération et stockage du wallet Ethereum.
 */
final class WalletGenerator {

  private EncryptServiceInterface $encryptService;
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   *   Service d’encryption.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   EntityTypeManager pour persister l’utilisateur.
   */
  public function __construct(
    EncryptServiceInterface $encrypt_service,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->encryptService    = $encrypt_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Génère un wallet via web3p/ethereum-wallet et le stocke (serveur).
   *
   * @param \Drupal\user\UserInterface $account
   *   L’utilisateur Drupal.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generate(UserInterface $account): void {
    // Idempotence.
    if (!$account->get('field_eth_address')->isEmpty()) {
      return;
    }

    // 1) Génération BIP39 + clé privée + adresse.
    $wallet = new Wallet();
    $wallet->generate(12);

    $mnemonic   = $wallet->mnemonic;
    $privateKey = $wallet->privateKey;
    $address    = $wallet->address;

    // 2) Chiffrage « serveur » avec EncryptService.
    $profile = EncryptionProfile::load('eth_wallet');
    if (!$profile) {
      throw new \RuntimeException('Profil "eth_wallet" introuvable.');
    }
    $cipher = $this->encryptService->encrypt($privateKey, $profile);

    // 3) Stockage.
    $account
      ->set('field_eth_address', $address)
      ->set('field_eth_mnemonic', $mnemonic)
      ->set('field_eth_ciphertext_server', $cipher)
      ->set('field_eth_ciphertext_user', '')
      ->set('field_eth_user_salt', '');
    $this->entityTypeManager->getStorage('user')->save($account);
  }

}
