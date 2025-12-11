<?php

use App\Models\Building;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Buildings for UTF-8 issues...\n";

try {
    $building = Building::find(5);
    if ($building) {
        echo "Testing JSON encode for Building 5...\n";
        $json = json_encode($building->toArray());
        
        if ($json === false) {
            echo "FAILED: " . json_last_error_msg() . "\n";
        } else {
            echo "SUCCESS: Building 5 encoded successfully.\n";
            echo "Location field present in JSON? " . (strpos($json, 'location') !== false ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "Building 5 not found.\n";
    }

} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
