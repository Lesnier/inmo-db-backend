<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inmo_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('entity_type', ['deal', 'ticket'])->default('deal');           $table->timestamps();

            $table->index('entity_type');
        });

        Schema::create('inmo_pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('inmo_pipelines')->onDelete('cascade');
            $table->string('name');
            $table->integer('position')->default(0);
            $table->integer('probability')->default(0); // 0-100
            $table->timestamps();

            $table->index(['pipeline_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_pipeline_stages');
        Schema::dropIfExists('inmo_pipelines');
    }
};
