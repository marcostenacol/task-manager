<?php

namespace App\Base\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait AuditLog {

    /**
     * @return void
     */
    public static function setConfigs(): void {
        DB::statement("select set_config('application.user_id', ?, false)", [Auth::id() ?? 1]);
        DB::statement("select set_config('application.user_ip', ?, false)", [request()->ip()]);
    }
}
