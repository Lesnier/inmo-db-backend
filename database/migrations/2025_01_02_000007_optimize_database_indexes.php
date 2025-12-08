<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Optimización de índices para escala de 10M+ propiedades y 100M+ media
     *
     * Estrategia:
     * 1. Índices compuestos para consultas WHERE comunes
     * 2. Índices de cobertura (covering indexes) para evitar lookups
     * 3. Índices espaciales para búsquedas geográficas
     * 4. Índices de ordenamiento
     */
    public function up()
    {
        // =====================================================
        // PROPERTIES - Tabla crítica con 10M+ registros
        // =====================================================

        // DROP índices simples existentes de la migración base que serán reemplazados por compuestos
        // NOTA: No se pueden eliminar índices de foreign keys (agent_id, category_id, building_id)
        // DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_agent_id_index');
        // DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_category_id_index');
        // DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_building_id_index');
        DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_status_index');
        DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_published_at_index');
        DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_operation_type_index');
        DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_type_of_offer_index');
        DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_country_state_city_index');
        DB::statement('ALTER TABLE inmo_properties DROP INDEX IF EXISTS inmo_properties_lat_lng_index');

        Schema::table('inmo_properties', function (Blueprint $table) {

            // ÍNDICE COMPUESTO PRINCIPAL: Búsqueda de propiedades publicadas por ubicación
            // Cubre: WHERE status='published' AND country=? AND state=? AND city=?
            // Uso: Listado principal de propiedades por ciudad
            $table->index(['status', 'country', 'state', 'city', 'published_at'], 'idx_properties_location_published');

            // ÍNDICE COMPUESTO: Búsqueda por agente + status
            // Cubre: WHERE agent_id=? AND status=?
            // Uso: Dashboard del agente, "Mis Propiedades"
            $table->index(['agent_id', 'status', 'published_at'], 'idx_properties_agent_status');

            // ÍNDICE COMPUESTO: Búsqueda por building + status
            // Cubre: WHERE building_id=? AND status=?
            // Uso: Propiedades en un edificio específico
            $table->index(['building_id', 'status'], 'idx_properties_building');

            // ÍNDICE COMPUESTO: Búsqueda por categoría + status + ubicación
            // Cubre: WHERE category_id=? AND status=? AND city=?
            // Uso: "Apartamentos en Madrid"
            $table->index(['category_id', 'status', 'city', 'price'], 'idx_properties_category_city');

            // ÍNDICE COMPUESTO: Búsqueda por tipo de operación + oferta
            // Cubre: WHERE operation_type=? AND type_of_offer=? AND status=?
            // Uso: "Propiedades en venta de agentes"
            $table->index(['operation_type', 'type_of_offer', 'status', 'price'], 'idx_properties_operation_type');

            // ÍNDICE COMPUESTO: Rango de precios + ubicación
            // Cubre: WHERE status=? AND city=? AND price BETWEEN ? AND ?
            // Uso: Filtro de precio en búsqueda
            $table->index(['status', 'city', 'price', 'published_at'], 'idx_properties_price_range');

            // ÍNDICE PARA ORDENAMIENTO: Propiedades más recientes
            // Cubre: ORDER BY published_at DESC
            // Ya existe published_at simple, agregar compuesto con status
            $table->index(['status', 'published_at', 'id'], 'idx_properties_recent');
        });

        // ÍNDICE ESPACIAL se creará en la migración 15 usando columna POINT virtual
        // Los SPATIAL INDEX requieren columnas tipo POINT, no DECIMAL separadas

        // =====================================================
        // MEDIA - Tabla MUY grande con 100M+ registros
        // =====================================================

        // DROP índices existentes que serán optimizados (si existen)
        DB::statement('ALTER TABLE inmo_media DROP INDEX IF EXISTS inmo_media_property_id_position_index');

        Schema::table('inmo_media', function (Blueprint $table) {

            // ÍNDICE COMPUESTO: property_id + type + position
            // Cubre: WHERE property_id=? AND type=? ORDER BY position
            // Uso: Galería de imágenes de una propiedad
            // NOTA: No incluimos 'url' porque VARCHAR(1024) excede el límite de 3072 bytes del índice
            $table->index(['property_id', 'type', 'position'], 'idx_media_property_gallery');

            // ÍNDICE para búsqueda por tipo específico
            // Cubre: WHERE property_id=? AND type=?
            // Uso: "Solo videos de esta propiedad"
            $table->index(['property_id', 'type', 'id'], 'idx_media_by_type');

            // ÍNDICE para paginación eficiente
            // Cubre: WHERE property_id > ? LIMIT ?
            // Uso: Carga lazy de imágenes
            $table->index(['id', 'property_id'], 'idx_media_pagination');
        });

        // =====================================================
        // BUILDINGS - Búsquedas por ubicación
        // =====================================================

        // DROP índice existente (si existe)
        DB::statement('ALTER TABLE inmo_buildings DROP INDEX IF EXISTS inmo_buildings_city_slug_index');

        Schema::table('inmo_buildings', function (Blueprint $table) {

            // ÍNDICE COMPUESTO: Búsqueda de edificios por ubicación
            // Cubre: WHERE city=? AND state=?
            $table->index(['country', 'state', 'city', 'id'], 'idx_buildings_location');

            // ÍNDICE: Búsqueda por agente
            // Cubre: WHERE agent_id=?
            $table->index(['agent_id', 'created_at'], 'idx_buildings_agent');

            // ÍNDICE para slug único (búsqueda rápida)
            // Ya existe unique en slug, OK
        });

        // ÍNDICE ESPACIAL para buildings se creará en la migración 15 con columna POINT virtual
        // Los SPATIAL INDEX requieren columnas tipo POINT, no DECIMAL separadas

        // =====================================================
        // FAVORITES - Tabla de relación N:N
        // =====================================================
        Schema::table('inmo_favorites', function (Blueprint $table) {
            // Ya tiene unique(user_id, property_id) - OK
            // Agregar índice inverso para búsquedas bidireccionales
            $table->index(['property_id', 'user_id', 'created_at'], 'idx_favorites_property_users');
        });

        // =====================================================
        // AGENTS - Búsquedas por status
        // =====================================================
        Schema::table('inmo_agents', function (Blueprint $table) {
            // ÍNDICE COMPUESTO: status + onboarding_status
            // Cubre: WHERE status='approved' AND onboarding_status=?
            $table->index(['status', 'onboarding_status', 'plan_id'], 'idx_agents_status');

            // ÍNDICE para plan_id (agentes por plan)
            $table->index(['plan_id', 'status'], 'idx_agents_plan');
        });

        // =====================================================
        // LEADS - CRM queries
        // =====================================================
        Schema::table('inmo_leads', function (Blueprint $table) {
            // ÍNDICE COMPUESTO: agent + status + fecha
            // Cubre: WHERE agent_id=? AND status=? ORDER BY created_at
            $table->index(['agent_id', 'status', 'created_at'], 'idx_leads_agent_status');

            // ÍNDICE: property_id (leads de una propiedad)
            $table->index(['property_id', 'created_at'], 'idx_leads_property');

            // ÍNDICE: contact_id (leads de un contacto)
            $table->index(['contact_id', 'agent_id'], 'idx_leads_contact');

            // ÍNDICE para source (análisis de fuentes)
            $table->index(['source', 'created_at'], 'idx_leads_source');
        });

        // =====================================================
        // CLIENTS - CRM queries
        // =====================================================
        Schema::table('inmo_clients', function (Blueprint $table) {
            // ÍNDICE COMPUESTO: agent + status
            // Cubre: WHERE agent_id=? AND status='active'
            $table->index(['agent_id', 'status', 'created_at'], 'idx_clients_agent_status');

            // ÍNDICE: contact_id
            $table->index(['contact_id', 'agent_id'], 'idx_clients_contact');
        });

        // =====================================================
        // CONTACTS - Esta tabla no tiene columnas email/phone individuales
        // Los datos están en el campo JSON 'data'
        // No se requieren índices adicionales
        // =====================================================

        // =====================================================
        // ACTIVITIES - Calendario y tareas
        // =====================================================
        Schema::table('inmo_activities', function (Blueprint $table) {
            // ÍNDICE COMPUESTO: agent + status + scheduled_at
            // Cubre: WHERE agent_id=? AND status=? AND scheduled_at >= ?
            // Uso: Calendario de actividades pendientes
            $table->index(['agent_id', 'status', 'scheduled_at'], 'idx_activities_agent_schedule');

            // ÍNDICE: client_id
            $table->index(['client_id', 'created_at'], 'idx_activities_client');

            // ÍNDICE: lead_id
            $table->index(['lead_id', 'created_at'], 'idx_activities_lead');

            // ÍNDICE: type (filtro por tipo de actividad)
            $table->index(['type', 'status', 'scheduled_at'], 'idx_activities_type');
        });

        // =====================================================
        // REQUIREMENTS - Matching de propiedades
        // =====================================================
        Schema::table('inmo_requirements', function (Blueprint $table) {
            // ÍNDICE COMPUESTO: client + status
            $table->index(['client_id', 'status'], 'idx_requirements_client');

            // ÍNDICE: agent + status (requirements activos del agente)
            $table->index(['agent_id', 'status', 'created_at'], 'idx_requirements_agent');
        });

        // =====================================================
        // PROPOSALS - Propuestas compartidas
        // =====================================================
        Schema::table('inmo_proposals', function (Blueprint $table) {
            // ÍNDICE: share_token (búsqueda por token público)
            $table->index(['share_token'], 'idx_proposals_token');

            // ÍNDICE COMPUESTO: agent + status
            $table->index(['agent_id', 'status', 'created_at'], 'idx_proposals_agent');

            // ÍNDICE: client_id
            $table->index(['client_id', 'created_at'], 'idx_proposals_client');
        });

        // =====================================================
        // PROPOSAL_PROPERTIES - Pivot table
        // =====================================================
        Schema::table('inmo_proposal_properties', function (Blueprint $table) {
            // ÍNDICE COMPUESTO: proposal_id + order
            // Cubre: WHERE proposal_id=? ORDER BY order
            $table->index(['proposal_id', 'order'], 'idx_proposal_props_order');

            // ÍNDICE inverso: property_id (propuestas que incluyen esta propiedad)
            $table->index(['property_id', 'proposal_id'], 'idx_proposal_props_property');
        });

        // =====================================================
        // CATEGORIES - Pequeña, pero búsquedas frecuentes
        // =====================================================
        Schema::table('inmo_categories', function (Blueprint $table) {
            // ÍNDICE en slug para búsqueda por URL
            $table->index(['slug'], 'idx_categories_slug');
        });

        // =====================================================
        // PLANS - Pequeña tabla
        // =====================================================
        Schema::table('inmo_plans', function (Blueprint $table) {
            // ÍNDICE para ordenar por precio
            $table->index(['price', 'period_days'], 'idx_plans_price');
        });
    }

    public function down()
    {
        // Properties
        Schema::table('inmo_properties', function (Blueprint $table) {
            $table->dropIndex('idx_properties_location_published');
            $table->dropIndex('idx_properties_agent_status');
            $table->dropIndex('idx_properties_building');
            $table->dropIndex('idx_properties_category_city');
            $table->dropIndex('idx_properties_operation_type');
            $table->dropIndex('idx_properties_price_range');
            $table->dropIndex('idx_properties_recent');
        });
        // El índice espacial se maneja en la migración 15

        // Media
        Schema::table('inmo_media', function (Blueprint $table) {
            $table->dropIndex('idx_media_property_gallery');
            $table->dropIndex('idx_media_by_type');
            $table->dropIndex('idx_media_pagination');
        });

        // Buildings
        Schema::table('inmo_buildings', function (Blueprint $table) {
            $table->dropIndex('idx_buildings_location');
            $table->dropIndex('idx_buildings_agent');
        });
        // El índice espacial se maneja en la migración 15

        // Favorites
        Schema::table('inmo_favorites', function (Blueprint $table) {
            $table->dropIndex('idx_favorites_property_users');
        });

        // Agents
        Schema::table('inmo_agents', function (Blueprint $table) {
            $table->dropIndex('idx_agents_status');
            $table->dropIndex('idx_agents_plan');
        });

        // Leads
        Schema::table('inmo_leads', function (Blueprint $table) {
            $table->dropIndex('idx_leads_agent_status');
            $table->dropIndex('idx_leads_property');
            $table->dropIndex('idx_leads_contact');
            $table->dropIndex('idx_leads_source');
        });

        // Clients
        Schema::table('inmo_clients', function (Blueprint $table) {
            $table->dropIndex('idx_clients_agent_status');
            $table->dropIndex('idx_clients_contact');
        });

        // Contacts - No se crearon índices adicionales

        // Activities
        Schema::table('inmo_activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_agent_schedule');
            $table->dropIndex('idx_activities_client');
            $table->dropIndex('idx_activities_lead');
            $table->dropIndex('idx_activities_type');
        });

        // Requirements
        Schema::table('inmo_requirements', function (Blueprint $table) {
            $table->dropIndex('idx_requirements_client');
            $table->dropIndex('idx_requirements_agent');
        });

        // Proposals
        Schema::table('inmo_proposals', function (Blueprint $table) {
            $table->dropIndex('idx_proposals_token');
            $table->dropIndex('idx_proposals_agent');
            $table->dropIndex('idx_proposals_client');
        });

        // Proposal Properties
        Schema::table('inmo_proposal_properties', function (Blueprint $table) {
            $table->dropIndex('idx_proposal_props_order');
            $table->dropIndex('idx_proposal_props_property');
        });

        // Categories
        Schema::table('inmo_categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_slug');
        });

        // Plans
        Schema::table('inmo_plans', function (Blueprint $table) {
            $table->dropIndex('idx_plans_price');
        });

        // Recrear índices originales
        Schema::table('inmo_properties', function (Blueprint $table) {
            $table->index('operation_type');
            $table->index('type_of_offer');
            $table->index(['country', 'state', 'city']);
        });

        Schema::table('inmo_media', function (Blueprint $table) {
            $table->index(['property_id', 'position']);
        });

        Schema::table('inmo_buildings', function (Blueprint $table) {
            $table->index(['city', 'slug']);
        });
    }
};
