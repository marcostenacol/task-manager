<?php

namespace App\Packages\Task\Tasks\Services;

use App\Base\Traits\Response;
use App\Packages\Admin\Organizations\Services\ResolveOrganizationScopeService;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Tasks\Models\Task;

class GuardTaskAccessService
{
    use Response;

    public function __construct(
        private ResolveOrganizationScopeService $resolveOrganizationScopeService,
    ) {}

    /**
     * Dono sempre pode. Task de organization (visibility='organization') pode ser
     * vista/editada por qualquer membro de uma organization do escopo do ator
     * (a própria + descendentes — ver ResolveOrganizationScopeService) ou por
     * um ator global (Owner/Admin veem tasks de organization de qualquer
     * organization, nunca tasks pessoais de terceiros). Exclusão fica restrita
     * ao dono ou a um ator global — evita um membro apagar o trabalho de
     * outro por engano.
     */
    public function guardCanAccess(Task $task, string $actor_id, bool $require_owner = false): void
    {
        if ($task->user_id === $actor_id) {
            return;
        }

        if ($task->visibility === 'organization') {
            $actor = User::with('role')->findOrFail($actor_id);
            $actor_is_global = $actor->global_role_id !== null || $actor->role->scope === 'global';

            if ($actor_is_global) {
                return;
            }

            $organization_ids = $this->resolveOrganizationScopeService->execute($actor_id);

            if (! $require_owner && $task->organization_id !== null && in_array($task->organization_id, $organization_ids ?? [], true)) {
                return;
            }
        }

        self::notAuthorizeExceptionResponse(
            message: 'Recurso não encontrado.',
            status_code: 404
        );
    }
}
