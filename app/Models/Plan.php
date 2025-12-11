<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $table = 'inmo_plans';

    protected $fillable = [
        'name',
        'price',
        'period_days',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
