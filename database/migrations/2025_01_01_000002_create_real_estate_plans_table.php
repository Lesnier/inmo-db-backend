<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_estate_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140);
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('period_days')->default(30);
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_plans');
    }
};
