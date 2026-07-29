<?php

namespace App\Packages\Admin\Settings\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\Settings\Requests\UpdateSettingRequest;
use App\Packages\Admin\Settings\Resources\SettingResource;
use App\Packages\Admin\Settings\Services\ListSettingsService;
use App\Packages\Admin\Settings\Services\UpdateSettingService;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    use Response;

    public function index(ListSettingsService $service): JsonResponse
    {
        try {
            $data = $service->execute();

            return self::successResponse(SettingResource::collection($data), 'Configurações recuperadas com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }

    public function update(string $id, UpdateSettingRequest $request, UpdateSettingService $service): JsonResponse
    {
        try {
            $admin = userObject();
            $data = $service->execute($id, $request->validated('value'), $admin->id);

            return self::successResponse(SettingResource::make($data), 'Configuração atualizada com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
