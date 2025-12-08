<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla PROPERTIES - Propiedades inmobiliarias
     * Depende de: inmo_agents, inmo_categories, inmo_buildings (opcional)
     */
    public function up(): void
    {
        Schema::create('inmo_properties', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('agent_id')->nullable()->constrained('inmo_agents')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('inmo_categories')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('inmo_buildings')->nullOnDelete();

            // Tipo de operación y oferta
            $table->string('operation_type', 50)->default('sell'); // sell, rent
            $table->string('type_of_offer', 50)->default('private_person'); // private_person, real_estate_agent

            // Datos básicos
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->decimal('price', 12, 2);
            $table->string('currency', 10)->default('EUR');

            // Estado y publicación
            $table->string('status', 50)->default('draft'); // draft, published, archived
            $table->timestamp('published_at')->nullable();

            // Ubicación
            $table->string('country', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('district', 120)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('street_address', 255)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Datos adicionales (JSON)
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));

            $table->timestamps();

            // Índices básicos
            $table->index('agent_id');
            $table->index('category_id');
            $table->index('building_id');
            $table->index('status');
            $table->index('published_at');
            $table->index('operation_type');
            $table->index('type_of_offer');
            $table->index(['country', 'state', 'city']);
            $table->index(['lat', 'lng']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_properties');
    }
};
