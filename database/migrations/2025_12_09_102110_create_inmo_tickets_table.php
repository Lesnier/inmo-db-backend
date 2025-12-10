<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inmo_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->string('type', 50)->default('support'); // requirement, support, incident
            $table->string('priority', 50)->default('medium'); // low, medium, high, urgent
            $table->string('status', 50)->default('open'); // open, pending, resolved, closed
            
            $table->foreignId('pipeline_id')->nullable()->constrained('inmo_pipelines')->nullOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('inmo_pipeline_stages')->nullOnDelete();
            
            $table->unsignedBigInteger('owner_id')->nullable(); // User ID
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
            
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            $table->index('type');
            $table->index('priority');
            $table->index('status');
            $table->index('owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_tickets');
    }
};
