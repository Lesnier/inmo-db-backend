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
    public function agent()
    {
        return $this->belongsTo(\App\Models\Agent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
