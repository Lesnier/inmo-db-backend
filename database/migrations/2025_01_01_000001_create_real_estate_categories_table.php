<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->nullable()->unique();
            $table->json('data')->default('{}');
            $table->timestamps();
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_categories');
    }
};
