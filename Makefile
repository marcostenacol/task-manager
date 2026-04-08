# Makefile melhorado para TASK MANAGER
# Requer: GNU make, docker compose v2
# Carrega variáveis do .env (mantive sua linha original)
-include .env

# Configs básicas
DOCKER_COMPOSE_FILE ?= docker-compose.yml
DC := docker compose -f $(DOCKER_COMPOSE_FILE)

# Prefix/project name
PREFIX ?= task-manager
COMPOSE_PROJECT_NAME ?= $(PREFIX)
export COMPOSE_PROJECT_NAME

# Perfil (dev, local, prod...)
PROFILE ?= local

# Fallbacks para nomes de serviços que o Makefile utiliza diretamente
DOCKER_SERVICE_PHP_FPM := task-manager-php-fpm
DOCKER_SERVICE_NGINX   := task-manager-nginx
DOCKER_SERVICE_PGSQL   := pgsql

# Helper para adicionar --profile quando informado
ifeq ($(strip $(PROFILE)),)
	PROFILE_FLAG :=
else
	PROFILE_FLAG := --profile $(PROFILE)
endif

# Small helper to show which compose file / env is used
define define-environment
	@echo "Using compose file: $(DOCKER_COMPOSE_FILE)"
	@echo "COMPOSE_PROJECT_NAME: '$(COMPOSE_PROJECT_NAME)'"
	@echo "PROFILE: '$(PROFILE)'"
endef

# Make help auto-generated from target comments
HELP_FUN = awk 'BEGIN {FS = ":.*##"; printf "\nUsage: make <target> [VARIABLE=val]\n\nTargets:\n"} /^[a-zA-Z0-9_.-]+:.*?##/ { printf "  %-18s %s\n", $$1, $$2 } END {print ""}' $(MAKEFILE_LIST)

.PHONY: help define-environment build up down restart logs logs-all shell-php shell-nginx \
	composer-install composer-update composer-dump composer-require node-install \
	delete-node_modules delete-vendor migrate migrate-fresh refresh key-generate \
	recreate-database recreate-testing-database restore in test certbot-run certbot-renew \
	passport-install install deploy cache wait-healthy

help: ## Mostrar este help
	@$(HELP_FUN)

define-environment: ## Mostra ambiente usado
	@$(define-environment)

# Builds
build: define-environment ## Build dos serviços listados no .env (ou todos se vazio)
	@echo "Building services: $(if $(SERVICE_NAMES),$(SERVICE_NAMES),<all services>)"
	@$(DC) $(PROFILE_FLAG) build --no-cache

up: define-environment ## Sobe containers (use PROFILE=dev/local/prod para profiles)
	@echo "Starting compose $(PROFILE_FLAG)"
	@$(DC) $(PROFILE_FLAG) up -d
	@$(MAKE) clear

down: ## Encerra todos os containers listados no docker-compose
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} down
	@#docker compose -f ${DOCKER_COMPOSE_FILE} ${DOCKER_SERVICE_PHP_FPM}  down -v

shell-php:
	@echo "Acessando shell do container app..."
	docker compose exec ${DOCKER_SERVICE_PHP_FPM} bash

shell-nginx:
	@echo "Acessando shell do container app..."
	docker compose exec ${DOCKER_SERVICE_NGINX} bash

logs:
	@echo "Exibindo logs de todos os containers..."
	docker compose logs -f

logs-php:
	@echo "Logs do container postgres:"
	docker compose logs -f ${DOCKER_SERVICE_PHP_FPM}

logs-nginx:
	@echo "Logs do container postgres:"
	docker compose logs -f ${DOCKER_SERVICE_NGINX}

key-generate: ## Executa o composer install
	make define-environment
	docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan key:generate

refresh:
	make composer-dump
	make clear

lib ?=
composer-require: ## Instala uma nova lib utilizando o composer
ifdef lib
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} composer require $(lib)
else
	echo "Informe o nome da lib a ser instalada"
endif

composer-install: ## Executa o composer install
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} composer install --no-interaction

composer-update: ## Executa o composer update
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} composer update --no-interaction

composer-dump: ## Executa o composer dump
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} composer dump --no-interaction

