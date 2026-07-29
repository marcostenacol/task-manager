<?php

namespace App\Packages\Social\Person\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Social\Person\Requests\ChangePasswordRequest;
use App\Packages\Social\Person\Requests\UpdateAvatarRequest;
use App\Packages\Social\Person\Requests\UpdatePersonRequest;
use App\Packages\Social\Person\Services\ChangePasswordService;
use App\Packages\Social\Person\Services\DetailPersonService;
use App\Packages\Social\Person\Services\UpdateOrCreateAvatarService;
use App\Packages\Social\Person\Services\UpdatePersonService;
use Illuminate\Http\JsonResponse;

class PersonController extends Controller
{
    use Response;

    public function __construct(
        private DetailPersonService $detailPersonService,
        private UpdatePersonService $updatePersonService,
        private UpdateOrCreateAvatarService $updateOrCreateAvatarService,
        private ChangePasswordService $changePasswordService,
    ) {}

    public function show(): JsonResponse
    {
        try {
            $user = userObject();
            $data = $this->detailPersonService->execute($user->id);

            return self::successResponse($data, 'Perfil recuperado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(UpdatePersonRequest $request): JsonResponse
    {
        try {
            $user = userObject();
            $data = $this->updatePersonService->execute($user->id, $request->validated());

            return self::successResponse($data, 'Perfil atualizado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $user = userObject();
            $this->changePasswordService->execute(
                $user->id,
                $request->validated('current_password'),
                $request->validated('new_password')
            );

            return self::successResponse(null, 'Senha alterada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function avatar(UpdateAvatarRequest $request): JsonResponse
    {
        try {
            $user = userObject();
            $url = $this->updateOrCreateAvatarService->execute($user->id, $request->file('avatar'));

            return self::successResponse(['avatar_url' => $url], 'Avatar atualizado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
