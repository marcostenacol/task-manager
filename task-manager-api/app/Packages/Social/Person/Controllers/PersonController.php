<?php

namespace App\Packages\Social\Person\Controllers;

use App\Http\Controllers\Controller;
use App\Base\Traits\Response;
use App\Packages\Social\Person\Services\DetailPersonService;
use App\Packages\Social\Person\Services\UpdatePersonService;
use App\Packages\Social\Person\Services\UpdateOrCreateAvatarService;
use App\Packages\Social\Person\Requests\UpdatePersonRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PersonController extends Controller
{
    use Response;

    public function show(): JsonResponse
    {
        try {
            $user = userObject();
            $data = app(DetailPersonService::class)->execute($user->id);
            return self::successResponse($data, 'Perfil recuperado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(UpdatePersonRequest $request): JsonResponse
    {
        try {
            $user = userObject();
            $data = app(UpdatePersonService::class)->execute($user->id, $request->validated());
            return self::successResponse($data, 'Perfil atualizado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function avatar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'avatar' => 'required|image|max:2048', // 2MB
            ]);

            $user = userObject();
            $url = app(UpdateOrCreateAvatarService::class)->execute($user->id, $request->file('avatar'));
            
            return self::successResponse(['avatar_url' => $url], 'Avatar atualizado com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
