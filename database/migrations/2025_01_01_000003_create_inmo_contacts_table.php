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
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 120)->nullable();
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            // Foreign key opcional a users (si el contacto se registra)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Índices para búsqueda rápida
            $table->index('email');
            $table->index('phone');
            $table->index('user_id');

            // Prevenir duplicados por email o phone
            $table->unique(['email', 'phone'], 'unique_contact');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_contacts');
    }
};
