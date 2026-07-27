# Testes

Ferramenta: **Pest 4**, já instalado e com cobertura real (diferente de outros harnesses AE3 onde Pest é ainda aspiracional). Confirmado em `composer.json` (`pestphp/pest: ^4.4`, `pestphp/pest-plugin-laravel: ^4.1`) e em `tests/Pest.php` (`pest()->extend(TestCase::class)->in('Feature')`).

## Cobertura real observada

```
tests/
├── Pest.php
├── TestCase.php
├── Unit/ExampleTest.php          # esqueleto padrão, sem lógica real
├── Feature/ExampleTest.php       # esqueleto padrão, sem lógica real
├── Feature/Task/TaskTest.php
├── Feature/Admin/AdminUserTest.php
├── Feature/Social/SocialTest.php
├── Feature/Auth/RegisterTest.php
├── Feature/Auth/LoginTest.php
├── Feature/Auth/LogoutTest.php
├── Feature/Auth/RefreshTokenTest.php
└── Feature/Infra/RBAC/PermissionTest.php
```

Há cobertura real de Feature para os fluxos principais (Auth completo, Task CRUD+filtros, Admin de usuários, Social/perfil, RBAC). **Não há testes `Unit/` reais** — apenas o `ExampleTest.php` esqueleto do Laravel; toda a cobertura observada é via Feature/HTTP.

## Padrão real de teste de Feature (confirmado em `tests/Feature/Task/TaskTest.php`)

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');
    $this->user = User::factory()->create(['password' => 'password123']);

    $response = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $this->token = $response->json('data.access_token.token');
    // ... carrega fixtures de referência (status/priority) por slug
});

test('deve criar uma tarefa com sucesso', function () {
    $response = withToken($this->token)->postJson('/api/v1/tasks', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Minha primeira tarefa');

    $this->assertDatabaseHas('tasks', [...]);
});
```

Pontos a seguir em teste novo:

- **`DatabaseTransactions`, não `RefreshDatabase`** — confirmado no `uses(...)` de `TaskTest.php` e comentado (desativado) em `tests/Pest.php`. O banco de teste é migrado uma vez via `make migrate-testing`/`make db-testing`, e cada teste roda dentro de uma transação revertida ao final — não recria o schema a cada teste.
- **Autenticação real via login**, não mock de `userInCache()`/token direto: o `beforeEach` faz um POST real em `route('v1.auth.login')` e usa o `access_token.token` retornado para montar `withToken($this->token)` nas chamadas seguintes. Não invente um helper de auth "fake" — replique esse padrão de login real no `beforeEach`.
- **Fixtures de referência (`TaskStatus`, `TaskPriority`) buscadas por `slug`** a partir dos seeders reais (`TaskStatusSeeder`, `TaskPrioritySeeder`), não criadas via Factory ad-hoc — confirme se a entidade que você for testar já tem seeder de referência antes de criar dados soltos.
- `artisan('optimize:clear')` no `beforeEach` — limpa caches de config/rota entre testes.

## Rodar

```bash
make test                  # vendor/bin/pest, tudo
make test f=NomeDoTeste    # vendor/bin/pest --filter=NomeDoTeste
make migrate-testing       # migra o banco de teste (--env=testing)
make db-testing            # dropa e recria o banco de teste via psql
```

## O que testar

Regra de bolso: teste o que, se quebrar, dói (fluxo de auth, CRUD de tarefa, permissão admin) — cobertura é consequência. Sem `Unit/` real hoje: se uma regra de negócio isolada (cálculo, validação, Enum) justificar teste sem HTTP/banco, `tests/Unit/` é o lugar certo, mas não há exemplo real além do esqueleto — ao criar o primeiro teste Unit real, siga a convenção AAA e nomes descritivos já usados nos testes de Feature (`test('deve <comportamento>')`).
