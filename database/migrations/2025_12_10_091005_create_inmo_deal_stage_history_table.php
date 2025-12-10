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
        Schema::create('inmo_deal_stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('inmo_deals')->onDelete('cascade');
            $table->foreignId('stage_id')->constrained('inmo_pipeline_stages'); // if stage deleted, keep history? usually yes, but for consistency cascade or set null. Let's cascade for simplicity or strictly constrain.
            $table->foreignId('pipeline_id')->constrained('inmo_pipelines');
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('exited_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // Helper
            $table->timestamps();
            
            $table->index(['deal_id', 'stage_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmo_deal_stage_history');
    }
};
