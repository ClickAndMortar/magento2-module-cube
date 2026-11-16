#!/usr/bin/env bash
# Installe Magento $MAGENTO_VERSION (vide, sans données d'exemple) puis le module depuis /module.
set -euo pipefail
cd /var/www/html
if [ ! -f composer.json ]; then
  composer create-project --no-interaction --no-progress --no-install \
    --repository-url=https://mirror.mage-os.org/ \
    "magento/project-community-edition=${MAGENTO_VERSION}" .
  case "$MAGENTO_VERSION" in
    # 2.4.6 impose league/flysystem 2.x, visé par un avis de sécurité que Composer >= 2.9 bloque.
    # Banc de test uniquement : on autorise l'installation.
    2.4.6*) composer config audit.block-insecure false ;;
  esac
fi
if [ ! -d vendor/magento/framework ]; then
  composer install --no-interaction --no-progress
fi
if [ ! -f app/etc/env.php ]; then
  bin/magento setup:install --no-interaction \
    --base-url="http://localhost:${HTTP_PORT}/" \
    --db-host=db --db-name=magento --db-user=magento --db-password=magento \
    --admin-firstname=Admin --admin-lastname=Test --admin-email=admin@example.com \
    --admin-user=admin --admin-password="Adm1n-$(head -c 12 /dev/urandom | od -An -tx1 | tr -d ' \n')" \
    --language=en_US --currency=EUR --timezone=Europe/Paris --use-rewrites=1 \
    --search-engine=opensearch --opensearch-host=opensearch --opensearch-port=9200 \
    --opensearch-index-prefix=m2 --backend-frontname=admin --cleanup-database
fi
if ! composer show clickandmortar/module-cube >/dev/null 2>&1; then
  composer config repositories.cube '{"type": "path", "url": "/module", "options": {"symlink": true}}'
  composer require --no-interaction --no-progress clickandmortar/module-cube:1.0.0
fi
bin/magento module:enable ClickAndMortar_Cube
bin/magento setup:upgrade --keep-generated
bin/magento config:set oauth/consumer/enable_integration_as_bearer 1
bin/magento cache:flush
bin/magento --version
