<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla LEADS - Movimientos de interés de un contacto
     * NO guarda datos personales (están en inmo_contacts)
     * Un lead relaciona: contacto → agente → propiedad
     */
    public function up(): void
    {
        Schema::create('inmo_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('inmo_agents')->onDelete('cascade');
            $table->foreignId('contact_id')->constrained('inmo_contacts')->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained('inmo_properties')->nullOnDelete();

            $table->string('status', 50)->default('nuevo'); // nuevo, contactado, visita_agendada, negociacion, cierre_ganado, cierre_perdido
            $table->string('source', 50)->default('web'); // web, whatsapp, phone, referral, etc.
            $table->text('message')->nullable();
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            // Índices
            $table->index('agent_id');
            $table->index('contact_id');
            $table->index('property_id');
            $table->index('status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_leads');
    }
};
