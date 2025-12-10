<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasPublisher;

class Building extends Model
{
    use HasFactory, HasPublisher, \App\Traits\HasAssociations;

    // Duplicate traits removed
    
    /**
     * @OA\Schema(
     *     schema="Building",
     *     type="object",
     *     @OA\Property(property="id", type="integer"),
     *     @OA\Property(property="name", type="string"),
     *     @OA\Property(property="address", type="string"),
     *     @OA\Property(property="floors", type="integer"),
     *     @OA\Property(property="year_built", type="integer"),
     *     @OA\Property(property="lat", type="number", format="float"),
     *     @OA\Property(property="lng", type="number", format="float"),
     *     @OA\Property(property="created_at", type="string", format="date-time"),
     *     @OA\Property(property="updated_at", type="string", format="date-time")
     * )
     */
    protected $table = 'inmo_buildings';

    protected $fillable = [
        'publisher_id',
        'publisher_type',
        'name',
        'slug',
        'address',
        'country',
        'state',
        'city',
        'district',
        'zip_code',
        'lat',
        'lng',
        'location',
        'year_built',
        'floors',
        'data',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'year_built' => 'integer',
        'floors' => 'integer',
        'data' => 'array',
    ];

    /**
     * Properties in this building
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'building_id');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }
    // user() and agent() methods removed as columns don't exist. 
    // Use publisher() relation and agent attribute.
}
