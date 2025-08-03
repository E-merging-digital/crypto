<?php

namespace Drupal\eth_balance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\eth_proxy\Service\EthProxyService;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for retrieving Ethereum balance.
 */
final class EthBalanceController extends ControllerBase {

  /**
   * The Ethereum proxy service.
   *
   * @var \Drupal\eth_proxy\Service\EthProxyService
   */
  private EthProxyService $ethProxy;

  /**
   * Logger channel for eth_balance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\eth_proxy\Service\EthProxyService $eth_proxy
   *   The eth proxy service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger for the eth_balance channel.
   */
  public function __construct(EthProxyService $eth_proxy, LoggerChannelInterface $logger) {
    $this->ethProxy = $eth_proxy;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('eth_proxy.rpc'),
      $container->get('logger.factory')->get('eth_balance')
    );
  }

  /**
   * Returns the balance for a given Ethereum address.
   *
   * @param string $address
   *   The Ethereum address.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing the address and its balance in ETH.
   */
  public function balance(string $address): JsonResponse {
    try {
      $raw = $this->ethProxy->request('eth_getBalance', [$address, 'latest']);
      $hex = is_array($raw) && isset($raw['result']) ? $raw['result'] : (string) $raw;
      $hexVal = preg_replace('/^0x/i', '', $hex);

      if ($hexVal === '' || !ctype_xdigit($hexVal)) {
        throw new \RuntimeException(sprintf('Invalid hex balance for address %s: %s', $address, $hex));
      }

      $wei = $this->hexToDec($hexVal);
      $eth = bcdiv($wei, bcpow('10', '18', 0), 18);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to get balance for @address: @message', [
        '@address' => $address,
        '@message' => $e->getMessage(),
      ]);
      $eth = '0.000000000000000000';
    }

    return new JsonResponse([
      'address' => $address,
      'balance' => $eth,
    ]);
  }

  /**
   * Converts a hex string (without 0x) to decimal using BCMath.
   *
   * @param string $hex
   *   Hexadecimal string.
   *
   * @return string
   *   Decimal representation.
   */
  protected function hexToDec(string $hex): string {
    $dec = '0';
    foreach (str_split($hex) as $c) {
      $dec = bcadd(bcmul($dec, '16', 0), (string) hexdec($c), 0);
    }
    return $dec;
  }

}
