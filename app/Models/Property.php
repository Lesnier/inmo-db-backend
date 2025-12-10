<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Traits\HasPublisher;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Property extends Model
{
    use HasFactory, HasPublisher, \App\Traits\HasAssociations;

    protected $table = 'inmo_properties';

    protected $fillable = [
        'publisher_id',
        'publisher_type',
        'category_id',
        'building_id',
        'operation_type',
        'title',
        'slug',
        'price',
        'currency',
        'status',
        'published_at',
        'country',
        'state',
        'city',
        'district',
        'zip_code',
        'street_address',
        'lat',
        'lng',
        'location',
        'data',
    ];

    protected $casts = [
        'data' => \App\Casts\PropertyDataCast::class,
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }



    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inmo_favorites', 'property_id', 'user_id');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    public function scopeWhereWithinBoundingBox($query, $swLat, $swLng, $neLat, $neLng)
    {
        return $query->whereBetween('lat', [$swLat, $neLat])
                     ->whereBetween('lng', [$swLng, $neLng]);
    }

    public function scopeWhereSpatialBBox($query, $swLat, $swLng, $neLat, $neLng)
    {
        // Construct Polygon from BBox (SW -> NW -> NE -> SE -> SW)
        // Order: Lng Lat
        // SW: $swLng $swLat
        // NW: $swLng $neLat
        // NE: $neLng $neLat
        // SE: $neLng $swLat
        // Close: $swLng $swLat
        
        $polygon = "POLYGON(($swLng $swLat, $swLng $neLat, $neLng $neLat, $neLng $swLat, $swLng $swLat))";
        
        // MBRContains(poly, point) -> efficient usage of SPATIAL index in MySQL
        return $query->whereRaw("MBRContains(ST_GeomFromText(?), location)", [$polygon]);
    }
}
