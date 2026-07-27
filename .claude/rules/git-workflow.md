# Git workflow

## Antes de qualquer mudança

Sempre `git fetch origin` antes de criar branch, editar arquivo ou abrir PR — não confiar no estado local sem fetch.

## Branches remotas reais observadas

```
main
homolog
TASK-REEST-reestruturacao
```

`main` é a branch default (`origin/HEAD -> origin/main`). **Não há `production` como branch ativa** neste repositório (diferente de outros projetos AE3 — confirme antes de assumir esse nome). Fluxo provável, a partir das branches existentes: `feature → homolog → main`, mas isso não foi confirmado por nenhum PR real lido neste harness — trate como inferência, não fato observado, e confirme com o time antes de assumir a política de destino de PR.

Toda branch nova nasce de `origin/main`, a menos que o usuário peça outra base:

```bash
git fetch origin
git checkout -b nome-da-branch origin/main
```

Não foi observado um prefixo de ticket único e consistente nos nomes de branch reais (`TASK-REEST-reestruturacao` é o único nome de branch de feature visível no histórico local) — confirme com o time antes de inventar um prefixo `AE3-`/`EB-`/`GIZ-` que não existe neste projeto.

## Mensagens de commit

Histórico real (`git log --oneline`) é heterogêneo:

```
feat: add profile dropdown menu, background UI dev mode, and Nuxt 4 page restructure
chore: atualiza configurações de ambiente (Makefile e docker-compose)
fix: refatoração da camada de serviços e ajustes de paginação no frontend
[SECURITY] fix(global): correções de segurança e qualidade
[REEST] feat(tasks): finalização do módulo de tarefas com filtros e kanban
[TASK-REEST] feat(frontend): implementação do módulo de autenticação e organização srcDir
```

Duas convenções coexistem: **Conventional Commits puro** (`feat:`, `fix:`, `chore:`) nos commits mais recentes, e um prefixo entre colchetes por fase/marco de projeto (`[REEST]`, `[SECURITY]`, `[CONFIG]`, `[TASK-REEST]`) nos commits anteriores. Os commits mais recentes do histórico já não usam mais o prefixo entre colchetes — para commits novos, **prefira Conventional Commits puro** (`feat:`, `fix:`, `chore:`, `test:`, `docs:`, com `(escopo)` opcional), seguindo a tendência mais recente, a menos que o usuário peça explicitamente um prefixo de ticket.

Nunca se coloque como co-autor nos commits.

## Pull Requests

Nenhum template de PR real (`.github/`) foi encontrado neste harness — se for necessário abrir PR, use a estrutura genérica de "o que foi feito / por que / como testar" até que um template real seja confirmado no repositório.
