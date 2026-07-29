<?php

namespace App\Packages\Admin\AuditLogs\Controllers;

use App\Base\Traits\Response;
use App\Http\Controllers\Controller;
use App\Packages\Admin\AuditLogs\Services\ListAuditLogsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use Response;

    public function __construct(
        private ListAuditLogsService $listAuditLogsService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $data = $this->listAuditLogsService->execute($request->all());

            return self::successResponse($data, 'Logs de auditoria recuperados com sucesso.');
        } catch (\Exception $e) {
            return self::returnError($e);
        }
    }
}
