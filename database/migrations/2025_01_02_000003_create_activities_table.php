<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('inmo_agents')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('inmo_clients')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('inmo_leads')->nullOnDelete();
            $table->string('type'); // visita, reunion, llamada, whatsapp, mensaje_app, agendado
            $table->string('title');
            $table->text('notes')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->json('data'); // DTO for additional activity information
            $table->timestamps();

            $table->index('agent_id');
            $table->index('client_id');
            $table->index('lead_id');
            $table->index('scheduled_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_activities');
    }
};
