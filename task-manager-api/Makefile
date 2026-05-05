# Makefile para TASK MANAGER
# Requer: GNU make, docker compose v2

-include .env

# Configurações Básicas
DOCKER_COMPOSE_FILE ?= docker-compose.yml
PREFIX ?= task-manager
COMPOSE_PROJECT_NAME ?= $(PREFIX)
export COMPOSE_PROJECT_NAME

# Perfil do Docker Compose (ex: local, dev, prod)
PROFILE ?= local
ifneq ($(strip $(PROFILE)),)
	PROFILE_FLAG := --profile $(PROFILE)
endif

DC := docker compose -f $(DOCKER_COMPOSE_FILE) $(PROFILE_FLAG)
DOCKER_SERVICE_PHP_FPM := task-manager-php-fpm
DOCKER_SERVICE_NGINX   := task-manager-nginx
DOCKER_SERVICE_PGSQL   := pgsql

.PHONY: help build up down restart logs shell-php shell-nginx php art composer test migrate install clear in restore passport-install

# --- AJUDA ---
help: ## Mostra os comandos disponíveis
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

# --- DOCKER (CONTROLE) ---
build: ## Build das imagens do Docker
	@$(DC) build --no-cache

up: ## Sobe os containers em background
	@$(DC) up -d
	@$(MAKE) clear

down: ## Para e remove os containers
	@$(DC) down

restart: ## Reinicia os containers e limpa caches
	@$(MAKE) down
	@$(MAKE) up
	@$(MAKE) clear

logs: ## Exibe logs de todos os containers
	@$(DC) logs -f

logs-php: ## Logs apenas do container PHP
	@$(DC) logs -f $(DOCKER_SERVICE_PHP_FPM)

logs-nginx: ## Logs apenas do container Nginx
	@$(DC) logs -f $(DOCKER_SERVICE_NGINX)

# --- ACESSO E UTILITÁRIOS ---
shell-php: ## Acessa o terminal bash do container PHP
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) bash

shell-nginx: ## Acessa o terminal bash do container Nginx
	@$(DC) exec $(DOCKER_SERVICE_NGINX) bash

in: ## Script interativo para escolher e entrar em um container
	@bash .docker/scripts/in.sh

# Atalhos de Comando (Pass-through)
# Uso: make art c="migrate:status" | make composer c="update" | make npm c="install"
art: ## Roda comandos do Artisan: make art c="comando"
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan $(c)

composer: ## Roda comandos do Composer: make composer c="comando"
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) composer $(c)

npm: ## Roda comandos do NPM: make npm c="comando"
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) npm $(c)

php: ## Roda comandos PHP diretos: make php c="-v"
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php $(c)

# --- LARAVEL ---
migrate: ## Executa as migrations pendentes
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan migrate

migrate-fresh: ## Reseta o banco e roda todas as migrations
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan migrate:fresh

clear: ## Limpa todos os caches do Laravel (optimize:clear)
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan optimize:clear

db-seed: ## Alimenta o banco com dados (Seeders)
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan db:seed

key-generate: ## Gera a chave da aplicação (APP_KEY)
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan key:generate

passport-install: ## Instalação do Laravel Passport
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan passport:install

# --- TESTES ---
test: ## Executa os testes via Pest (use f=NomeDoTeste para filtrar)
ifdef f
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) vendor/bin/pest --filter=$(f)
else
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) vendor/bin/pest
endif

# --- BANCO DE DADOS (POSTGRES) ---
db-recreate: ## Dropa e cria o banco de dados principal
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) psql -U "$(DB_USERNAME)" -d "postgres" -c "DROP DATABASE IF EXISTS \"$(DB_DATABASE)\";"
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) psql -U "$(DB_USERNAME)" -d "postgres" -c "CREATE DATABASE \"$(DB_DATABASE)\";"

db-testing: ## Dropa e cria o banco de dados de testes
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) psql -U "$(DB_USERNAME)" -d "postgres" -c "DROP DATABASE IF EXISTS \"$(DB_DATABASE)_testing\";"
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) psql -U "$(DB_USERNAME)" -d "postgres" -c "CREATE DATABASE \"$(DB_DATABASE)_testing\";"

restore: ## Restaura um backup (use filename=arquivo.sql)
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) psql -U "$(DB_USERNAME)" -d "$(DB_DATABASE)" -c "CREATE EXTENSION IF NOT EXISTS postgis;"
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) dropdb -U "$(DB_USERNAME)" "$(DB_DATABASE)" || true
	@$(DC) exec $(DOCKER_SERVICE_PGSQL) createdb -U "$(DB_USERNAME)" "$(DB_DATABASE)"
	@$(DC) exec -T $(DOCKER_SERVICE_PGSQL) pg_restore -U "$(DB_USERNAME)" -d "$(DB_DATABASE)" "/home/backups/$(filename)"

# --- INSTALAÇÃO ---
install: ## Instalação completa do projeto do zero (Dev)
	@echo "--- Iniciando Instalação TASK MANAGER ---"
	@$(MAKE) build
	@$(MAKE) up
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) composer install
	@$(MAKE) key-generate
	@$(MAKE) migrate-fresh
	@echo "--- Instalação Finalizada! ---"

# --- PRODUÇÃO / DEPLOY ---
cache-optimize: ## Otimiza a performance: cache de config, rotas e views
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan config:cache
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan route:cache
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) php artisan view:cache

deploy: ## Sequência de deploy seguro para servidor de produção
	@echo "--- Iniciando Deploy ---"
	@$(MAKE) up
	@$(DC) exec $(DOCKER_SERVICE_PHP_FPM) composer install --no-dev --optimize-autoloader
	@$(MAKE) migrate
	@$(MAKE) cache-optimize
	@$(MAKE) clear
	@echo "--- Deploy Finalizado com Sucesso! ---"

certbot-run: ## Primeira instalação do SSL Certbot (Uso: m=email@ex.com d=dominio.com)
	@$(DC) exec $(DOCKER_SERVICE_NGINX) certbot run --nginx --agree-tos --no-eff-email -m $(m) -d $(d)

certbot-renew: ## Renova os certificados SSL do Certbot
	@$(DC) exec $(DOCKER_SERVICE_NGINX) certbot renew
