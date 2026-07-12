-include .env

USER_ID ?= $(shell id -u)

restart: stop up

build:
	@echo "Building containers"
	@USER_ID=$(USER_ID) docker compose --env-file .env build

up:
	@echo "Starting containers"
	@USER_ID=$(USER_ID) docker compose --env-file .env up -d --remove-orphans

rebuild:
	@echo "Rebuilding containers"
	@USER_ID=$(USER_ID) docker compose up -d --build

stop:
	@echo "Stopping containers"
	@docker compose stop

shell:
	@docker exec -it $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm) /bin/bash

composer-install:
	@echo "Running composer install"
	@docker exec -it $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm) composer install

composer-update:
	@echo "Running composer install"
	@docker exec -it $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.php-fpm) composer update

restore-db:
	@echo "Restore database dump from file ${DB_DATABASE}.sql"
	@docker exec -i $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.mariadb) mariadb -u${DB_USERNAME} -p"${DB_PASSWORD}" ${DB_DATABASE} < ${DB_DATABASE}.sql

backup-db:
	@echo "Backup database to ${DB_DATABASE}_1.sql"
	@docker exec $$(docker ps -q -f name=${COMPOSE_PROJECT_NAME}.mariadb) mariadb-dump -u${DB_USERNAME} -p"${DB_PASSWORD}" ${DB_DATABASE} > ${DB_DATABASE}_1.sql

prepare-dev:
	@cp -R .docker/certbot/conf/live/test-app.loc .docker/certbot/conf/live/${APP_HOST}
	@cp .docker/docker-compose.dev.yml ./docker-compose.override.yml

CROWDIN_BRANCH ?= 9.x

crowdin-download:
	@echo "Downloading translations from Crowdin (branch ${CROWDIN_BRANCH})"
	@crowdin download --branch ${CROWDIN_BRANCH}

crowdin-upload:
	@echo "Uploading sources to Crowdin (branch ${CROWDIN_BRANCH})"
	@crowdin upload sources --branch ${CROWDIN_BRANCH}

crowdin-upload-translations:
	@echo "Uploading translations to Crowdin (branch ${CROWDIN_BRANCH})"
	@crowdin upload translations --branch ${CROWDIN_BRANCH}

crowdin-upload-all: crowdin-upload crowdin-upload-translations
