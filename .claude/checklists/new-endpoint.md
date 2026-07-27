# Checklist — Novo endpoint

1. [ ] Definir método HTTP e URI seguindo o padrão já usado no domínio (`GET /`, `POST /`, `GET /{id}`, `PUT /{id}`, `DELETE /{id}`, e ações customizadas como `PATCH /{id}/status` em `Task`).
2. [ ] Rota em `routes/api.php`, dentro do grupo correto (`prefix`/`as`), com `->name(...)`.
3. [ ] Middleware `auth.api` (autenticação simples) ou `auth.api:{permissão}` (com ability) — nunca reaproveitar a permissão de outra ação por conveniência (ver `admin.users.*`, uma permissão dedicada por ação).
4. [ ] `FormRequest` dedicado se houver corpo de request com validação (`authorize() => true`, sem `failedValidation()` redundante) — ou `Request::validate([...])` inline só para casos triviais de um campo (ver `TaskController::updateStatus`).
5. [ ] Service dedicado, injetado no método do Controller (não no construtor).
6. [ ] Resource de resposta, `toArray()` só.
7. [ ] Controller: `try/catch` → `self::successResponse(...)` / `self::returnError($e)`.
8. [ ] Teste Feature cobrindo status code, `success`, shape de `data`, e pelo menos um caminho de erro (404/422/403 conforme o caso).
9. [ ] Pint no(s) arquivo(s) alterado(s).
