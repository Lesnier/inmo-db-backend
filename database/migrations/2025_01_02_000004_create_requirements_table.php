<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('inmo_clients')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('inmo_agents')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active'); // active, fulfilled, cancelled
            $table->json('data'); // DTO for requirement characteristics (tipo, categoria, precio_min, precio_max, metros_min, metros_max, ubicacion, etc.)
            $table->timestamps();

            $table->index('client_id');
            $table->index('agent_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_requirements');
    }
};
