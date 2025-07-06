<?php

namespace Drupal\eth_balance\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\eth_proxy\Service\EthProxyService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Provides a block to display an Ethereum address balance.
 *
 * @Block(
 *   id = "eth_balance_block",
 *   admin_label = @Translation("Ethereum Balance"),
 * )
 */
class EthBalanceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Le service EthProxyService injecté.
   *
   * @var \Drupal\eth_proxy\Service\EthProxyService
   */
  protected EthProxyService $ethProxy;

  /**
   * Constructs a new EthBalanceBlock instance.
   *
   * @param array $configuration
   *   Plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\eth_proxy\Service\EthProxyService $eth_proxy
   *   Le service centralisé pour les appels JSON-RPC.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EthProxyService $eth_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ethProxy = $eth_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      // Notez bien le service id défini dans eth_proxy.services.yml
      $container->get('eth_proxy.rpc')
    );
  }

  /**
   * Convertit une chaîne hexadécimale (sans 0x) en chaîne décimale (BCMath).
   *
   * @param string $hex
   *   Valeur hexadécimale (ex. '1a3f').
   * @return string
   *   Valeur décimale (ex. '6719').
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

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    // 1. Récupération de l'adresse Ethereum depuis la configuration du bloc.
    $config = $this->getConfiguration();
    $address = $config['address'] ?? '';
    if (empty($address)) {
      return [
        '#markup' => $this->t('Aucune adresse Ethereum configurée.'),
      ];
    }

    // 2. Appel au service eth_proxy pour récupérer le solde (hexadécimal).
    /** @var \Drupal\eth_proxy\Service\EthProxyService $proxy */
    $proxy = $this->ethProxy;
    $raw = $proxy->request('eth_getBalance', [$address, 'latest']);

    // 3. Extraction du champ "result" si nécessaire.
    if (is_array($raw) && isset($raw['result'])) {
      $hex = (string) $raw['result'];
    }
    else {
      $hex = (string) $raw;
    }

    // 4. Normalisation de l'hex (suppression du préfixe "0x").
    $hexVal = preg_replace('/^0x/i', '', $hex);

    // 5. Validation et conversion.
    if ($hexVal === '' || !ctype_xdigit($hexVal)) {
      \Drupal::logger('eth_balance')->error(
        'eth_getBalance invalide pour @address : @hex',
        ['@address' => $address, '@hex' => $hex]
      );
      // Définit le solde initial à zéro en cas d’erreur.
      $initial_balance = '0.000000000000000000';
    }
    else {
      // Conversion hex → décimal (wei).
      $wei = $this->hexToDec($hexVal);
      // Conversion wei → ETH (division par 10^18).
      $factor = bcpow('10', '18', 0);
      $initial_balance = bcdiv($wei, $factor, 18);
    }

    // 6. Construction du render array avec template Twig et JS pour le polling.
    return [
      '#theme'    => 'eth_balance_block',
      '#address'  => $address,
      '#balance'  => $initial_balance,
      '#endpoint' =>
        Url::fromRoute('eth_balance.balance', ['address' => $address])->toString(),
      '#attached' => [
        'library' => ['eth_balance/real_time_balance'],
      ],
      '#cache'    => [
        'tags' => ['eth_balance_block:' . $address],
      ],
    ];
  }

}
