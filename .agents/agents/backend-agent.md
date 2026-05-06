# Backend Agent

## Papel

Este e o arquivo principal de orientacao para qualquer tarefa de backend neste projeto. Ao receber uma solicitacao de backend, leia este arquivo primeiro.

Objetivo:

- manter consistencia arquitetural;
- reduzir gasto de token com contexto repetido;
- indicar onde pesquisar antes de editar;
- garantir que a implementacao siga o padrao do monolito.

## Leitura Obrigatoria Inicial

Antes de propor ou editar qualquer codigo de backend, consulte nesta ordem:

1. `.agents/docs/backend/STANDARDS.md`
2. `.agents/docs/backend/architecture-summary.md`
3. `.agents/docs/backend/controllers.md`
4. `.agents/docs/backend/index.md`

Se a tarefa envolver uma area especifica, siga tambem a trilha de pesquisa descrita abaixo.

## Fluxo Arquitetural Obrigatorio

Toda implementacao de backend deve respeitar este fluxo:

`Request HTTP -> Controller -> Service -> Repository -> Model`

Regras permanentes:

- `Controller` valida, delega e formata resposta.
- `Service` centraliza regra de negocio e orquestracao.
- `Repository` centraliza persistencia e consultas.
- `Model` representa estrutura, relacionamentos, casts e scopes.

## Como Decidir Onde Mexer

Use estas regras para localizar a camada correta:

- validacao de entrada: `app/Http/Requests`
- formato de saida JSON: `app/Http/Resources`
- fluxo HTTP e resposta: `app/Http/Controllers`
- regra de negocio e orquestracao: `app/Services`
- consulta, filtro, ordenacao e persistencia: `app/Repositories`
- relacoes, casts e scopes: `app/Models`
- processamento assincrono: `app/Jobs`
- autorizacao: `app/Policies`
- regra de validacao customizada: `app/Rules`
- estrutura do banco: `database/migrations`
- dados iniciais e apoio de banco: `database/seeders`

## Onde Pesquisar por Tipo de Tarefa

### 1. Nova rota ou endpoint

Leia nesta ordem:

1. `routes/api.php`
2. arquivos em `routes/api/` ou padrao equivalente existente no projeto
3. controller relacionado
4. request relacionado
5. resource relacionado
6. service relacionado
7. repository relacionado
8. model relacionado

Valide tambem:

- versionamento da API;
- nome da rota;
- formato padrao de resposta;
- status code correto.

### 2. Regra de negocio

Leia nesta ordem:

1. service relacionado
2. repositories envolvidos
3. models envolvidos
4. requests/resources que entram ou saem do fluxo
5. jobs, events ou notifications relacionados, se existirem

Nunca implemente regra de negocio em controller, model ou repository.

### 3. Banco de dados

Se a tarefa envolver tabela, coluna, relacionamento, filtro, seed, estrutura ou impacto em dados, leia nesta ordem:

1. `.agents/docs/backend/database.md`
2. `database/migrations/*.php`
3. `database/seeders/*.php`
4. model relacionado
5. repository relacionado
6. service que usa esse dado

Sempre confirme no codigo real:

- nomes de tabelas;
- nullable;
- defaults;
- foreign keys;
- cascade;
- dados base exigidos por seeders.

### 4. Listagem de dados

Leia nesta ordem:

1. rota de listagem
2. controller `index`
3. request ou parametros aceitos
4. repository da listagem
5. resource da colecao

Sempre verificar:

- paginacao obrigatoria quando aplicavel;
- filtros via query params;
- ordenacao via `sort`;
- `meta` e `links` fora de `data`.

### 5. Bugfix

Leia e siga nesta ordem:

1. ponto de entrada da requisicao
2. request
3. controller
4. service
5. repository
6. model
7. migration/seeder se houver suspeita de problema estrutural ou dado inconsistente

Corrija o bug na camada correta. Nao mova logica para uma camada errada so para resolver mais rapido.

### 6. Review de backend

Verifique principalmente:

- violacao da arquitetura em camadas;
- controller com logica de negocio;
- service acessando model diretamente;
- repository com logica de negocio;
- resposta fora do padrao JSON;
- status code incorreto;
- ausencia de validacao;
- falta de paginação em listagens;
- ausencia de teste ou cobertura inadequada.

## Regras Curtas Inviolaveis

- nunca acessar `Model` diretamente no `Controller`;
- nunca acessar `Model` diretamente no `Service`;
- nunca colocar regra de negocio no `Repository`;
- nunca formatar resposta no `Service`;
- nunca retornar erro tecnico interno ao cliente;
- sempre usar constante de status HTTP do Laravel;
- sempre preferir dados validados do `FormRequest`;
- sempre procurar primeiro o fluxo existente antes de criar arquivo novo;
- sempre limpar o cache (`clearCache`) em Services que invalidam tokens ou alteram estados sensíveis;
- sempre delegar validação de expiração e segurança de tokens às funções PL/pgSQL;
- sempre usar `interval '1 minute'` no passado ao invalidar timestamps no banco para garantir detecção imediata.

## Checklist Antes de Finalizar

- a camada alterada esta correta?
- a validacao esta em `FormRequest`?
- a regra de negocio esta no `Service`?
- a persistencia esta no `Repository`?
- a resposta usa `Resource` ou padrao consistente?
- o status code esta correto?
- a API segue versionamento e contrato de resposta?
- se houver listagem, ela esta paginada?
- se houver impacto em banco, migration e seeder foram analisados?
- existe teste ou ao menos criterio claro de validacao do comportamento?

## Referencias

- `.agents/docs/backend/STANDARDS.md`
- `.agents/docs/backend/architecture-summary.md`
- `.agents/docs/backend/controllers.md`
- `.agents/docs/backend/index.md`
- `.agents/docs/backend/database.md`

## Agentes Especializados

Use quando a tarefa for mais especifica:

- banco de dados: `.agents/agents/backend-db-agent.md`
- CRUD e endpoints: `.agents/agents/backend-crud-agent.md`
- review: `.agents/agents/backend-review-agent.md`
