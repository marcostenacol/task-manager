<?php

namespace App\Packages\Admin\Roles\Models;

use App\Packages\Admin\Permissions\Models\Permission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
