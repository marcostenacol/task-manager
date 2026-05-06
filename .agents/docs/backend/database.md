# Backend Database Guide

## Objetivo

Este documento orienta a leitura de estrutura de banco no projeto.

## Ordem de Pesquisa

Quando a tarefa envolver banco, leia nesta ordem:

1. `database/migrations`
2. `database/seeders`
3. `app/Models`
4. `app/Repositories`
5. `app/Services`

## O que Confirmar nas Migrations

- nome da tabela;
- colunas e tipos;
- `nullable`;
- `default`;
- `unique`;
- foreign keys;
- comportamento de delete e update;
- indices;
- timestamps.

## O que Confirmar nos Seeders

- dados base obrigatorios;
- dependencias de execucao entre tabelas;
- valores que o sistema assume como existentes;
- relacoes criadas durante o seeding.

## O que Confirmar no Model

- nome e representacao da entidade;
- `fillable` ou protecao equivalente;
- relacionamentos;
- casts;
- scopes que impactam consultas.

## O que Confirmar no Repository

- consultas existentes;
- filtros e ordenacao implementados;
- paginacao;
- pontos centralizados de persistencia;
- chaves de cache, quando existirem.

## Regra de Implementacao

- toda mudanca estrutural deve nascer em migration;
- toda leitura de comportamento de dados deve considerar migration e seeder, nao apenas model;
- regra de negocio que dependa de banco deve ficar no `Service`, nao no `Repository`.

## 6. Funções PL/pgSQL e Segurança Delegada 🔐

Algumas lógicas críticas de segurança são delegadas ao PostgreSQL:
1. **Localização**: Funções customizadas estão em migrations (ex: `admin.process_login`, `admin.generate_refresh_token`).
2. **Validação Atômica**: O banco é responsável por detectar reuso de tokens e invalidar sessões em uma única transação.
3. **Intervalo de Invalidação**: Ao atualizar timestamps de expiração, use `interval '1 minute'` no passado para garantir que a consulta `expires_at < NOW()` funcione consistentemente através de diferentes camadas.
4. **Hasing de Senha**: Utilizamos o algoritmo nativo **Blowfish (bf)** via `crypt()` e `gen_salt('bf')` na função `admin.generate_password_hash`. A validação é feita via `crypt(input, stored_hash)` diretamente no SQL.

Ao analisar bugs de autenticação, sempre verifique as definições dessas funções nas migrations.
