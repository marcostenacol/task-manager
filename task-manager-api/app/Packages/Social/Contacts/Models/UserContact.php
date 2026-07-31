<?php

namespace App\Packages\Social\Contacts\Models;

use App\Packages\Admin\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContact extends Model
{
    use HasUuids;

    protected $table = 'social.user_contacts';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'value',
        'is_primary',
    ];

    protected $casts = [
        'id' => 'string',
        'user_id' => 'string',
        'is_primary' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
