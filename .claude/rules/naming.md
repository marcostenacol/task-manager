# Convenções de nomenclatura

## Idioma

Identificadores de código em **inglês** (classes, métodos, variáveis, arquivos). Mensagens ao usuário (`message:` em `successResponse()`/`returnError()`, mensagens de validação) em **português** — confirmado em todos os Controllers reais (`'Tarefa criada com sucesso.'`, `'Você não possui permissão para acessar esse recurso!'`).

## Padrões por camada (confirmados no código real)

| Camada | Padrão | Exemplos reais |
|--------|--------|-----------------|
| Model | `PascalCase`, singular | `Task`, `User`, `TaskStatus`, `TaskPriority`, `UserContact` |
| Repository | `{Model}Repository` | `TaskRepository`, `UserRepository`, `AuthRepository` |
| Service | `{Ação}{Entidade}Service` | `CreateTaskService`, `UpdateTaskStatusService`, `ListTasksService`, `DetailUserService`, `BanUserService`, `ChangeUserRoleService` |
| Controller | `{Entidade}Controller` | `TaskController`, `AdminUserController`, `PersonController`, `ContactController` |
| Request | `{Ação}{Entidade}Request` | `CreateTaskRequest`, `UpdateTaskRequest`, `LoginRequest`, `RegisterRequest` |
| Resource | `{Entidade}Resource` | `TaskResource`, `AdminUserResource`, `ContactResource`, `PersonResource`, `LoginResource` |
| Enum | `{Entidade}Enum` | `SettingsEnum` |
| Middleware | `{Ação}Middleware` | `AuthenticateMiddleware`, `ForceJsonMiddleware` |

Método principal de um Service: sempre `execute()` (confirmado em todas as Services de `Task/Tasks/Services/` e `Admin/Users/Services/`).

## Variáveis

Variáveis comuns em `camelCase` no código real (**não** `snake_case`) — ex.: `$userId`, `$statusId`, `$dueDate` em `TaskRepository::listWithFilters()`. Isso diverge do padrão `snake_case` usado em outros projetos AE3 — siga o padrão real observado aqui: **camelCase para variáveis em geral**, incluindo dependências injetadas.

Colunas de banco em `snake_case` (`status_id`, `due_date`, `created_at`) — padrão SQL/Eloquent normal, sem divergência.

## Importações

`use` no topo do arquivo é o padrão majoritário (visto em Models, Services, Requests). **⚠️ Excessão real e conhecida**: `routes/api.php` usa FQCN inline (`\App\Packages\Social\Person\Controllers\PersonController::class`) na maior parte das rotas de `social` e `admin` — não é um padrão a seguir, é uma violação já presente no arquivo. Ao adicionar rota nova, prefira `use` no topo (Boy Scout Rule aplicada à própria linha que você adiciona), mas não é obrigatório reescrever as rotas existentes.

## Boy Scout Rule

Ao tocar um arquivo, adapte-o aos padrões observados neste documento — mas não mude a decisão de ter/não ter Repository, nem mova um domínio de pasta, sem pedido explícito.
