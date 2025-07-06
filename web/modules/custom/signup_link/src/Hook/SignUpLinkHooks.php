<?php

/**
 * @file
 * Provides hook implementations for the signup_link module.
 *
 * @package Drupal\signup_link
 */

declare(strict_types=1);

namespace Drupal\signup_link\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;

/**
 * Implements hooks to rename the login block link and conditionally hide the
 * signup menu link for authenticated users.
 *
 * @ingroup signup_link
 */
class SignUpLinkHooks {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new SignUpLinkHooks instance.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Alters the user login block to rename the "create account" link.
   *
   * Implements hook_preprocess_block().
   *
   * @param array $variables
   *   Renderable array of block variables.
   */
  #[Hook('preprocess_block')]
  public function preprocessBlock(array &$variables): void {
    if ($variables['plugin_id'] !== 'system_user_login_block' || !$this->currentUser->isAnonymous()) {
      return;
    }
    if (isset($variables['content']['user_links']['#items']['create_account'])) {
      $variables['content']['user_links']['#items']['create_account']['#title'] = t('Sign up');
    }
  }

  /**
   * Removes the "Sign up" menu link for authenticated users.
   *
   * Implements hook_menu_tree_alter().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The menu tree elements.
   * @param string $menu_name
   *   The machine name of the menu being rendered.
   */
  #[Hook('menu_tree_alter')]
  public function alterAccountMenu(array &$tree, string $menu_name): void {
    if ($menu_name !== 'account' || $this->currentUser->isAnonymous()) {
      return;
    }
    foreach ($tree as $key => $element) {
      if ($element->link->getRouteName() === 'user.register') {
        unset($tree[$key]);
      }
    }
  }
}
