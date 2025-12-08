<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('inmo_agents')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('inmo_clients')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('enviada'); // enviada, vista, aceptada, rechazada
            $table->string('share_token')->unique(); // For sharing via link
            $table->json('data'); // DTO for additional proposal information
            $table->timestamps();

            $table->index('agent_id');
            $table->index('client_id');
            $table->index('share_token');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_proposals');
    }
};
