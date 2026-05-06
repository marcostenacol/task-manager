# Backend Testing and TDD Guide

## Objetivo

Testes fazem parte do design do backend. O comportamento esperado deve ser definido antes ou junto da implementacao, nao apenas validado no final.

## Regra Geral

Sempre que viavel, seguir o ciclo:

1. escrever teste falhando;
2. implementar o minimo para passar;
3. refatorar mantendo os testes verdes.

## O Que Priorizar em Testes

Prioridade alta:

- fluxos de negocio em `Service`;
- contratos de resposta de endpoints criticos;
- validacao de requests;
- regras de permissao;
- consultas sensiveis com filtros, ordenacao e paginação.

Prioridade media:

- repositories com consulta complexa;
- jobs criticos;
- integracao entre camadas em fluxos centrais.

## Estrategia por Camada

### Controller

Testar:

- status code;
- formato da resposta;
- validacao falhando;
- autorizacao;
- integracao com service para fluxo principal.

Evitar focar em regra de negocio detalhada aqui.

### Service

Testar:

- caminho feliz;
- guard clauses;
- falhas de regra;
- uso de transacao quando relevante;
- dispatch de job, event ou notification quando aplicavel.

### Repository

Testar quando houver:

- query complexa;
- combinacao de filtros;
- ordenacao customizada;
- paginação;
- comportamento que possa quebrar facil com mudancas de schema.

### Request

Testar:

- campos obrigatorios;
- tipos aceitos;
- limites de tamanho;
- regras customizadas;
- autorizacao quando existir no request.

## O Que Nao Fazer

- nao testar implementacao irrelevante;
- nao duplicar o mesmo teste em varias camadas sem motivo;
- nao escrever teste acoplado demais a detalhes internos se o comportamento observavel for o que importa.

## Checklist de TDD

- o comportamento esperado esta descrito no teste?
- existe teste para sucesso?
- existe teste para falha principal?
- existe teste para validacao ou autorizacao quando necessario?
- a mudanca ficou protegida contra regressao?

## Norte Pratico

Se a tarefa altera comportamento de negocio, comece pelo teste do `Service`.

Se a tarefa altera contrato HTTP, complemente com teste do endpoint.

Se a tarefa altera filtros, ordenacao ou paginacao, cubra tambem o comportamento de consulta.
