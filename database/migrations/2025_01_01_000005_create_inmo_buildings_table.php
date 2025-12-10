<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::create('inmo_buildings', function (Blueprint $table) {
            $table->id();
            
            // Publisher (User)
            $table->foreignId('publisher_id')->constrained('users')->onDelete('cascade');
            $table->string('publisher_type', 50);

            // Basic building info
            $table->string('name');
            $table->string('slug')->unique();

            // Location
            $table->string('address')->nullable();
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('zip_code', 20)->nullable();

            // Map coordinates
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            
            if (DB::getDriverName() !== 'sqlite') {
                $table->point('location')->nullable(); 
            }



            // Structure
            $table->integer('year_built')->nullable();
            $table->integer('floors')->nullable();

            // Flexible metadata
            $table->json('data')->default('{}');

            $table->timestamps();

            // Indexes
            $table->index(['publisher_id', 'publisher_type']);
            $table->index(['city', 'slug']);
            $table->index(['country', 'state', 'city']);
            
            // if (DB::getDriverName() !== 'sqlite') {
            //    $table->spatialIndex('location');
            // }
        });
    }

    public function down()
    {
        Schema::dropIfExists('inmo_buildings');
    }
};
