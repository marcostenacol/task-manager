# Gestão de Usuários — criação/edição de usuários + troca de senha

Documento de design para orientar a implementação. Não implementado ainda — só planejado, seguindo os padrões reais já confirmados no projeto (`.claude/rules/`).

## Contexto

Hoje `Admin/Users` (backend) só tem `index`, `show`, `ban`, `activate`, `changeRole` (`AdminUserController`). Não existe:
- Criar usuário via admin.
- Editar dados de um usuário existente (nome/e-mail/role) via admin.
- Trocar a própria senha (nenhum endpoint de mudança de senha existe em lugar nenhum do projeto — nem em `Auth`, nem em `Social/Person`).

No frontend, `UserTable.vue` só tem ações de banir/ativar/ver detalhes — sem criar/editar. `ProfileForm.vue` edita nome/e-mail/bio, mas não senha.

## Parte 1 — Backend

### 1.1 Criar usuário (admin)

**Rota**: `POST /v1/admin/users`, middleware `auth.api:admin.users.create` (permissão nova, allow-list — seguir o padrão de `admin.users.list`/`admin.users.ban` já existente).

**Arquivos novos**:
- `app/Packages/Admin/Users/Requests/CreateUserRequest.php` — mesmo padrão de `RegisterRequest`:
  ```php
  return [
      'name' => ['required', 'string', 'max:255'],
      'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
      'password' => ['required', 'string', 'min:8'],
      'role_id' => ['required', 'uuid', Rule::exists(Role::class, 'id')],
  ];
  ```
- `app/Packages/Admin/Users/Services/CreateUserService.php` — `execute(array $data): User`, `DB::transaction()`, busca o `UserStatus` `active` por slug (mesmo padrão do `RegisterService::execute()`), chama `UserRepository::save()`. Diferente do `RegisterService`, o `role_id` vem do payload (admin escolhe), não é fixado em `user`.
- `AdminUserController::store(CreateUserRequest $request, CreateUserService $service)` — `self::successResponse(AdminUserResource::make($service->execute($request->validated())), 'Usuário criado com sucesso.', Response::HTTP_CREATED)`.

**Rota** (`routes/api.php`, dentro do grupo `admin.users.`):
```php
Route::post('/', [AdminUserController::class, 'store'])->middleware('auth.api:admin.users.create')->name('store');
```

**Migration**: nova permissão `admin.users.create` precisa existir em `admin.permissions` e ser associada à role `admin` em `admin.role_has_permissions` — seguir o padrão de migration de seeder já usado no projeto (ver `2026_07_28_120000_run_permission_seeder.php`), **não** inserir direto via SQL solto na migration.

### 1.2 Editar usuário (admin)

**Rota**: `PUT /v1/admin/users/{id}`, middleware `auth.api:admin.users.update` (permissão nova).

**Arquivos novos**:
- `app/Packages/Admin/Users/Requests/UpdateUserRequest.php`:
  ```php
  return [
      'name' => ['sometimes', 'string', 'max:255'],
      'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($this->route('id'))],
      'role_id' => ['sometimes', 'uuid', Rule::exists(Role::class, 'id')],
  ];
  ```
  Sem `password` aqui — troca de senha é fluxo separado (ver Parte 1.3), assim como `ChangeUserRoleService` já é separado de update genérico (mesmo espírito de "um Service por ação").
- `app/Packages/Admin/Users/Services/UpdateUserService.php` — `execute(string $id, array $data): User`, `DB::transaction()`, `User::findOrFail($id)->update($data)`.
- `AdminUserController::update(string $id, UpdateUserRequest $request, UpdateUserService $service)`.

**Rota**:
```php
Route::put('{id}', [AdminUserController::class, 'update'])->middleware('auth.api:admin.users.update')->name('update');
```

### 1.3 Troca de senha (self-service)

Diferente de criar/editar (ação de admin sobre outro usuário), troca de senha é o próprio usuário trocando a própria senha — não pertence a `Admin/Users`, pertence a `Social/Person` (mesmo domínio do resto do "meu perfil") ou a `Auth/Auth` (mesmo domínio de login/registro). **Recomendação**: colocar em `Social/Person`, já que a tela é "Meu Perfil" no frontend — evita criar um novo grupo de rota só para isso.

**Rota**: `PUT /v1/social/profile/password`, middleware `auth.api` (sem permissão extra — é sobre o próprio usuário autenticado, resolvido via `userObject()`, mesmo padrão de `PersonController::show()`).

