<?php

namespace Drupal\eth_balance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\eth_proxy\Service\EthProxyService;

class EthBalanceController extends ControllerBase {
  public function balance(string $address): JsonResponse {
    /** @var EthProxyService $proxy */
    $proxy = \Drupal::service('eth_proxy.rpc');
    $raw = $proxy->request('eth_getBalance', [$address, 'latest']);
    $hex = is_array($raw) && isset($raw['result']) ? $raw['result'] : (string) $raw;
    $hexVal = preg_replace('/^0x/i', '', $hex);
    // (Vous pouvez réutiliser hexToDec() depuis votre bloc si vous la mettez
    // par exemple dans un trait, ou la recoder ici.)
    $wei = $this->hexToDec($hexVal);
    $eth = bcdiv($wei, bcpow('10','18',0), 18);
    return new JsonResponse(['address' => $address, 'balance' => $eth]);
  }

  protected function hexToDec(string $hex): string {
    $dec = '0';
    foreach (str_split($hex) as $c) {
      $dec = bcadd(bcmul($dec, '16', 0), (string) hexdec($c), 0);
    }
    return $dec;
  }
}
