<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $table = 'real_estate_plans';

    protected $fillable = [
        'name',
        'price',
        'period_days',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
