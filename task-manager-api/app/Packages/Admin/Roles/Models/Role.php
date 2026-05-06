<?php

namespace App\Packages\Admin\Roles\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Role extends Model
{
    use HasUuids;

    protected $table = 'admin.roles';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }
}
