<?php

namespace Drupal\signup_link\Hook;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Service pour altérer l'interface de connexion et le menu compte.
 */
final class SignUpLinkHooks {
  use StringTranslationTrait;

  /**
   * Current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

  /**
   * Constructs the helper.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   String translation service.
   */
  public function __construct(AccountProxyInterface $current_user, $translator) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $translator;
  }

  /**
   * Modifie le lien de création de compte dans le bloc de connexion.
   *
   * @param array &$variables
   *   Variables du preprocess.
   */
  public function preprocessBlock(array &$variables): void {
    if ($variables['plugin_id'] !== 'system_user_login_block' || !$this->currentUser->isAnonymous()) {
      return;
    }
    if (isset($variables['content']['user_links']['#items']['create_account'])) {
      $variables['content']['user_links']['#items']['create_account']['#title'] = $this->t('Sign up');
    }
  }

  /**
   * Supprime le lien "S’inscrire" pour les utilisateurs authentifiés.
   *
   * @param array &$tree
   *   Arbre du menu.
   * @param string $menu_name
   *   Nom du menu.
   */
  public function alterAccountMenu(array &$tree, string $menu_name): void {
    if ($menu_name !== 'account' || $this->currentUser->isAnonymous()) {
      return;
    }
    foreach ($tree as $key => $element) {
      if (isset($element->link) && $element->link->getRouteName() === 'user.register') {
        unset($tree[$key]);
      }
    }
  }

}
