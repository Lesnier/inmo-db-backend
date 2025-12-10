<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Support\Facades\Cache;

class PropertySearchService
{
    /**
     * Search properties with Bounding Box and caching.
     *
     * @param array $filters Additional filters (category_id, price range, etc.)
     * @param array|null $bbox [sw_lat, sw_lng, ne_lat, ne_lng]
     * @param int $zoom Zoom level for normalization
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(array $filters, ?array $bbox = null, int $zoom = 12)
    {
        // 1. Generate Cache Key
        $cacheParams = [
            'zoom' => $zoom,
            'filters' => $filters,
        ];

        if ($bbox) {
             // Round to 3 decimals
            $precision = 3;
            $swLatNormalized = round($bbox['sw_lat'], $precision);
            $swLngNormalized = round($bbox['sw_lng'], $precision);
            $neLatNormalized = round($bbox['ne_lat'], $precision);
            $neLngNormalized = round($bbox['ne_lng'], $precision);
            
            $cacheParams['bbox_norm'] = [$swLatNormalized, $swLngNormalized, $neLatNormalized, $neLngNormalized];
        }
        
        $cacheKey = 'search_' . sha1(json_encode($cacheParams));

        // 3. Cache-Aside Pattern
        // Cache TTL from config (default 60 minutes)
        $ttl = config('inmo.search_cache_ttl', 60);
        
        return Cache::tags(['properties'])->remember($cacheKey, now()->addMinutes($ttl), function () use ($bbox, $filters) {
            
            $query = Property::query()->published();

            // Apply Geospatial Scope ONLY if BBox is present
            // Apply Geospatial Scope ONLY if BBox is present
            if ($bbox) {
                // $query->whereWithinBoundingBox($bbox['sw_lat'], $bbox['sw_lng'], $bbox['ne_lat'], $bbox['ne_lng']);
                // Use Spatial Index via scope
                $query->whereSpatialBBox($bbox['sw_lat'], $bbox['sw_lng'], $bbox['ne_lat'], $bbox['ne_lng']);
            }

            // Apply Filters
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (!empty($filters['operation_type'])) {
                $query->where('operation_type', $filters['operation_type']);
            }

            if (!empty($filters['min_price'])) {
                $query->where('price', '>=', $filters['min_price']);
            }

            if (!empty($filters['max_price'])) {
                $query->where('price', '<=', $filters['max_price']);
            }
            
            if (!empty($filters['bedrooms'])) {
                 // Assuming bedrooms is stored in JSON data column or extracted
                 // If using extracted index:
                 //$query->where('bedrooms', '>=', $filters['bedrooms']);
                 // If using JSON:
                 $query->where('data->general->bedrooms', '>=', $filters['bedrooms']);
            }

            if (!empty($filters['bathrooms'])) {
                 $query->where('data->general->bathrooms', '>=', $filters['bathrooms']);
            }

            // Relationship Eager Loading
            $query->with(['category', 'media', 'building']);

            return $query->get();
        });
    }
}
