# 📊 Progresso de Implementação

> **Este arquivo é a fonte da verdade sobre o estado atual do projeto.**
> Deve ser atualizado a cada fase concluída — Backend e Frontend separadamente.
> Última atualização: 2026-05-06

---

## Legenda

| Símbolo | Significado |
|---------|-------------|
| ✅ | Concluído e testado |
| 🚧 | Em progresso |
| ⬜ | Não iniciado |
| ❌ | Bloqueado / com problema |

---

## 🏗️ Infraestrutura Base

| Item | Status | Observação |
|------|--------|------------|
| Scaffold Laravel + estrutura de Packages | ✅ | `app/Packages/{Dominio}/` |
| Docker (PHP, Nginx, Redis, PostgreSQL) | ✅ | `postgis/postgis:17-master` (Debian) |
| PostgreSQL + extensões (pgcrypto, pg_trgm, unaccent) | ✅ | |
| Schemas (`admin`, `tasks`, `social`) | ✅ | |
| Stubs customizados | ✅ | |
| `.agents/` com documentação de padrões | ✅ | |
| Rate limiting (throttle:10,1 em login/register) | ✅ | |
| Redis + CacheTrait | ✅ | |
| RBAC (abilities no AuthenticateMiddleware) | ✅ | |
| PermissionSeeder | ✅ | |

---

## 🔐 Módulo Auth

### Backend
| Item | Status | Observação |
|------|--------|------------|
| Migrations (roles, users, tokens, refresh_tokens) | ✅ | |
| Seeders (roles, user_statuses) | ✅ | |
| Funções PL/pgSQL (process_login, get_user_by_token) | ✅ | Blowfish nativo (`gen_salt('bf')`) |
| `generate_password_hash` via `crypt + bf` | ✅ | Model `User.php` chama a função SQL |
| `process_refresh` | ✅ | |
| `process_logout` | ✅ | |
| `LoginController` + `LoginService` | ✅ | `abort(400)` em falha |
| `RegisterController` + `RegisterService` | ✅ | |
| `LogoutController` + `LogoutService` | ✅ | |
| `RefreshTokenController` + `RefreshTokenService` | ✅ | |
| `AuthenticateMiddleware` | ✅ | |
| Testes (Login, Logout, Register, Refresh) | ✅ | 4 arquivos, todos passando |

### Frontend
| Item | Status | Observação |
|------|--------|------------|
| `modules/auth/models/auth.ts` | ✅ | |
| `modules/auth/services/AuthService.ts` | ✅ | |
| `modules/auth/hooks/useAuth.ts` | ✅ | |
| `modules/auth/components/LoginForm.vue` | ✅ | |
| `modules/auth/components/RegisterForm.vue` | ✅ | |
| `pages/login.vue` | ✅ | |
| `pages/register.vue` | ✅ | |
| Middleware de rota (proteção de páginas privadas) | ✅ | |
| Landing page | ✅ | `modules/Landing/` |

---

## 👤 Módulo Social

### Backend
| Item | Status | Observação |
|------|--------|------------|
| Migration `add_social_fields_to_users_table` | ✅ | `avatar_path`, `bio` |
| Migration `create_user_contacts_table` | ✅ | |
| `PersonController` (show, update, avatar) | ✅ | |
| `DetailPersonService` | ✅ | Cache Redis por `user_id` |
| `UpdatePersonService` | ✅ | Invalida cache |
| `UpdateOrCreateAvatarService` | ✅ | Storage local |
| `PersonResource` | ✅ | |
| `UpdatePersonRequest` | ✅ | |
| `ContactController` (index, store, destroy) | ✅ | |
| `CreateContactService` | ✅ | |
| `UserContact` model | ✅ | |
| `ContactResource` | ✅ | |
| Testes (`SocialTest.php`) | ✅ | 4 testes passando |

