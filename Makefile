# Makefile - Task Manager (Root)

.PHONY: help install up down build clean api ui

help:
	@echo "Comandos disponíveis (Root):"
	@echo "  make install  - Instala dependências do backend (Docker) e frontend (NPM)"
	@echo "  make up       - Inicia o backend (Docker) e o frontend (NPM run dev)"
	@echo "  make down     - Para os containers do backend"
	@echo "  make build    - Faz o build da API (Docker) e do frontend"
	@echo "  make clean    - Limpa o frontend e os caches da API"
	@echo ""
	@echo "Comandos delegados:"
	@echo "  make api cmd=\"comando\" - Roda um comando específico no Makefile do backend (ex: make api cmd=migrate)"
	@echo "  make ui cmd=\"comando\"  - Roda um comando específico no Makefile do frontend (ex: make ui cmd=build)"

install:
	@echo "--- Instalando Backend (API) ---"
	@$(MAKE) -C task-manager-api install
	@echo "--- Instalando Frontend (UI) ---"
	@$(MAKE) -C task-manager-ui install
	@echo "--- Instalação concluída ---"

up:
	@echo "--- Iniciando Backend (API) ---"
	@$(MAKE) -C task-manager-api up
	@echo "--- Iniciando Frontend (UI - Background) ---"
	@$(MAKE) -C task-manager-ui dev > nuxt_output.log 2>&1 &
	@echo "--- Sistema iniciado! Frontend em http://localhost:25565 ---"
	@echo "--- Logs do frontend disponíveis em: task-manager-ui/nuxt_output.log ---"

down:
	@echo "--- Parando Backend (API) ---"
	@$(MAKE) -C task-manager-api down
	@echo "--- Parando Frontend (UI) ---"
	@pkill -f "nuxt dev" || true

build:
	@echo "--- Build do Backend ---"
	@$(MAKE) -C task-manager-api build
	@echo "--- Build do Frontend ---"
	@$(MAKE) -C task-manager-ui build

clean:
	@echo "--- Limpando caches da API ---"
	@$(MAKE) -C task-manager-api clear
	@echo "--- Limpando arquivos do Frontend ---"
	@$(MAKE) -C task-manager-ui clean

api:
	@if [ -z "$(cmd)" ]; then \
		echo "Uso: make api cmd=\"comando\""; \
		echo "Comandos disponíveis na API:"; \
		$(MAKE) -C task-manager-api help; \
	else \
		$(MAKE) -C task-manager-api $(cmd); \
	fi

ui:
	@if [ -z "$(cmd)" ]; then \
		echo "Uso: make ui cmd=\"comando\""; \
		echo "Comandos disponíveis na UI:"; \
		$(MAKE) -C task-manager-ui help; \
	else \
		$(MAKE) -C task-manager-ui $(cmd); \
	fi
