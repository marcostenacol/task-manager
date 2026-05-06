<?php

namespace App\Packages\Admin\Users\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasUuids, HasFactory, Notifiable;

    protected $table = 'admin.users';

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'role_id',
        'last_status_id',
        'avatar_path',
        'bio',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'role_id' => 'string',
            'last_status_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => DB::selectOne("SELECT admin.generate_password_hash(?) as hash", [$value])->hash,
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Packages\Social\Contacts\Models\UserContact::class, 'user_id');
    }
}
