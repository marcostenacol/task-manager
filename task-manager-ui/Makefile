# TaskMaster UI - Makefile

.PHONY: install dev build generate clean help

help:
	@echo "Comandos disponíveis:"
	@echo "  make install  - Instala as dependências do projeto"
	@echo "  make dev      - Inicia o servidor de desenvolvimento"
	@echo "  make build    - Gera o build de produção"
	@echo "  make generate - Gera o site estático (SSG)"
	@echo "  make clean    - Remove as pastas de cache e node_modules"

install:
	npm install

dev:
	npm run dev

build:
	npm run build

generate:
	npm run generate

clean:
	rm -rf .nuxt .output node_modules
