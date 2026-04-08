<?php

namespace App\Base\Traits;

use App\Services\Educational\Courses\SyncShiftService;
use Illuminate\Support\Str;

trait HasSlug
{

    public static function bootHasSlug()
    {
        static::creating(function ($model) {
            $model->slug = $model->generateSlug();
        });

        static::updating(function ($model) {
            if ($model->isDirty('name')) {
                $model->slug = $model->generateSlug();
            }
        });
    }

    protected function generateSlug(): string
    {
        $slugFrom = $this->slugFrom ?? 'name';
        $slugColumn = $this->slugColumn ?? 'slug';
        $slug = Str::slug($this->$slugFrom);
        $originalSlug = $slug;
        $count = 1;
        while (static::where($slugColumn, $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
