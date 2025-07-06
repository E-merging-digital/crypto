<?php

namespace Drupal\eth_transactions\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\eth_proxy\Service\EthProxyService;

class EthTransactionsController extends ControllerBase {
  protected EthProxyService $proxy;

  public function __construct(EthProxyService $proxy) {
    $this->proxy = $proxy;
  }

  /**
   * Liste des transactions pour une adresse.
   */
  public function list(string $address): JsonResponse {
    // TODO: utiliser $this->proxy->request('eth_getTransactionByAddress', [$address]);
    return new JsonResponse([]);
  }
}
