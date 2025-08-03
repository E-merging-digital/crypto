<?php

namespace Drupal\eth_proxy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\ClientInterface;

/**
 * Service to proxy JSON-RPC calls to an Ethereum node.
 */
final class EthProxyService {

  /**
   * Configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private ClientInterface $httpClient;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   HTTP client.
   */
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
   * @return array|string
   *   RPC response as associative array, or hex string for balances.
   *
   * @throws \RuntimeException
   *   If the request fails or the RPC returns an error.
   */
  public function request(string $method, array $params = []): array|string {
    $config = $this->configFactory->get('eth_proxy.settings');
    $url = $config->get('node_url');
    $jwt = trim((string) $config->get('jwt_token'));

    $payload = [
      'jsonrpc' => '2.0',
      'id' => uniqid('', TRUE),
      'method' => $method,
      'params' => $params,
    ];

    $options = ['json' => $payload];
    if ($jwt !== '') {
      $options['headers']['Authorization'] = 'Bearer ' . $jwt;
    }

    try {
      $response = $this->httpClient->request('POST', $url, $options);
      $data = json_decode($response->getBody()->__toString(), TRUE);
      if (isset($data['error'])) {
        throw new \RuntimeException('RPC Error: ' . ($data['error']['message'] ?? 'Unknown'));
      }
      return $data['result'] ?? '';
    }
    catch (GuzzleException $e) {
      throw new \RuntimeException('HTTP Error: ' . $e->getMessage(), 0, $e);
    }
  }

}
