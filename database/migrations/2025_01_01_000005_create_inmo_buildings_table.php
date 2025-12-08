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

            // Owner / administrator of the building
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('agent_id')->references('id')->on('inmo_agents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

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

            // Structure
            $table->integer('year_built')->nullable();
            $table->integer('floors')->nullable();

            // Flexible metadata
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));

            $table->timestamps();

            // Indexes
            $table->index(['agent_id', 'user_id']);
            $table->index(['city', 'slug']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inmo_buildings');
    }
};
