<?php

namespace App\Packages\Admin\UserStatuses\Models;

use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    protected $table = 'admin.user_statuses';

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
