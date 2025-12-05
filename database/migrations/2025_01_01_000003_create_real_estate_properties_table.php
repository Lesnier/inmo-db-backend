<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title', 255)->nullable();
            $table->string('slug', 255)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('status', 50)->default('draft');
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('real_estate_categories')->onDelete('set null');
            $table->index('agent_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_properties');
    }
};
