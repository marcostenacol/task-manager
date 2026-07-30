<?php

namespace App\Packages\Task\Tasks\Models;

use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'public.tasks';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'organization_id',
        'visibility',
        'status_id',
        'priority_id',
        'title',
        'description',
        'due_date',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'organization_id' => 'string',
            'status_id' => 'string',
            'priority_id' => 'string',
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TaskPriority::class, 'priority_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
