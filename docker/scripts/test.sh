#!/usr/bin/env bash
# Tests unitaires (cadre de test unitaire de Magento) puis tests d'API contre l'instance locale.
set -euo pipefail
cd /var/www/html
echo "== Magento $(bin/magento --version | awk '{print $NF}') / PHP $(php -r 'echo PHP_VERSION;')"
echo "== Tests unitaires"
(cd dev/tests/unit && ../../../vendor/bin/phpunit -c phpunit.xml.dist ../../../vendor/clickandmortar/module-cube/Test/Unit)
echo "== Tests d'API"
CUBE_TOKEN="$(bin/magento cm-cube:integration:create --replace --token-only --name='Cube tests')" \
CUBE_BASE_URL="http://web:8000" \
  vendor/bin/phpunit --bootstrap vendor/autoload.php --colors=never vendor/clickandmortar/module-cube/Test/Api
