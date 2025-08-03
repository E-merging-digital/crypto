<?php

namespace Drupal\eth_balance\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eth_proxy\Service\EthProxyService;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Ethereum balance block.
 *
 * @Block(
 *   id = "eth_balance_block",
 *   admin_label = @Translation("Ethereum balance")
 * )
 */
final class EthBalanceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The eth proxy service.
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
   * Constructs the block.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\eth_proxy\Service\EthProxyService $eth_proxy
   *   Ethereum RPC proxy service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger channel.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EthProxyService $eth_proxy, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ethProxy = $eth_proxy;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('eth_proxy.rpc'),
      $container->get('logger.factory')->get('eth_balance')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $address = $config['address'] ?? '';
    if (empty($address)) {
      return [
        '#markup' => $this->t('Aucune adresse Ethereum configurée.'),
      ];
    }

    /** @var \Drupal\eth_proxy\Service\EthProxyService $proxy */
    $proxy = $this->ethProxy;
    $raw = $proxy->request('eth_getBalance', [$address, 'latest']);

    if (is_array($raw) && isset($raw['result'])) {
      $hex = (string) $raw['result'];
    }
    else {
      $hex = (string) $raw;
    }

    $hexVal = preg_replace('/^0x/i', '', $hex);

    if ($hexVal === '' || !ctype_xdigit($hexVal)) {
      $this->logger->error('eth_getBalance invalide pour @address : @hex', [
        '@address' => $address,
        '@hex' => $hex,
      ]);
      $initial_balance = '0.000000000000000000';
    }
    else {
      $wei = $this->hexToDec($hexVal);
      $factor = bcpow('10', '18', 0);
      $initial_balance = bcdiv($wei, $factor, 18);
    }

    return [
      '#theme' => 'eth_balance_block',
      '#address' => $address,
      '#balance' => $initial_balance,
      '#endpoint' => Url::fromRoute('eth_balance.balance', ['address' => $address])->toString(),
      '#attached' => [
        'library' => ['eth_balance/real_time_balance'],
      ],
      '#cache' => [
        'tags' => ['eth_balance_block:' . $address],
      ],
    ];
  }

  /**
   * Convertit une chaîne hexadécimale (sans 0x) en chaîne décimale (BCMath).
   *
   * @param string $hex
   *   Valeur hexadécimale.
   *
   * @return string
   *   Valeur décimale.
   */
  protected function hexToDec(string $hex): string {
    $dec = '0';
    $length = strlen($hex);
    for ($i = 0; $i < $length; $i++) {
      $digit = hexdec($hex[$i]);
      $dec = bcadd(bcmul($dec, '16', 0), (string) $digit, 0);
    }
    return $dec;
  }

}
