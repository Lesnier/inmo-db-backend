<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, approved, suspended
            $table->string('onboarding_status')->default('incomplete'); // incomplete, complete
            $table->foreignId('plan_id')->nullable()->constrained('inmo_plans')->nullOnDelete();
            $table->json('data'); // DTO for flexible agent information
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_agents');
    }
};
