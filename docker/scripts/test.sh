#!/usr/bin/env bash
# Unit tests (Magento's unit test framework), then the API test against the local instance.
set -euo pipefail
cd /var/www/html
echo "== Magento $(bin/magento --version | awk '{print $NF}') / PHP $(php -r 'echo PHP_VERSION;')"
echo "== Unit tests"
(cd dev/tests/unit && ../../../vendor/bin/phpunit -c phpunit.xml.dist ../../../vendor/clickandmortar/magento2-module-cube/Test/Unit)
echo "== API test"
CUBE_TOKEN="$(bin/magento cm-cube:integration:create --replace --token-only --name='Cube tests')" \
CUBE_BASE_URL="http://web:8000" \
  vendor/bin/phpunit --bootstrap vendor/autoload.php --colors=never vendor/clickandmortar/magento2-module-cube/Test/Api
