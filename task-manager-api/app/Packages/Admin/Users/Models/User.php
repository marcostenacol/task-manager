<?php

namespace App\Packages\Admin\Users\Models;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use App\Packages\Social\Contacts\Models\UserContact;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

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
            'deleted_at' => 'datetime',
        ];
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => DB::selectOne('SELECT admin.generate_password_hash(?) as hash', [$value])->hash,
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function lastStatus(): BelongsTo
    {
        return $this->belongsTo(UserStatus::class, 'last_status_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(UserContact::class, 'user_id');
    }
}
