<?php

namespace App\Packages\Admin\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'admin.settings';

    protected $fillable = [
        'name',
        'value',
        'description',
    ];

    public $timestamps = false;
}
