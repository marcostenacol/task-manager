<?php

namespace App\Base\Traits;

use Illuminate\Http\Request;

trait Resource
{
    public static function checkAttributes(Request $request, $attribute): bool
    {
        if (! $request->has('attributes')) {
            return true;
        }

        return collect(explode(',', $request->get('attributes')))->contains($attribute);
    }
}
