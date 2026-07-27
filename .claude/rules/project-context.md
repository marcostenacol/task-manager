# Contexto do Projeto — Task Manager

## O que é

Plataforma de gestão de tarefas pessoal/multiusuário: cada usuário autenticado gerencia suas próprias tarefas (CRUD, status, prioridade, filtros), tem um perfil social (dados pessoais, avatar, contatos) e há um painel administrativo para gestão de usuários (banir, ativar, trocar role). Confirmado em `README.md` e na árvore de domínios reais (`Task`, `Social`, `Admin`, `Auth`).

## Domínios reais (`app/Packages/`)

| Domínio | Conteúdo | Onde vive |
|---------|----------|-----------|
| Auth | Registro, login, logout, refresh token — via funções PL/pgSQL | `app/Packages/Auth/Auth/` |
| Admin/Users | Listagem, detalhe, banir, ativar, trocar role de usuários | `app/Packages/Admin/Users/` |
| Admin/Roles, Admin/Permissions, Admin/UserStatuses, Admin/Settings | Cadastros de apoio ao RBAC e configuração | `app/Packages/Admin/{Roles,Permissions,UserStatuses,Settings}/` |
| Social/Person | Perfil do usuário (dados, avatar) | `app/Packages/Social/Person/` |
| Social/Contacts | Contatos do usuário | `app/Packages/Social/Contacts/` |
| Task/Tasks | CRUD de tarefas, filtros, status de tarefa | `app/Packages/Task/Tasks/` |
| Task/Statuses, Task/Priorities | Cadastros de apoio (status/prioridade de tarefa) | `app/Packages/Task/Statuses/`, `app/Packages/Task/Priorities/` |

## Atores

- **Usuário comum**: gerencia suas próprias tarefas e perfil.
- **Admin**: gerencia usuários da plataforma (via permissões dedicadas `admin.users.list`, `admin.users.show`, `admin.users.ban`, `admin.users.activate`, `admin.users.role` — confirmado em `routes/api.php`).

RBAC real: permissões nomeadas por ação (`admin.users.ban`, não uma permissão genérica "admin") verificadas via `array_intersect` no middleware `auth.api:{permissão}` — allow-list, sem `spatie/laravel-permission`.

## Limitações de infraestrutura conhecidas (documentadas no `README.md` do projeto)

O `README.md` do `task-manager-api` lista explicitamente funcionalidades **fora do escopo por limitação de infraestrutura local** — importante não implementar/assumir nenhuma dessas sem alinhar antes:

| Funcionalidade | Motivo | Estado real |
|---|---|---|
| Envio de e-mail | Sem SMTP configurado (`MAIL_MAILER=log`) | "Esqueci minha senha"/boas-vindas não implementados |
| Notificação push | Sem FCM/APNS | Atualização de status só via polling/refetch |
| SMS/WhatsApp | Sem Twilio/Z-API | Campo de contato armazenado, não dispara mensagem |
| Webhooks externos | Sem endpoint público exposto | Não suportado |
| Filas de job | Sem worker ativo em dev (`QUEUE_CONNECTION=database`, sem `queue:work` no fluxo padrão) | Todo processamento é síncrono |
| Recuperação de senha por e-mail | Depende de SMTP | Redefinição manual via admin |

Qualquer feature nova que dependa de comunicação externa (e-mail, SMS, push, webhook) deve ser avaliada antes de entrar no backlog — não é uma omissão a "corrigir" silenciosamente numa tarefa não relacionada.

## Conformidade

O sistema armazena dados pessoais do usuário (nome, e-mail, contatos, avatar). Trate como dado pessoal sensível ao adicionar campo novo — confirme finalidade antes de expandir o schema de `Social/Person`/`Social/Contacts`.
