#!/usr/bin/env bash
# Usage: docker/run.sh <2.4.6|2.4.8|2.4.9> [install|test|down|shell]
set -euo pipefail
cd "$(dirname "$0")"
case "${1:-}" in
  2.4.6) export MAGENTO_VERSION=2.4.6-p15 PHP_TAG=8.2-fpm-10 MARIADB_TAG=10.6 HTTP_PORT=8246 ;;
  2.4.8) export MAGENTO_VERSION=2.4.8-p5  PHP_TAG=8.3-fpm-8  MARIADB_TAG=11.4 HTTP_PORT=8248 ;;
  2.4.9) export MAGENTO_VERSION=2.4.9     PHP_TAG=8.4-fpm-3  MARIADB_TAG=11.4 HTTP_PORT=8249 ;;
  *) echo "usage: run.sh <2.4.6|2.4.8|2.4.9> [install|test|down|shell]" >&2; exit 2 ;;
esac
export COMPOSE_PROJECT_NAME="cube-${1//./}"
dc() { docker compose --profile web "$@"; }
case "${2:-test}" in
  install) dc up -d db opensearch php && dc exec -T php /scripts/install.sh && dc up -d web ;;
  test)    dc up -d && dc exec -T php /scripts/test.sh ;;
  shell)   dc exec php bash ;;
  down)    dc down -v ;;
esac