node-install: ## Executa bower e yarn install
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} npm install --non-interactive

delete-node_modules: ## Remove a pasta node_modules
	make define-environment
	docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} rm -Rf node_modules

delete-vendor: ## Remove a pasta node_modules
	make define-environment
	docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} rm -Rf vendor

env ?=
node-run: ## Roda o comando npm run
ifdef env
	echo "Rodando yarn run no modo $(env)"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} npm run $(env)
else
	echo "Rodando yarn run no modo prod"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} npm run prod
endif

migrate: ## Executa o comando php artisan migrate
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan migrate --force

migrate-fresh: ## Executa o comando php artisan migrate --force
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan migrate:fresh

clear: ## Executa o comando php artisan migrate --force
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan optimize:clear

db-seed: ## Executa o comando php artisan db:seed
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan db:seed

## Exemplo: make assign-password password=12345678
assign-password:
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan password:assign --password=$(password)

## Exemplo: make recreate-database
recreate-database: ## Restaura um backup do banco de dados
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "postgres" -c "SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity WHERE pg_stat_activity.datname = '${DB_DATABASE}';"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "postgres" -c "DROP DATABASE IF EXISTS ${DB_DATABASE};"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "postgres" -c "CREATE DATABASE ${DB_DATABASE};"

## Exemplo: make recreate-testing-database
recreate-testing-database: ## Restaura um backup do banco de dados
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -c "DROP DATABASE IF EXISTS ${DB_DATABASE}_testing;"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -c "CREATE DATABASE ${DB_DATABASE}_testing;"

## Exemplo: make restore filename=pds.sql
#restore: ## Restaura um backup do banco de dados
#	make define-environment
#	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "CREATE EXTENSION IF NOT EXISTS postgis;"
#	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "${DB_DATABASE}" -f "/home/backups/$(filename)"

## Exemplo: make restore filename=pds.sql
restore: ## Restaura um backup do banco de dados
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c "CREATE EXTENSION IF NOT EXISTS postgis;"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql dropdb -U "${DB_USERNAME}" "${DB_DATABASE}" || true
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql createdb -U "${DB_USERNAME}" "${DB_DATABASE}"
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec -T pgsql pg_restore -U "${DB_USERNAME}" -d "${DB_DATABASE}" "/home/backups/$(filename)"
    #docker compose -f ${DOCKER_COMPOSE_FILE} exec pgsql psql -U "${DB_USERNAME}" -d "${DB_DATABASE}" -f "/home/backups/$(filename)"

in: ## Lista todos os containers levantados para o usuário escolher um e entrar
	@bash .docker/scripts/in.sh

group ?=
filter ?=
test: ## Roda o phpunit
ifdef group
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} vendor/bin/phpunit --group=$(group)
else ifdef filter
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} vendor/bin/phpunit --filter=$(filter)
else
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} vendor/bin/phpunit
endif

certbot-run: ## Solicita a primeira instalação do certificado digital utilizando certbot
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec nginx certbot run --nginx --agree-tos --no-eff-email -m ${CERTBOT_EMAIL_ADDRESS} -d ${CERTBOT_DOMAIN}

certbot-renew: ## Solicita a renovação do certificado digital utilizando certbot
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec nginx certbot renew

passport-install: ## Faz install do passport
	make define-environment
	docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan passport:install

restart: ## Restarta todos os containers em execução
	make define-environment
	make down
	make up
	make refresh

install: ## Instala a aplicação executando todos os passos necessários
	echo "TASK MANAGER INSTALL"
	make build
	make up
	make delete-node_modules
	make delete-vendor
	make composer-install
	make key-generate
	echo "Install finished!"

## Configuração de deploy
deploy:
	@echo "TASK MANAGER DEPLOY!"
	make define-environment
	make clear
	make down
	make up
	make refresh
	make migrate
	make cache
	@echo "DEPLOY FINALIZADO!"

## Realiza a criação de cache
cache:
	make define-environment
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan config:cache
	@docker compose -f ${DOCKER_COMPOSE_FILE} exec ${DOCKER_SERVICE_PHP_FPM} php artisan route:cache
