<?php

namespace App\Packages\Auth\Auth\Controllers;

use App\Base\Http\Controllers\BaseController;
use App\Packages\Auth\Auth\Requests\RegisterRequest;
use App\Packages\Auth\Auth\Resources\RegisterResource;
use App\Packages\Auth\Auth\Services\RegisterService;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RegisterController extends BaseController
{
    public function register(RegisterRequest $request, RegisterService $service): JsonResponse
    {
        try {
            $user = $service->execute($request->validated());

            return self::successResponse(
                data: RegisterResource::make($user),
                message: 'Usuário registrado com sucesso.',
                status_code: Response::HTTP_CREATED
            );
        } catch (Exception $exception) {
            return self::returnError($exception);
        }
    }
}
