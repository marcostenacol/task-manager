<?php

if (! function_exists('app_storage_path')) {

    function appStoragePath($path = ''): string {
        return storage_path('app/' . $path);
    }
}
