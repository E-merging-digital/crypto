<?php

namespace Drupal\eth_transactions\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Fournit la liste des transactions pour une adresse Ethereum.
 */
final class EthTransactionsController {

  /**
   * Liste les transactions pour une adresse.
   *
   * Note : l'API JSON-RPC d'Ethereum ne fournit pas de point de terminaison
   * pour lister directement toutes les transactions d'une adresse. Ceci est un
   * placeholder pour une future indexation ou service externe.
   *
   * @param string $address
   *   Adresse Ethereum.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Réponse JSON contenant l'adresse
   *   et les transactions (actuellement vides).
   */
  public function list(string $address): JsonResponse {
    // @todo Fix problem "remplacer par une vraie implémentation" here
    //   Explorer les blocs ou intégrer un index externe.
    $transactions = [];

    return new JsonResponse([
      'address' => $address,
      'transactions' => $transactions,
    ]);
  }

}
