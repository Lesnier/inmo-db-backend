<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('property_id');
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('real_estate_properties')->onDelete('cascade');
            $table->unique(['user_id', 'property_id']);
            $table->index('user_id');
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_favorites');
    }
};
