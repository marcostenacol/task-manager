# Camada de Rotas

As **Rotas** são responsáveis por definir os **Endpoints** da API, mapeando URIs HTTP para ações específicas nos Controllers. Utilizamos um sistema de carregamento dinâmico e modular para garantir escalabilidade e organização.

## 1. Conceito e Utilização
O roteamento é dividido por versões da API (ex: `v1`) e, dentro de cada versão, por arquivos de domínio (ex: `users.php`, `orders.php`). Isso evita que um único arquivo de rotas se torne impossível de manter.

---

## 2. Responsabilidades (O que FAZER) ✅
1.  **Modularização**: Criar um arquivo específico em `routes/api/v1/` para cada novo domínio ou package.
2.  **Agrupamento por Prefixo**: Utilizar `Route::group(['prefix' => 'nome-do-dominio', ...])` para organizar as URIs.
3.  **Aliases de Nome**: Definir sempre um nome para a rota usando o parâmetro `as` no grupo e `.name()` no endpoint (ex: `people.show`).
4.  **Middleware de Autenticação**: Proteger rotas sensíveis usando o middleware `auth`.
5.  **Vinculação de Controller**: Apontar diretamente para as classes de Controller dentro dos Packages (ex: `[PersonController::class, 'show']`).
6.  **Carregamento Automático**: O arquivo `routes/api.php` já realiza o `glob` automático de todos os arquivos em `v1/`, portanto, basta criar o arquivo no diretório correto.

---

## 3. Proibições (O que NÃO FAZER) ❌
1.  **Lógica na Rota**: Jamais utilize Closures (funções anônimas) diretamente nos arquivos de rota para processar lógica. Use sempre Controllers.
2.  **Rotas Fora de Grupos**: Evite declarar rotas soltas sem prefixos ou nomes definidos.
3.  **Hardcoding de Paths**: Não aponte para controllers usando strings (ex: `'App\Http\Controllers\UserController@login'`). Use a sintaxe de classe `[UserController::class, 'login']`.

---

## 4. Diretrizes para IA (Prompting/Agentic) 🤖
Ao criar uma nova funcionalidade, siga este fluxo de roteamento:
- Verifique se o arquivo de domínio já existe em `routes/api/v1/`.
- Utilize o padrão de nomes `v1.dominio.acao`.
- Se a rota envolve um ID, utilize o padrão de prefixo `{id}` para agrupar sub-recursos.

---

## 5. Exemplo de Implementação Padrão (routes/api/v1/domain.php)

```php
use App\Packages\Domain\Module\Controllers\EntityController;
use Illuminate\Support\Facades\Route;

// 1. Agrupamento por domínio, alias e segurança
Route::group(['prefix' => 'entities', 'as' => 'entities.', 'middleware' => 'auth'], function () {
    
    // Rota simples de busca
    Route::get('search', [EntityController::class, 'search'])->name('search');

    // 2. Agrupamento por recurso específico (ID)
    Route::prefix('{entity_id}')->group(function () {
        Route::get('', [EntityController::class, 'show'])->name('show');
        
        // Sub-recursos vinculados à entidade
        Route::get('history', [EntityController::class, 'getHistory'])->name('get-history');
        Route::put('data', [EntityController::class, 'updateData'])->name('update-data');
    });
});
```

---

## 6. Estrutura de Carregamento (routes/api.php)

O sistema carrega os módulos automaticamente, permitindo que o desenvolvedor foque apenas na criação dos arquivos de v1.

```php
Route::group(['prefix' => 'v1', 'as' => 'v1.'], function () {
    foreach (glob(base_path('routes/api/v1/*.php')) as $route_file) {
        require $route_file;
    }
});
```

---

## 7. Convenções de URI
- **Plural**: Use sempre o plural para os domínios (ex: `/people`, `/attendances`, `/addresses`).
- **Kebab-case**: Use hífens para separar palavras na URI (ex: `/simple-register`, `/family-members`).
- **Verbos HTTP**: 
    - `GET`: Recuperação de dados.
    - `POST`: Criação de recursos.
    - `PUT`: Atualização completa.
    - `PATCH`: Atualização parcial (se aplicável).
    - `DELETE`: Remoção de recursos.
