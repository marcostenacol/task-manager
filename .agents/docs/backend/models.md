# Camada de Model

Os Models representam a **Estrutura de Dados** e as entidades do domínio. Eles são estritamente ligados ao esquema do PostgreSQL e focam na definição de relacionamentos e casts.

## 1. Conceito e Utilização
Esta arquitetura incentiva o uso de múltiplos schemas no PostgreSQL para organização lógica. Por isso, a definição explícita da tabela com o prefixo do schema é recomendada em todos os Models.

---

## 2. Responsabilidades (O que FAZER) ✅
1.  **Qualificação de Tabela**: Definir obrigatoriamente a propriedade `$table` com o schema (ex: `protected $table = 'social.people';`).
2.  **Atribuição em Massa**: Definir as colunas permitidas no array `$fillable`.
3.  **Relacionamentos**: Tipar os retornos dos métodos de relacionamento (ex: `: BelongsTo`, `: HasMany`).
4.  **Casts**: Utilizar o método `casts(): array` para converter tipos de banco (ex: `boolean`, `datetime`, `json`) para tipos nativos do PHP.
5.  **Timestamps**: O Laravel assume `created_at` e `updated_at` por padrão. Se a tabela não os possuir, defina `public $timestamps = false;`.

---

## 3. Proibições (O que NÃO FAZER) ❌
1.  **Regra de Negócio**: Não insira lógica de negócio complexa dentro do Model. Use Services para isso.
2.  **Consultas (Queries)**: Evite criar métodos que realizam consultas complexas dentro do Model. Delegue para o Repository.
3.  **Acesso Direto ao DB**: Não utilize a Facade `DB` dentro do Model.
4.  **Mutators Pesados**: Evite mutators que realizam consultas a outras tabelas, pois isso causa problemas de performance (N+1).

---

## 4. Diretrizes para IA (Prompting/Agentic) 🤖
Ao criar ou revisar um Model, verifique:
- Se o nome da tabela contém o schema (`admin.`, `social.`, `public.`, etc).
- Se os relacionamentos estão apontando para as classes corretas dentro dos Packages.
- Se a propriedade `$fillable` está completa e segura.

---

## 5. Exemplo de Implementação Padrão

```php
namespace App\Packages\Domain\Module\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Packages\Domain\Module\Models\RelatedEntity;

class Entity extends Model
{
    // 1. Definição recomendada do Schema + Tabela
    protected $table = 'schema.table_name';

    // 2. Colunas para mass assignment
    protected $fillable = [
        'field_one',
        'field_two',
        'related_entity_id',
        'active'
    ];

    /**
     * 3. Definição de Casts (Laravel 11+)
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * 4. Relacionamento Tipado
     */
    public function relatedEntity(): BelongsTo
    {
        return $this->belongsTo(RelatedEntity::class);
    }
}
```

---

## 6. Sugestão de Organização de Schemas
- `admin`: Usuários, permissões e configurações centrais.
- `audit`: Logs de auditoria e monitoramento.
- `public`: Dados de utilidade geral e tabelas base.
- `[domain]`: Tabelas específicas de cada domínio de negócio.
