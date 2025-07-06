<?php
namespace Drupal\eth_proxy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class EthProxyService {
  protected ConfigFactoryInterface $configFactory;
  protected ClientInterface $httpClient;

  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * Execute a JSON-RPC request to the Ethereum node.
   *
   * @param string $method
   *   JSON-RPC method name.
   * @param array $params
   *   Parameters array.
   *
   * @return array|string  Tableau pour les méthodes retournant des listes JSON-RPC, ou chaîne hexadécimale pour les balances
   *   RPC response as associative array.
   *
   * @throws \Exception
   */
  public function request(string $method, array $params = []): array|string {
    $config = $this->configFactory->get('eth_proxy.settings');
    $url = $config->get('node_url');
    $jwt = trim($config->get('jwt_token'));

    $payload = [
      'jsonrpc' => '2.0',
      'id' => uniqid(),
      'method' => $method,
      'params' => $params,
    ];

    $options = ['json' => $payload];
    if (!empty($jwt)) {
      $options['headers']['Authorization'] = 'Bearer ' . $jwt;
    }

    try {
      $response = $this->httpClient->request('POST', $url, $options);
      $data = json_decode($response->getBody()->__toString(), TRUE);
      if (isset($data['error'])) {
        throw new \Exception('RPC Error: ' . $data['error']['message']);
      }
      return $data['result'];
    }
    catch (GuzzleException $e) {
      throw new \Exception('HTTP Error: ' . $e->getMessage());
    }
  }
}
