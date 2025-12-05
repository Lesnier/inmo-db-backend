<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'real_estate_categories';

    protected $fillable = [
        'name',
        'slug',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'category_id');
    }
}
