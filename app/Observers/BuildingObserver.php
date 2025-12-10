<?php

namespace App\Observers;

use App\Models\Building;
use Illuminate\Support\Facades\DB;

class BuildingObserver
{
    public function saving(Building $building): void
    {
        if ($building->lat && $building->lng) {
            if (DB::getDriverName() !== 'sqlite') {
                $building->location = DB::raw("ST_GeomFromText('POINT({$building->lng} {$building->lat})')");
            }
        }
    }
}
