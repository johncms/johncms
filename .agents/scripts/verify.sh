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

# Static analysis: PHPStan level 5 with a baseline (phpstan-baseline.neon) that accepts the
# existing legacy debt. Only NEW findings fail the gate. When a change legitimately shifts
# baselined code, regenerate with:
#   docker exec $(docker ps -q -f name=johncms9.php-fpm) composer phpstan-baseline
# and review the diff — a shrinking baseline is good, a growing one needs a reason.
echo "==> composer phpstan"
docker exec "$CONTAINER" composer phpstan

echo "==> composer test"
docker exec "$CONTAINER" composer test

echo "==> verification gate passed"
