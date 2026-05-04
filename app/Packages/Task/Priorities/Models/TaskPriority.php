<?php

namespace App\Packages\Task\Priorities\Models;

use Illuminate\Database\Eloquent\Model;

class TaskPriority extends Model
{
    protected $table = 'public.task_priorities';

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
