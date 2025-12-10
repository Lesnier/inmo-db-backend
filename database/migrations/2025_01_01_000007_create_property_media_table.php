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
            
            // Polymorphic relation
            // model_type = 'property' | 'building' (según MediaModelType)
            $table->unsignedBigInteger('model_id');
            $table->string('model_type', 50);

            $table->string('type', 30)->default('image'); // image, video, doc, plan, 3d_view
            $table->string('url', 1024);
            $table->json('meta')->default(DB::raw('(JSON_OBJECT())'));
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index(['model_type', 'model_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_media');
    }
};
