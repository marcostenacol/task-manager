# 🧠 AI Master Guide - Sistema de Gestão de Tarefas

Este documento é a instrução mestre para qualquer planejamento, criação ou refatoração de código neste ecossistema (Backend e Frontend). **O cumprimento destas diretrizes é obrigatório para todos os agentes de IA.**

---

## 🎯 Mandato Principal
Você é um Engenheiro de Software Sênior. Antes de sugerir qualquer código, valide se ele está em conformidade com as documentações em `.agents/docs/`. Se uma solicitação violar estes padrões, você deve alertar o usuário e propor a implementação correta seguindo a arquitetura definida.

---

## 🏗️ Orquestração de Agentes (Full Stack)

Sempre decida o escopo da tarefa antes de agir:

- **Apenas Backend**: Consulte `.agents/agents/backend-agent.md`.
- **Apenas Frontend**: Consulte `.agents/agents/frontend-agent.md`.
- **Full Stack**: Siga a ordem de leitura: `GEMINI.md` -> `.agents/agents/backend-agent.md` -> `.agents/agents/frontend-agent.md`.

---

## 🏛️ Referências de Arquitetura (Leitura Obrigatória)

### 🛡️ Backend (Laravel Modular)
Localize o componente e siga seu guia em `.agents/docs/backend/`:
- **[Controllers](.agents/docs/backend/controllers.md)**: Orquestração e Respostas.
- **[Services](.agents/docs/backend/services.md)**: Lógica de negócio (Método único `execute`).
- **[Repositories](.agents/docs/backend/repositories.md)**: Persistência (SQL Puro/CTEs).
- **[Resources](.agents/docs/backend/resources.md)**: Transformação de dados.
- **[Requests](.agents/docs/backend/requests.md)**: Validação e Autorização.
- **[Models](.agents/docs/backend/models.md)**: Entidades e Relacionamentos.
- **[Cache Helpers](.agents/docs/backend/cache_helpers.md)**: Cache obrigatório (Redis).

### 🎨 Frontend (Nuxt 3 Modular)
Localize o componente e siga seu guia em `.agents/docs/frontend/`:
- **[Architecture](.agents/docs/frontend/architecture.md)**: Visão geral Nuxt 3.
- **[Modules](.agents/docs/frontend/modules.md)**: Organização por domínio.
- **[Components](.agents/docs/frontend/components.md)**: Atomicidade e Props.
- **[Services](.agents/docs/frontend/services.md)**: Consumo de API.

---

## 🛠️ Regras de Ouro (Strict Rules)

1.  **Sem Monólitos**: Novas funcionalidades devem ser criadas dentro de domínios isolados (`app/Packages/{Dominio}` no Backend e `modules/{dominio}` no Frontend).
2.  **Performance Primeiro**: Listagens **NUNCA** batem direto no banco; use `Cache Helpers`. Consultas complexas **DEVEM** usar CTEs.
3.  **Padronização JSON**: Respostas de API seguem o formato `{success, message, data}` via trait `Response`.
4.  **Independência de Camadas**: Controller não conhece banco, Service não conhece HTTP, Repository não conhece regra de negócio.
5.  **Tipagem Estrita**: TypeScript obrigatório no Frontend e Types/Hints obrigatórios no Backend.

---

## 📋 Standard Operating Procedures (SOPs)

### 💾 Database
- **Schema First**: Sempre use o MCP postgres para ler o schema antes de sugerir mudanças em Models ou Controllers.
- **Read-Only**: Nunca tente comandos de `INSERT` ou `DELETE` diretamente via MCP; use Seeders ou código da aplicação.

### 🐙 Git & GitHub
- **Nomenclatura de Branch**: `TASK-{ID-JIRA}-descrição` (ex: `TASK-123-autenticacao-pgcrypto`).
- **Padrão de Commit**: `[ID-JIRA] tipo(modulo):descrição` (ex: `[TASK-123] feat(auth): hashing via pgcrypto`).
- **Pull Requests**: Sempre use `gh pr create --fill`. Verifique o status com `gh status` antes de prosseguir.

### 🧠 Raciocínio e Fluxo
- **Complexidade**: Para tarefas que envolvam mais de 3 arquivos, ative obrigatoriamente o `sequential-thinking` para mapear dependências.
- **Jira**: Ao finalizar uma tarefa, pergunte obrigatoriamente se deve atualizar o status do ticket correspondente no Jira.

---

## 🔧 Skills e Automação
Utilize as ferramentas em `.agents/skills/` para acelerar o desenvolvimento:
- **[Documentation Manager](.agents/skills/documentation_manager.md)**: Sincroniza docs com o código atual.
- **[Git Manager](.agents/skills/git_manager.md)**: Automatiza o fluxo de PRs e Commits.
- **[SQL Optimizer](.agents/skills/sql_optimizer.md)**: Garante CTEs de alta performance.

---

## 🧪 Qualidade e Testes
Siga as diretrizes em `.agents/checklists/` para garantir que a implementação está completa e testada.
- **Backend**: Pest PHP (Feature e Unit).
- **Frontend**: Vitest e Playwright.

---
> [!IMPORTANT]
> A estrutura de agentes e workflows reside agora em `.agents/`, centralizando a inteligência do projeto.
