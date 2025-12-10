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

            // Publisher (User)
            $table->foreignId('publisher_id')->constrained('users')->onDelete('cascade');
            $table->string('publisher_type', 50); // role name (agent, agency, private, etc)

            $table->foreignId('category_id')->nullable()->constrained('inmo_categories')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('inmo_buildings')->nullOnDelete();

            // Tipo de operación
            $table->string('operation_type', 50)->default('sell'); // sell, rent

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
            
            if (DB::getDriverName() !== 'sqlite') {
                 $table->point('location')->nullable(); 
            }



            // Datos adicionales (JSON)
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));

            // Columnas VIRTUALES GENERADAS (para indexación eficiente)
            // Se usa json_extract para mayor compatibilidad con MariaDB ver. antigua
            $table->unsignedTinyInteger('bedrooms')->virtualAs("json_unquote(json_extract(data, '$.general.bedrooms'))")->nullable();
            $table->unsignedTinyInteger('bathrooms')->virtualAs("json_unquote(json_extract(data, '$.general.bathrooms'))")->nullable();

            $table->timestamps();

            // Índices básicos
            $table->index(['publisher_id', 'publisher_type']);
            $table->index('category_id');
            $table->index('building_id');
            $table->index('status');
            $table->index('published_at');
            $table->index('operation_type');
            $table->index(['country', 'state', 'city']);
            $table->index(['lat', 'lng']);
            
            // Spatial Index (MySQL Only)
            // if (DB::getDriverName() !== 'sqlite') {
            //    $table->spatialIndex('location');
            // }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_properties');
    }
};
