<?php

namespace App\Base\Traits;

use Illuminate\Support\Str;

trait HasLinks
{
    public function links(): array
    {
        $class = Str::plural(mb_strtolower(class_basename($this)));
        return [
            [
                'rel' => 'self',
                'href' => route("{$class}.show", $this),
                'method' => 'GET',
            ],
            [
                'rel' => 'update',
                'href' => route("{$class}.update", $this),
                'method' => 'PUT',
            ]
        ];
    }
}