### Frontend
| Item | Status | Observação |
|------|--------|------------|
| `modules/social/models/social.ts` | ✅ | |
| `modules/social/services/SocialService.ts` | ✅ | |
| `modules/social/hooks/useProfile.ts` | ✅ | |
| `modules/social/components/ProfileCard.vue` | ✅ | |
| `modules/social/components/ProfileForm.vue` | ✅ | |
| `modules/social/components/AvatarUpload.vue` | ✅ | |
| `modules/social/components/ContactsSection.vue` | ✅ | |
| `pages/profile.vue` | ✅ | |

---

## 📋 Módulo Task

### Backend
| Item | Status | Observação |
|------|--------|------------|
| `Task.php` model | ✅ | Esqueleto |
| `TaskStatus.php`, `TaskPriority.php` models | ✅ | Esqueletos |
| `TaskController` | ✅ | |
| `CreateTaskService` | ✅ | |
| `ListTasksService` | ✅ | CTE + filtros + paginação + Redis |
| `DetailTaskService` | ✅ | Cache por `task_id` |
| `UpdateTaskService` | ✅ | |
| `DeleteTaskService` | ✅ | |
| `UpdateTaskStatusService` | ✅ | Valida transição de status |
| `TaskRepository` | ✅ | CTE com filtros dinâmicos |
| `TaskResource` | ✅ | |
| `CreateTaskRequest`, `UpdateTaskRequest` | ✅ | |
| `task_cache.php` helper | ✅ | |
| Rotas (`/v1/tasks`, `/v1/tasks/{id}`, `/status`) | ✅ | |
| Testes (Create, List, Detail, Update, Delete, Status) | ✅ | 6 arquivos |

### Frontend
| Item | Status | Observação |
|------|--------|------------|
| `modules/tasks/models/task.ts` | ✅ | |
| `modules/tasks/services/TaskService.ts` | ✅ | |
| `modules/tasks/hooks/useTasks.ts` | ✅ | |
| `modules/tasks/hooks/useTaskForm.ts` | ✅ | |
| `modules/tasks/components/TaskCard.vue` | ✅ | |
| `modules/tasks/components/TaskList.vue` | ✅ | |
| `modules/tasks/components/TaskKanban.vue` | ✅ | Drag & drop HTML5 nativo |
| `modules/tasks/components/TaskModal.vue` | ✅ | |
| `modules/tasks/components/TaskFilters.vue` | ✅ | |
| `pages/tasks/index.vue` | ✅ | |
| `pages/tasks/[id].vue` | ✅ | |

---

## 🛡️ Módulo Admin

### Backend
| Item | Status | Observação |
|------|--------|------------|
| `User.php`, `Role.php`, `Permission.php` models | ✅ | Com HasUuids |
| `UserRepository` | ✅ | Implementado `listWithFilters` (CTE) |
| `AdminUserController` | ✅ | |
| `ListUsersService` | ✅ | CTE + último status + Redis |
| `DetailUserService` | ✅ | Cache por `user_id` |
| `BanUserService` | ✅ | Sem e-mail |
| `ActivateUserService` | ✅ | Sem e-mail |
| `ChangeUserRoleService` | ✅ | |
| `ListUserStatusHistoryService` | ✅ | |
| `AdminUserResource` | ✅ | |
| Rotas admin (protegidas com `auth.api:admin.*`) | ✅ | |
| Testes (List, Detail, Ban, Activate, ChangeRole) | ✅ | Testes Pest PHP |

### Frontend
| Item | Status | Observação |
|------|--------|------------|
| `modules/admin/models/admin.ts` | ✅ | |
| `modules/admin/services/AdminService.ts` | ✅ | |
| `modules/admin/hooks/useUsers.ts` | ✅ | |
| `modules/admin/hooks/useUserDetail.ts` | ✅ | |
| `modules/admin/components/UserTable.vue` | ✅ | Design premium glassmorphism |
| `modules/admin/components/UserStatusBadge.vue` | ✅ | |
| `modules/admin/components/UserStatusTimeline.vue` | ✅ | |
| `modules/admin/components/UserRoleSelect.vue` | ✅ | |
| `modules/admin/components/UserActionButtons.vue` | ✅ | Confirmação antes de banir |
| `pages/admin/users/index.vue` | ✅ | |
| `pages/admin/users/[id].vue` | ✅ | |
