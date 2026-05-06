# Arquitetura Técnica - Backend Modular Laravel

Esta arquitetura foi desenhada para sistemas escaláveis que exigem alta performance, manutenibilidade e separação clara de domínios. Ela utiliza uma abordagem modular inspirada em Domain-Driven Design (DDD).

## 1. Visão Geral e Estrutura de Pastas

A lógica de negócio é organizada por domínios dentro de `app/Packages`, separando o núcleo do sistema das funcionalidades específicas.

### Organização do Diretório `app/`
- **`Base/`**: O "Framework Interno". Contém abstrações reutilizáveis:
  - `Traits/Response`: Padronização de saídas JSON.
  - `Repository/BaseRepository`: Métodos Eloquent genéricos.
  - `Helpers/`: Funções globais de strings, data, SQL, etc.
- **`Packages/`**: O coração do projeto. Cada subdiretório representa um domínio de negócio:
  - `Controllers/`: Orquestração HTTP.
  - `Services/`: Lógica de negócio (Padrão de método único).
  - `Repositories/`: Persistência isolada (Uso de CTEs/SQL puro).
  - `Resources/`: Transformação de dados (Payloads enxutos).
- **`Http/`**: Middlewares globais e tratamento de exceções.
- **`Models/`**: Definição de entidades e relacionamentos (usando Schemas do PostgreSQL).

## 2. Padrão Controller-Service-Repository

Todas as requisições devem seguir este ciclo de vida rigoroso:

### Camada de Controller
- **Regra**: Não contém lógica de negócio nem consultas ao banco.
- **Função**: Injeta o Service, passa os dados validados e retorna uma resposta padronizada via `BaseController`.

### Camada de Service
- **Regra**: Cada Service realiza apenas UMA ação (Single Responsibility).
- **Nomeclatura**: `Acao + Entidade + Service` (ex: `StoreOrderService`).
- **Padrão**: Deve conter um método público `execute()`.

### Camada de Repository
- **Regra**: Isolar o acesso ao banco.
- **Performance**: Consultas complexas que envolvem múltiplos relacionamentos ou cálculos devem ser implementadas usando **Common Table Expressions (CTEs)** do PostgreSQL via Eloquent `DB::select` ou `fromRaw`.

## 3. Estratégia de Banco de Dados (PostgreSQL)

O sistema utiliza múltiplos **Schemas** para organizar a estrutura de dados, garantindo isolamento lógico e segurança.

- **Schemas Padrões Sugeridos**:
  - `admin.`: Usuários, permissões, logs de auditoria.
  - `social.`: Pessoas, endereços, contatos.
  - `public.`: Tokens de acesso, migrações.
  - `[dominio].`: Tabelas específicas do módulo.

### Lógica em Banco (Opcional/Crítica)
Para operações de segurança máxima (auth) ou performance extrema, delegue para funções PL/pgSQL.

## 4. Cache e Performance (Redis)

O uso de Cache é **obrigatório** para listagens recorrentes e dados de configuração.
- Utilize a `CacheTrait` para padronizar o tempo de vida e as chaves.
- Crie `Cache Helpers` dentro de cada módulo para abstrair se o dado vem do banco ou do Redis.

## 5. Roteamento Modular

As rotas são carregadas dinamicamente:
- `routes/api.php` realiza um `glob` em `routes/api/v1/*.php`.
- Isso permite que cada módulo declare suas próprias rotas sem poluir o arquivo principal.

---
> [!TIP]
> Esta arquitetura permite que múltiplos times trabalhem em módulos diferentes sem conflitos, mantendo uma base de código uniforme e previsível.
