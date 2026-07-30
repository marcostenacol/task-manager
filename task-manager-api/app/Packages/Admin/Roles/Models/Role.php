<?php

namespace App\Packages\Admin\Roles\Models;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Permissions\Models\Permission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'scope',
        'organization_id',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'level' => 'integer',
            'organization_id' => 'string',
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

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
