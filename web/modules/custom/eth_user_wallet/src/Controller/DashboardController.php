<?php

declare(strict_types=1);

namespace Drupal\eth_user_wallet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\eth_proxy\Service\EthProxyService;

/**
 * Controller du dashboard Ethereum.
 */
class DashboardController extends ControllerBase {

  private EthProxyService $ethProxy;

  /**
   * Constructeur.
   *
   * @param \Drupal\eth_proxy\Service\EthProxyService $eth_proxy
   *   Service JSON-RPC Ethereum.
   */
  public function __construct(EthProxyService $eth_proxy) {
    $this->ethProxy = $eth_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('eth_proxy.rpc'));
  }

  /**
   * Affiche le dashboard.
   *
   * @param \Drupal\user\UserInterface $user
   *   L’utilisateur cible.
   *
   * @return array
   *   Render array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function dashboard(UserInterface $user): array {
    if ($this->currentUser()->id() !== $user->id()) {
      throw $this->createAccessDeniedException();
    }

    $address = $user->get('field_eth_address')->value;
    $raw     = $this->ethProxy->request('eth_getBalance', [$address, 'latest']);
    $hex     = is_array($raw) && isset($raw['result']) ? $raw['result'] : (string) $raw;
    $hexVal  = preg_replace('/^0x/i', '', $hex);

    // hex→dec→ETH
    $wei = $this->hexToDec($hexVal);
    $eth = bcdiv($wei, bcpow('10','18',0), 18);

    // Clé privée/mnemonic déchiffrés
    $private   = $this->tempStoreFactory()->get('eth_user_wallet')->get('decrypted_private') ?? '';
    $mnemonic  = $private ? $user->get('field_eth_mnemonic')->value : '';

    return [
      '#theme'    => 'eth_user_wallet_dashboard',
      '#address'  => $address,
      '#balance'  => $eth,
      '#mnemonic' => $mnemonic,
      '#private'  => $private,
    ];
  }

  /**
   * Convertit une hex string en décimal via BCMath.
   */
  private function hexToDec(string $hex): string {
    $dec = '0';
    foreach (str_split($hex) as $c) {
      $dec = bcadd(bcmul($dec, '16', 0), (string) hexdec($c), 0);
    }
    return $dec;
  }

}
