#!/usr/bin/env sh
#
# Deterministic verification gate for the self-review protocol: coding standard + tests.
# See .agents/review/README.md
#
# All PHP/Composer commands run inside the php-fpm container (see AGENTS.md,
# Docker Command Policy) so results do not depend on the host PHP version.
#
# Fix style violations with:
#   docker exec $(docker ps -q -f name=johncms.php-fpm) composer cs-fix

set -e

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)

# The container name is derived from the Compose project name, so read it from .env instead
# of hard-coding it: a developer may run the stack under any project name.
PROJECT_NAME=${COMPOSE_PROJECT_NAME:-}
if [ -z "$PROJECT_NAME" ] && [ -f "$ROOT/.env" ]; then
    PROJECT_NAME=$(sed -n 's/^COMPOSE_PROJECT_NAME=//p' "$ROOT/.env" | head -n 1 | tr -d '"'"'"' \r')
fi
PROJECT_NAME=${PROJECT_NAME:-johncms}

CONTAINER=$(docker ps -q -f "name=^${PROJECT_NAME}\.php-fpm$")

if [ -z "$CONTAINER" ]; then
    echo "Error: the ${PROJECT_NAME}.php-fpm container is not running. Start it with 'docker compose up -d'." >&2
    exit 1
fi

echo "==> composer cs-check"
docker exec "$CONTAINER" composer cs-check

# Static analysis: PHPStan level 5 with a baseline (phpstan-baseline.neon) that accepts the
# existing legacy debt. Only NEW findings fail the gate. When a change legitimately shifts
# baselined code, regenerate with:
#   docker exec $(docker ps -q -f name=johncms.php-fpm) composer phpstan-baseline
# and review the diff — a shrinking baseline is good, a growing one needs a reason.
echo "==> composer phpstan"
docker exec "$CONTAINER" composer phpstan

echo "==> composer test"
docker exec "$CONTAINER" composer test

# Templates are covered by nothing else in the gate: a syntax error in one is valid PHP-free
# text to phpcs, phpstan and the tests, and only shows up as a broken page.
echo "==> twig:lint"
docker exec "$CONTAINER" php system/bin/console twig:lint

# ide-twig.json tells the IDE which directory a @namespace template name resolves to. It is
# generated from the same registry the loader uses, and a module added to the configuration
# leaves it stale — with template navigation quietly broken and nothing else to report it.
# Regenerate with:
#   docker exec $(docker ps -q -f name=johncms.php-fpm) php system/bin/console twig:ide-config
echo "==> twig:ide-config --check"
docker exec "$CONTAINER" php system/bin/console twig:ide-config --check

echo "==> verification gate passed"
