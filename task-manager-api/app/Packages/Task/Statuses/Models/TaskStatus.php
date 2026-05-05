<?php

namespace App\Packages\Task\Statuses\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    protected $table = 'public.task_statuses';

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
