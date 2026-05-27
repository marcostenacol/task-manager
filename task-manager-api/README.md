# Task Manager

Uma plataforma moderna de gestão de tarefas construída com Laravel modular no backend e Nuxt 3 no frontend, com segurança e performance delegadas ao PostgreSQL.

## Funcionalidades

- **Autenticação Segura**: Login, registro, logout e refresh token via PL/pgSQL.
- **Gestão de Tarefas**: CRUD completo com status, prioridades e filtros avançados.
- **Perfil Social**: Edição de perfil, avatar e contatos do usuário.
- **Painel Admin**: Gestão de usuários, roles e histórico de status.
- **Cache Inteligente**: Redis em todas as listagens e tokens.
- **RBAC**: Controle de acesso baseado em permissões por role.

## Stack Técnica

### Backend
- **Framework**: Laravel 11 (arquitetura modular por Packages)
- **Banco de Dados**: PostgreSQL com schemas customizados (`admin`, `public`)
- **Segurança**: Funções PL/pgSQL para autenticação e validação de tokens
- **Cache**: Redis (tokens, listagens, dados de usuário)
- **Testes**: Pest PHP (Feature + Unit)

### Frontend
- **Framework**: Nuxt 3 (Vue 3 + TypeScript)
- **Organização**: Módulos por domínio (`modules/auth`, `modules/tasks`, etc.)
- **Estilo**: CSS Vanilla com design system próprio (glassmorphism, dark mode)

### Infraestrutura
- **Containerização**: Docker + Docker Compose
- **Proxy**: Nginx
- **Automação**: Makefile com comandos para dev, test e database

---

## ⚠️ Limitações de Infraestrutura

> **Este projeto não utiliza nenhum serviço externo de mensageria ou notificação.**

As seguintes funcionalidades **estão fora do escopo** por limitação de infraestrutura local:

| Funcionalidade | Motivo | Alternativa adotada |
|---|---|---|
| **Envio de e-mails** | Sem servidor SMTP configurado | Ações como "esqueci minha senha" e "boas-vindas" não são implementadas |
| **Notificações Push** | Sem serviço FCM/APNS | Status de tarefas atualizados apenas via polling/refetch |
| **SMS / WhatsApp** | Sem integração com Twilio/Z-API | Campo de contato é armazenado mas não dispara mensagens |
| **Webhooks externos** | Sem endpoint de recebimento exposto publicamente | Integrações externas não são suportadas |
| **Filas de jobs** | Sem worker de fila ativo em dev | Todos os processos são síncronos |
| **Recuperação de senha por e-mail** | Depende de SMTP | Não implementado — redefinição manual via admin |

> [!NOTE]
> Qualquer funcionalidade futura que dependa de comunicação externa deve ser avaliada e aprovada antes de ser adicionada ao backlog.

---

## Instalação

### Pré-requisitos

- Docker e Docker Compose
- Make
- Node.js 20+

### Setup Rápido

```bash
# Clone o repositório
git clone <repository-url>
cd task-manager

# Suba os containers
make up

# Execute as migrations
make migrate

# Execute os seeders
make art c="db:seed"

# Inicie o frontend
cd task-manager-ui && npm install && npm run dev
```

### Configuração do Ambiente

Copie o `.env.example` e configure:

```env
DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=task-manager
DB_USERNAME=root
DB_PASSWORD=root
DB_SCHEMA=public

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

---

## Rodando os Testes

```bash
# Recria o banco de testes e roda todos os testes
make migrate-testing && make test

# Roda um grupo de testes específico
make test f=Auth
make test f=Task
make test f=Social
make test f=Admin
```

---

## Estrutura do Projeto

```
task-manager/
├── task-manager-api/              # Backend Laravel Modular
│   ├── app/
│   │   ├── Base/                  # Traits, Helpers e Repository base
│   │   └── Packages/
│   │       ├── Admin/             # Domínio Admin (Users, Roles, Permissions)
│   │       ├── Auth/              # Domínio Auth (Login, Logout, Refresh)
│   │       ├── Social/            # Domínio Social (Perfil, Contatos, Avatar)
│   │       └── Task/              # Domínio Task (Tasks, Statuses, Priorities)
│   ├── database/
│   │   ├── migrations/            # Migrations + funções PL/pgSQL
│   │   └── seeders/
│   ├── routes/api.php
│   └── tests/Feature/             # Testes espelhando cada domínio
├── task-manager-ui/               # Frontend Nuxt 3
│   └── app/
│       ├── assets/css/            # Design system (variáveis, reset)
│       ├── composables/           # useApi.ts (interceptor global)
│       ├── middleware/            # Proteção de rotas
│       ├── modules/
│       │   ├── Landing/           # Landing page
│       │   ├── auth/              # Login, Register
│       │   ├── social/            # Perfil do usuário
│       │   ├── tasks/             # Dashboard e CRUD de tarefas
│       │   └── admin/             # Painel administrativo
│       └── pages/                 # Roteamento Nuxt
└── .agents/                       # Documentação e guias para automação
    ├── agents/                    # Agentes especializados
    ├── docs/                      # Referências de arquitetura
    └── skills/                    # Skills automatizadas
```

---

## Documentação da API

A documentação da API é gerada automaticamente via **Scramble (OpenAPI)** e estará disponível em:

```
http://localhost:8000/docs/api
```

---

## Licença

Este projeto é licenciado sob a [MIT license](https://opensource.org/licenses/MIT).
