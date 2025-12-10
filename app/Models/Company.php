<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAssociations;

class Company extends Model
{
    use HasFactory, HasAssociations;

    protected $table = 'inmo_companies';
    
    /**
     * @OA\Schema(
     *     schema="Company",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="industry", type="string"),
     *     @OA\Property(property="domain", type="string"),
     *     @OA\Property(property="phone", type="string"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
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
