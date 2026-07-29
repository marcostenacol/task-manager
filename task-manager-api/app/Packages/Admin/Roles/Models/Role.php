<?php

namespace App\Packages\Admin\Roles\Models;

use App\Packages\Admin\Permissions\Models\Permission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'admin.roles';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'slug',
        'level',
        'color',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'level' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'admin.role_has_permissions',
            'role_id',
            'permission_id'
        );
    }
}
