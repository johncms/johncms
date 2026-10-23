#!/usr/bin/env sh
#
# Deterministic verification gate for the self-review protocol. See .agents/review/README.md
#
# The checks themselves are `composer check` (composer.json), the same list CI runs, so the gate
# and the pipeline cannot drift apart. This script only runs it inside the php-fpm container (see
# AGENTS.md, Docker Command Policy), so results do not depend on the host PHP version.
#
# What `composer check` runs, and what to do when a step fails:
#
# * cs-check — the coding standard. Fix with:
#     docker exec $(docker ps -q -f name=johncms.php-fpm) composer cs-fix
# * phpstan — level 5 with a baseline (phpstan-baseline.neon) that accepts the existing legacy
#   debt; only NEW findings fail. When a change legitimately shifts baselined code, regenerate with:
#     docker exec $(docker ps -q -f name=johncms.php-fpm) composer phpstan-baseline
#   and review the diff — a shrinking baseline is good, a growing one needs a reason.
# * test — both PHPUnit suites. The functional one renders real pages and needs the built
#   assets (`npm run build`).
# * twig-lint — templates are covered by nothing else: a syntax error in one is valid PHP-free
#   text to phpcs, phpstan and the tests, and only shows up as a broken page.
# * twig-ide-check — ide-twig.json tells the IDE which directory a @namespace template name
#   resolves to. It is generated from the same registry the loader uses, and a module added to
#   the configuration leaves it stale, with template navigation quietly broken. Regenerate with:
#     docker exec $(docker ps -q -f name=johncms.php-fpm) php system/bin/console twig:ide-config

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

# -v prints the name of every step before it runs, so a failure says which one it was.
docker exec "$CONTAINER" composer -v check

echo "==> verification gate passed"
