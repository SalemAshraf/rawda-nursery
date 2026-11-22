<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['features_ar' => 'array', 'features_en' => 'array', 'published' => 'boolean', 'price_minor' => 'integer'];
    }

    public static function catalog()
    {
        return static::where('published', true)->orderBy('sort')->orderBy('id')->get(['id', 'name_ar', 'name_en', 'description_ar', 'description_en', 'price_minor', 'currency', 'interval', 'features_ar', 'features_en']);
    }
}
