<?php

namespace App\Observers;

use App\Models\Property;
use Illuminate\Support\Facades\Cache;

class PropertyObserver
{
    public function saving(Property $property): void
    {
        if (empty($property->slug)) {
            $property->slug = \Illuminate\Support\Str::slug($property->title) . '-' . time();
        }

        if ($property->lat && $property->lng) {
             // Use DB::raw to set geometry
             // In Laravel for Point insertion we usually need DB::raw
             // But 'saving' is tricky because we are operating on the model instance before save.
             // We can use a mutator on the Model or DB::raw in the query, OR use the `grimzy/laravel-mysql-spatial` package.
             // Without package: We need to set it as an expression. Since Laravel 10/11 supports raw binds more easily.
             
             // Simplest Native way without packages:
             // $property->location = DB::raw("ST_GeomFromText('POINT({$property->lng} {$property->lat})')");
             // However, DB::raw inside model attributes can be tricky on 'create'.
             
             // Better: Let's assume the user will install a package OR we use a setter on the model.
             // Let's rely on the Model to cast/set. If not using a package, we have to do it manually in Controller or here.
             
             // For this codebase, I see no spatial package. I will use DB::raw.
             if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                 $property->location = \Illuminate\Support\Facades\DB::raw("ST_GeomFromText('POINT({$property->lng} {$property->lat})')");
             }
        }
    }

    public function saved(Property $property): void
    {
        $this->clearCache($property);
    }

    public function deleted(Property $property): void
    {
        $this->clearCache($property);
    }

    protected function clearCache(Property $property)
    {
        // 1. Clear Detail Cache
        Cache::tags(["property_{$property->id}"])->flush();

        // 2. Clear Search Results Cache
        // Since search results are tagged with generic 'properties' tag, we flush it.
        // This is aggressive but ensures consistency. 
        Cache::tags(['properties'])->flush();

        // 3. Clear Favorites lists? 
        // Favorites are cached by user tags (e.g. user_{id}_favorites). We don't track which users favorited this efficiently here without pivot query.
        // However, favorite LISTS are just IDs usually. If the property data changes, the list might not need update unless it embeds data.
        // Assuming favorite list endpoint returns property data:
        // Ideally we should flush users who favorited this.
        // For now, let's keep it simple. If critical, we can query inmo_favorites.
    }
}
