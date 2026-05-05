<?php

namespace App\Packages\Auth\Auth\Controllers;

use App\Base\Http\Controllers\BaseController;
use App\Packages\Auth\Auth\Services\LogoutService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends BaseController
{
    public function logout(Request $request, LogoutService $service): JsonResponse
    {
        try {
            $token = handlerRequestToken($request->bearerToken());
            $service->execute($token);

            return self::successResponse(
                message: 'Logout realizado com sucesso.',
                status_code: Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return self::returnError($exception);
        }
    }
}
