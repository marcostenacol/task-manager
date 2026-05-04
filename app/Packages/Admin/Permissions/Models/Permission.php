<?php

namespace App\Packages\Admin\Permissions\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'admin.permissions';

    protected $fillable = [
        'id',
        'name',
        'description',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }
}
