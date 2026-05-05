<?php

namespace App\Packages\Auth\Auth\Controllers;

use App\Base\Http\Controllers\BaseController;
use App\Packages\Auth\Auth\Resources\LoginResource;
use App\Packages\Auth\Auth\Services\ProcessRefreshTokenService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenController extends BaseController
{
    public function refresh(Request $request, ProcessRefreshTokenService $service): JsonResponse
    {
        try {
            $data = $service->execute($request->input('refresh_token'));

            return self::successResponse(
                data: LoginResource::make($data),
                message: 'Token atualizado com sucesso.',
                status_code: Response::HTTP_OK
            );
        } catch (Exception $exception) {
            return self::returnError($exception);
        }
    }
}
