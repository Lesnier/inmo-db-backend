<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Company extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_companies';

    protected $fillable = [
        'name',
        'industry',
        'domain',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
