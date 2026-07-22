#!/usr/bin/env sh
#
# Deterministic verification gate for the self-review protocol: coding standard + tests.
# See .agents/review/README.md
#
# All PHP/Composer commands run inside the php-fpm container (see AGENTS.md,
# Docker Command Policy) so results do not depend on the host PHP version.
#
# Fix style violations with:
#   docker exec $(docker ps -q -f name=johncms9.php-fpm) composer cs-fix

set -e

CONTAINER=$(docker ps -q -f name=johncms9.php-fpm)

if [ -z "$CONTAINER" ]; then
    echo "Error: the johncms9.php-fpm container is not running. Start it with 'docker compose up -d'." >&2
    exit 1
fi

echo "==> composer cs-check"
docker exec "$CONTAINER" composer cs-check

# Psalm is intentionally not part of the gate: psalm.xml.dist still references modules that
# no longer exist, so `composer psalm` cannot run. Add it back once the config is fixed.

echo "==> composer test"
docker exec "$CONTAINER" composer test

echo "==> verification gate passed"
