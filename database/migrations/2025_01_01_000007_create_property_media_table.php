<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->string('type', 30)->default('image');
            $table->string('url', 1024);
            $table->json('meta')->default(DB::raw('(JSON_OBJECT())'));
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('inmo_properties')->onDelete('cascade');
            $table->index('property_id');
            $table->index(['property_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_media');
    }
};
