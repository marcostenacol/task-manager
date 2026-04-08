#!/usr/bin/env bash

set -e

BASE_DIR=""

echo "🔍 Verificando estrutura de pastas em '$BASE_DIR'..."

if [ ! -d "$BASE_DIR" ]; then
  echo "❌ Diretório '$BASE_DIR' não encontrado."
  exit 1
fi

# Cria .gitkeep em diretórios vazios
find "$BASE_DIR" -type d -empty -not -path "*/.git/*" -exec touch {}/.gitkeep \;

echo "✅ Estrutura garantida (.gitkeep criado onde necessário)"
