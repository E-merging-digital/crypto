Workflow Dev & Qualité — Drupal 11 / PHP 8.3
Objectifs

    Empêcher l’introduction de régressions sur main et branches de release.

    Laisser la possibilité de pousser un WIP (handover, sauvegarde avant congés) sans bloquer l’équipe, tout en garantissant qu’aucun merge n’ait lieu si la CI est rouge (règles de protection + checks requis).
    GitHub Docs+2GitHub Docs+2

Flux local
Pré-requis

# Installer les dépendances dev
composer install

# GrumPHP (pré-commit) s’installe via Composer et enregistre les hooks
# (si besoin) : composer require --dev phpro/grumphp

    GrumPHP enregistre des hooks Git et bloque le commit si une tâche échoue.
    GitHub
    The Man in the Arena

Au commit (rapide)

    Le hook pre-commit (GrumPHP) exécute : lint PHP, PHPCS (Drupal), PHPStan, unit tests « Unit », Twig/JSON/XML lint.

    En cas d’échec, le commit est refusé par GrumPHP.

Au push (exhaustif)

    Le hook pre-push exécute PHPUnit complet (via GrumPHP testsuite pre-push) puis Behat (profil local) si présent.

    En cas d’échec, le push est refusé (les hooks pre-push peuvent empêcher un git push).
    git-scm.com+1

Bypass encadré (cas exceptionnels)
Quand ?

    Handover / backup (vendredi soir avant congés), investigations, refactoring en cours mais besoin de partager.

Comment ?

Trois options documentées (choisir une seule) :

    Tag de commit : ajoutez à votre dernier commit l’un de ces marqueurs :

[bypass-pre-push]

    Variable d’environnement (session courante) :

BYPASS_PRE_PUSH=1 git push

    Fichier drapeau (local) :

touch .git/ALLOW_BROKEN_PUSH
git push
rm .git/ALLOW_BROKEN_PUSH

Le script pre-push détecte l’un de ces signaux et autorise le push WIP.
Alternative « brute » (à éviter) : --no-verify qui bypasse les hooks au commit/push. Utiliser avec parcimonie et en connaissance de cause.
git-scm.com

    Remarque : on peut aussi skipper la CI GitHub Actions sur push/pull_request en ajoutant au message de commit :
    [skip ci], [ci skip], [no ci], [skip actions] ou [actions skip]. Ces mots-clés n’affectent pas d’autres événements (pull_request_target, etc.).
    The GitHub Blog
    GitHub Docs

Protection des branches sur GitHub (sécurité)

Activez, dans Settings → Branches → Add rule :

    main (et branches release/*) protégées.

    Required status checks : vos jobs Actions doivent être verts pour merger.

    Optionnel : reviews obligatoires, interdiction des force-push, etc.
    GitHub Docs+1

Ainsi, même si un dev pousse un WIP, aucun merge n’est possible tant que la CI n’est pas passée.
Mise en place des hooks locaux
1) GrumPHP (pré-commit déjà géré par Composer)

Rien à faire si le projet inclut phpro/grumphp. Sinon :

composer require --dev phpro/grumphp

GrumPHP installe le hook pre-commit et exécute vos tâches.
GitHub
2) Hook pre-push (batterie lourde + bypass encadré)

Créer le dossier et activer core.hooksPath :

mkdir -p .githooks
git config core.hooksPath .githooks

Créez .githooks/pre-push (exécutable) avec :

#!/usr/bin/env bash
set -euo pipefail

# --- Bypass encadré -----------------------------------------
LAST_MSG="$(git log -1 --pretty=%B || echo "")"
if [[ "${BYPASS_PRE_PUSH:-0}" = "1" ]] \
|| grep -qiE '\[(bypass-pre-push|emergency-push)\]' <<<"$LAST_MSG" \
|| [[ -f .git/ALLOW_BROKEN_PUSH ]]; then
echo ">>> BYPASS PRE-PUSH ACTIVÉ : usage exceptionnel (handover/backup)."
echo ">>> Rappel : la protection de branche bloquera tout merge si la CI échoue."
exit 0
fi
# ------------------------------------------------------------

# Exécuter dans ddev si dispo (sinon local)
if command -v ddev >/dev/null 2>&1; then
EXEC='ddev exec --'
else
EXEC=''
fi

echo ">>> Running GrumPHP testsuite: pre-push"
$EXEC ./vendor/bin/grumphp run --testsuite pre-push

# Behat local (profil 'local') si présent
if [ -x ./vendor/bin/behat ] && [ -f ./behat.yml ]; then
echo ">>> Running Behat (profile: local)"
$EXEC ./vendor/bin/behat -c behat.yml -p local --colors --strict
else
echo ">>> Behat not found or behat.yml missing: skipping."
fi

    Réf. hooks Git et pre-push :
    git-scm.com
    atlassian.com

Commandes utiles

    Lancer la suite pré-commit manuellement :

vendor/bin/grumphp run --testsuite pre-commit

    Lancer la suite pré-push manuellement :

vendor/bin/grumphp run --testsuite pre-push

    Bypass documenté pour un push (rare, handover) :

git commit -m "WIP: ... [bypass-pre-push]"
git push

    Bypass brut (évite les hooks — déconseillé) :

git commit --no-verify -m "..."
git push --no-verify

    --no-verify désactive pre-commit / commit-msg (et côté push, pre-push). À réserver aux urgences, la protection GitHub empêchant de toute façon le merge sans CI verte.
    git-scm.com

    Skipper la CI GitHub Actions (si approprié) :

[skip ci]   # ou [ci skip] / [no ci] / [skip actions] / [actions skip]

    Ne s’applique qu’aux événements push / pull_request.
    The GitHub Blog
    GitHub Docs

FAQ

Pourquoi autoriser un push WIP ?
Pour permettre la reprise de travaux (absence imprévue, relève), ou sauvegarder une avancée significative. Le merge reste bloqué par les required status checks.
GitHub Docs

Peut-on empêcher --no-verify ?
Non, on ne peut pas le désactiver globalement. On encadre son usage par la politique ci-dessus et les branch protections.
git-scm.com
Résumé

    Strict au commit (GrumPHP rapide).

    Exhaustif au push (PHPUnit complet + Behat).

    Bypass encadré possible pour WIP/backup.

    Aucun merge sur main/release sans CI verte (GitHub branch protection + checks requis).
