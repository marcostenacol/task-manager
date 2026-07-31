<?php

namespace App\Base\Traits;

use App\Models\Log\ApplicationLog;
use Exception;

use function App\Helpers\userObject;

trait HandlerLog
{
    public static function registerLog($exception, $message, $status_code): void
    {
        try {
            //            ApplicationLog::create([
            //                'application' => env('APP_NAME', 'VESTMENTOR'),
            //                'url' => request()->fullUrl(),
            //                'file_name' => $exception->getFile() ?? 'Arquivo não identificado',
            //                'query_params' => count(request()->query()) > 0 ? json_encode(request()->query()) : null,
            //                'request_body' => count(request()->all()) > 0 ? json_encode(request()->all()) : null,
            //                'message' => $message,
            //                'user_ip' => request()->ip(),
            //                'user_id' => userObject()?->id,
            //                'date_exception' => now(),
            //                'status_code' => $status_code
            //            ]);
        } catch (Exception $exception) {
            return;
        }

    }
}
