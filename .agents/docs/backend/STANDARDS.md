# Padrões de Desenvolvimento - Backend

A padronização garante que qualquer desenvolvedor consiga ler e manter o código de qualquer módulo do sistema.

## 1. Padronização de JSON Response

Todas as respostas da API devem obrigatoriamente seguir o formato:
```json
{
    "success": boolean,
    "message": "Mensagem humanizada",
    "data": [] | {} | null
}
```
### Implementação
Use a trait `App\Base\Traits\Response` no `BaseController`.
- Erros de validação (422) devem retornar um objeto `errors` dentro de `data`.
- Exceptions não tratadas devem ser capturadas pelo `Handler` e retornadas usando o método `internalServerErrorResponse`.

## 2. Nomenclatura (Naming Conventions)

### Classes e Arquivos
- **Controllers**: `EntidadeController` (ex: `UserController.php`).
- **Services**: `VerboEntidadeService` (ex: `StorePersonService.php`).
- **Repositories**: `EntidadeRepository` (ex: `OrderRepository.php`).
- **Resources**: `EntidadeResource` (ex: `ProductResource.php`).

### Banco de Dados
- **Tabelas**: Plural, snake_case (ex: `user_statuses`).
- **Colunas**: snake_case (ex: `created_at`).
- **Foreign Keys**: `entidade_id` (ex: `person_id`).

## 3. Regras de Ouro da Camada Service

1. **Injeção de Dependência**: Repositories e outros Services devem ser injetados via construtor.
2. **Método Único**: Prefira o uso de um único método público `execute()`.
3. **Tipo de Retorno**: Defina estritamente o tipo de retorno do método.
4. **Sem HTTP**: O Service nunca deve ler dados de `request()` diretamente ou retornar `response()`. Ele recebe dados e retorna objetos/valores.

## 4. Repositories e Performance (SQL Puro vs Eloquent)

- **Leitura**: Para listagens complexas, use `DB::raw` ou consultas SQL puras com CTEs.
- **Escrita**: Use Eloquent para garantir disparos de eventos e auditorias.

## 5. Validação e Autorização (Requests)

Use FormRequests do Laravel para centralizar `rules()` e `authorize()`.

## 6. Segurança Delegada e PL/pgSQL 🛡️

O projeto utiliza o PostgreSQL como motor de segurança para o fluxo de autenticação:
1. **Atomicidade**: Lógicas de login, refresh e logout são delegadas para funções PL/pgSQL (ex: `admin.process_login`).
2. **Integridade de Tokens**: A detecção de reuso de Refresh Tokens (ataques de replay) é tratada atomicamente no banco.
3. **Invalidação Determinística**: Ao invalidar tokens, use sempre `now() - interval '1 minute'` para garantir que disparidades de milissegundos entre o servidor de aplicação e o banco não causem falsos positivos em verificações de expiração (`expires_at < NOW()`).

---
> [!IMPORTANT]
> A lógica de negócio reside **APENAS** nos Services. Controllers apenas chamam Services, e Repositories apenas persistem/buscam dados.
