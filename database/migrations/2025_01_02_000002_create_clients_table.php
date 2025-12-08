<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla CLIENTS - Contactos promovidos a clientes
     * NO guarda datos personales (están en inmo_contacts)
     * Referencia a contact_id
     */
    public function up(): void
    {
        Schema::create('inmo_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('inmo_agents')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('inmo_contacts')->onDelete('cascade');
            $table->string('status', 50)->default('active'); // active, inactive
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            // Índices
            $table->index('agent_id');
            $table->index('contact_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_clients');
    }
};
