<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla CONTACTS - Entidad base universal de contactos
     * Un contacto puede convertirse en Lead o Client
     * Evita duplicación de datos personales
     */
    public function up(): void
    {
        Schema::create('inmo_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // If linked to a login user
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            
            // Lifecycle Stage (subscriber, lead, mql, sql, opportunity, customer, evangelist)
            $table->string('lifecycle_stage', 50)->default('subscriber');
            $table->string('lead_status', 50)->default('new'); // New, Open, In Progress, etc.
            
            // Address Fields for filtering
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('zip_code', 20)->nullable();

            // Owner (Explicit)
            $table->unsignedBigInteger('owner_id')->nullable(); 
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamp('last_activity_at')->nullable();
            
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            // Foreign key opcional a users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Índices
            $table->index('email');
            $table->index('user_id');
            $table->index('lifecycle_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_contacts');
    }
};
