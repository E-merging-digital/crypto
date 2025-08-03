#!/usr/bin/env bash
set -euo pipefail

echo "📦 Lancement des tests PHPUnit"

# Définir des valeurs par défaut (modifiable via l'environnement)
: "${SIMPLETEST_BASE_URL:=http://127.0.0.1:8080}"
: "${SIMPLETEST_DB:=sqlite://localhost/web/sites/default/files/.ht.sqlite}"
export SIMPLETEST_BASE_URL
export SIMPLETEST_DB

# Choisir le fichier de config PHPUnit : priorité à la racine, puis dans web/
if [[ -f phpunit.xml.dist ]]; then
  config="phpunit.xml.dist"
elif [[ -f phpunit.xml ]]; then
  config="phpunit.xml"
elif [[ -f web/phpunit.xml.dist ]]; then
  config="web/phpunit.xml.dist"
elif [[ -f web/phpunit.xml ]]; then
  config="web/phpunit.xml"
else
  echo "❌ Aucun fichier de configuration PHPUnit trouvé. Copiez web/core/phpunit.xml.dist à la racine et adaptez-le." >&2
  exit 1
fi

# Exécuter PHPUnit
vendor/bin/phpunit --configuration "$config" "$@"