**Arquivos novos**:
- `app/Packages/Social/Person/Requests/ChangePasswordRequest.php`:
  ```php
  return [
      'current_password' => ['required', 'string'],
      'new_password' => ['required', 'string', 'min:8', 'confirmed'],
  ];
  ```
  (`confirmed` exige `new_password_confirmation` no payload — padrão do Laravel, não precisa de campo customizado.)
- `app/Packages/Social/Person/Services/ChangePasswordService.php`:
  ```php
  public function execute(string $userId, string $currentPassword, string $newPassword): void
  {
      DB::transaction(function () use ($userId, $currentPassword, $newPassword) {
          $user = User::findOrFail($userId);

          if (! DB::selectOne('SELECT admin.check_hash_constant_time(?, ?) as ok', [
              DB::selectOne('SELECT admin.generate_password_hash(?) as hash', [$currentPassword])->hash,
              $user->password,
          ])->ok) {
              throw new InvalidArgumentException('Senha atual incorreta.');
          }

          $user->update(['password' => $newPassword]); // passa pelo Attribute::make (User::password()), já faz o hash
      });
  }
  ```
  **Atenção**: confirmar a assinatura exata de `admin.check_hash_constant_time` lendo `database/migrations/2026_04_09_090808_create_auth_flux.php` antes de implementar — o exemplo acima assume que ela compara dois hashes, mas pode ser que ela espere `(senha_pura, hash)` como no uso real do `process_login` (`crypt(password_p, user_data.password::text)`). Ajustar para bater com a função real, não com esta suposição.
- `PersonController::changePassword(ChangePasswordRequest $request, ChangePasswordService $service)` — `self::successResponse(null, 'Senha alterada com sucesso.')`.

**Rota**:
```php
Route::put('profile/password', [PersonController::class, 'changePassword'])->name('profile.change-password');
```

## Parte 2 — Frontend

### 2.1 Menu/tela de usuários (admin) — criar e editar

**Arquivos**:
- `AdminService.ts` — adicionar:
  ```ts
  async createUser(data: CreateUserData) {
      return useApi('/v1/admin/users', { method: 'POST', body: data });
  },
  async updateUser(id: string, data: UpdateUserData) {
      return useApi(`/v1/admin/users/${id}`, { method: 'PUT', body: data });
  }
  ```
- `app/modules/admin/components/UserFormModal.vue` — novo, seguindo exatamente o mesmo padrão visual/estrutural de `TaskModal.vue` (overlay + backdrop + form com tokens do design system, não Tailwind): campos nome/e-mail/senha (só na criação)/role (`<select>` com as roles reais, buscadas via um novo `AdminService.listRoles()` ou endpoint já existente se houver — confirmar se `GET /v1/admin/roles` já existe antes de assumir; se não existir, é mais um endpoint a planejar).
- `app/pages/admin/users/index.vue` — adicionar botão "Novo Usuário" (mesmo estilo de `.btn-new-task` em `tasks/index.vue`) que abre o `UserFormModal`; `UserTable.vue` ganha um botão de "editar" (ícone lápis, ao lado de banir/ativar/detalhes) que abre o mesmo modal em modo edição.

### 2.2 Troca de senha (self-service)

**Arquivos**:
- `SocialService.ts` — adicionar `changePassword(current_password, new_password, new_password_confirmation)` chamando `PUT /v1/social/profile/password`.
- `useProfile.ts` — adicionar `changePassword(data)` que chama o service e retorna `true`/`false` (mesmo padrão de `updateProfile`/`uploadAvatar`).
- Novo componente `app/modules/social/components/ChangePasswordForm.vue` — mesmo padrão visual de `ProfileForm.vue`, 3 campos (senha atual, nova senha, confirmar nova senha), exibindo erros de validação (`errors?.current_password`, etc.) como já feito em `TaskModal.vue`.
- `app/pages/profile.vue` — adicionar `<ChangePasswordForm @change-password="changePassword" />` na coluna direita, abaixo de `ProfileForm`.

## Fora de escopo deste documento

- Reset de senha por e-mail (já documentado como fora de escopo no `README.md` do projeto — sem SMTP configurado, ver `rules/project-context.md`).
- Exclusão (delete) de usuário — não pedido, e o padrão do projeto hoje é "banir" em vez de deletar (soft state via `last_status_id`), não uma operação destrutiva de registro.
- Testes Pest para os novos endpoints — devem ser adicionados ao implementar, seguindo o padrão de `tests/Feature/Admin/AdminUserTest.php`, mas não escritos aqui (este documento é design, não implementação).
