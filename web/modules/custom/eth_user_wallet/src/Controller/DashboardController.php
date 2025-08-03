<?php

namespace Drupal\eth_user_wallet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dashboard controller for eth_user_wallet.
 */
final class DashboardController extends ControllerBase {

  /**
   * Temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  private PrivateTempStoreFactory $tempStoreFactory;

  /**
   * Constructs the controller.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp store factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * Example dashboard.
   */
  public function dashboard(): Response {
    $store = $this->tempStoreFactory->get('eth_user_wallet');
    $value = $store->get('some_key') ?? 'default';

    return new Response(sprintf('Dashboard value: %s', $value));
  }

}
