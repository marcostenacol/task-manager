<?php

namespace App\Packages\Auth\Auth\Controllers;

use App\Base\Http\Controllers\BaseController;
use App\Packages\Auth\Auth\Requests\LoginRequest;
use App\Packages\Auth\Auth\Resources\LoginResource;
use App\Packages\Auth\Auth\Services\LoginService;
use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends BaseController
{
    public function login(LoginRequest $request, LoginService $service): JsonResponse
    {
        try {
            $data = $service->execute(
                $request->input('email'),
                $request->input('password')
            );

            return self::successResponse(
                data: LoginResource::make($data),
                message: 'Login realizado com sucesso.',
                status_code: Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return self::returnError($exception);
        }
    }
}
