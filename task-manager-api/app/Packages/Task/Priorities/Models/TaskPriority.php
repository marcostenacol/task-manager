<?php

namespace App\Packages\Task\Priorities\Models;

use Illuminate\Database\Eloquent\Model;

class TaskPriority extends Model
{
    protected $table = 'public.task_priorities';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'order',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }
}
