<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Optimización de índices para escala de 10M+ registros y 100M+ imágenes
     */
    public function up()
    {
        // =====================================================
        // PROPERTIES - Tabla crítica con 10M+ registros
        // =====================================================
        Schema::table('inmo_properties', function (Blueprint $table) {
            
            // ÍNDICE COMPUESTO PRINCIPAL: Búsqueda por ubicación + estado
            // Cubre: WHERE status='published' AND country=? AND state=? AND city=?
            $table->index(['status', 'country', 'state', 'city', 'published_at'], 'idx_props_loc_pub');

            // ÍNDICE COMPUESTO: Publisher + Status (polimórfico)
            $table->index(['publisher_type', 'publisher_id', 'status', 'published_at'], 'idx_props_pub_status');

            // ÍNDICE COMPUESTO: Category + Precio + Ubicación
            $table->index(['category_id', 'status', 'city', 'price'], 'idx_props_cat_city_price');

            // ÍNDICE COMPUESTO: Operation + Publisher
            $table->index(['operation_type', 'publisher_type', 'status', 'price'], 'idx_props_op_pub');

            // ÍNDICE COMPUESTO: Precio + Ubicación
            $table->index(['status', 'city', 'price'], 'idx_props_price_loc');

            // ÍNDICE COMPUESTO: Características (Bedrooms/Bathrooms Virtuales)
            $table->index(['status', 'bedrooms', 'bathrooms', 'price'], 'idx_props_features');

            // ÍNDICE ORDENAMIENTO RECIENTE
            $table->index(['status', 'published_at', 'id'], 'idx_props_recent');
        });

        // =====================================================
        // MEDIA - 100M+ registros
        // =====================================================
        Schema::table('inmo_media', function (Blueprint $table) {
            // Covering index para galerías: evita acceder a la tabla principal si solo necesitas URLs y tipos
            // Note: URL removed from index to avoid key length limit (1071/3072 bytes)
            $table->index(['model_type', 'model_id', 'type', 'position'], 'idx_media_gallery_cover');
            
            // Índice simple para paginación rápida por modelo
            $table->index(['model_type', 'model_id', 'id'], 'idx_media_paging');
        });

        // =====================================================
        // ASSOCIATIONS - Tabla de enlace N:N (Potencialmente enorme)
        // =====================================================
        Schema::table('inmo_associations', function (Blueprint $table) {
            // Covering Indexes Bidireccionales
            
            // "Dame todas las cosas asociadas a A" (incluyendo el tipo de B para filtrar rápido)
            $table->index(['object_type_a', 'object_id_a', 'type', 'object_type_b', 'object_id_b'], 'idx_assoc_A_lookup');
            
            // "Dame todas las cosas asociadas a B" (Reverse Lookup)
            $table->index(['object_type_b', 'object_id_b', 'type', 'object_type_a', 'object_id_a'], 'idx_assoc_B_lookup');
        });

        // =====================================================
        // CONTACTS - 10M+ registros
        // =====================================================
        // =====================================================
        // CONTACTS - 10M+ registros
        // =====================================================
        Schema::table('inmo_contacts', function (Blueprint $table) {
            // 1. Filtrado básico: nombre (para búsquedas rápidas)
            $table->index(['last_name', 'first_name'], 'idx_contacts_name');
            
            // 2. Búsqueda por ubicación + Estado del Lead
            // Ejemplo: "Dame todos los leads nuevos en Madrid"
            $table->index(['country', 'state', 'city', 'lead_status'], 'idx_contacts_loc_status');

            // 3. Segmentación: Lifecycle Stage + Lead Status + Owner
            // Ejemplo: "Mis MQLs que están en progreso"
            $table->index(['owner_id', 'lifecycle_stage', 'lead_status'], 'idx_contacts_segment');

            // 4. Actividad Reciente: Ideal para "Leads calientes" o "Sin contacto reciente"
            // Filtrar por Owner + Last Activity (Desc)
            $table->index(['owner_id', 'last_activity_at'], 'idx_contacts_activity');
            
            // 5. Creación reciente por estado (para dashboard)
            $table->index(['lead_status', 'created_at'], 'idx_contacts_new_leads');

            // 6. Búsqueda directa por email o teléfono (lookup único)
            $table->index('email', 'idx_contacts_email_simple');
            $table->index('mobile', 'idx_contacts_mobile_simple');
        });

        // =====================================================
        // DEALS - Optimización para Pipeline y Reportes
        // =====================================================
        Schema::table('inmo_deals', function (Blueprint $table) {
            // 1. Vista de Pipeline (Kanban): Filtrar por Pipeline y ver todos los stages
            // 'pipeline_id' + 'stage_id' es la query más común.
            // Agregamos 'updated_at' para ordenar por última actividad (aprox) o 'created_at'
            $table->index(['pipeline_id', 'stage_id', 'created_at'], 'idx_deals_kanban');

            // 2. Filtrado avanzado en listas: Pipeline + Stage + Status + Value (amount)
            $table->index(['pipeline_id', 'stage_id', 'status', 'amount'], 'idx_deals_filter_val');
            
            // 3. Filtrado por fecha (Created At - Hoy, Ayer, Semana) dentro de un pipeline
            $table->index(['pipeline_id', 'created_at'], 'idx_deals_date_range');
            
            // 4. Forecast/Proyecciones: Status (Open) + Expected Close Date + Amount
            $table->index(['status', 'expected_close_date', 'amount'], 'idx_deals_forecast_v2');
            
            // 5. Mis Deals: Owner + Status + Sort by Created
            $table->index(['owner_id', 'status', 'created_at'], 'idx_deals_my_work');
        });

        // =====================================================
        // TICKETS - Optimización para Soporte/Tableros
        // =====================================================
        Schema::table('inmo_tickets', function (Blueprint $table) {
            // 1. Tablero de trabajo: Pipeline + Stage + Priority
            $table->index(['pipeline_id', 'stage_id', 'priority'], 'idx_tickets_board_prio');
            
            // 2. Filtrado por Status + Priority + CreatedAt (SLA monitoring)
            $table->index(['status', 'priority', 'created_at'], 'idx_tickets_sla');
            
            // 3. Mis Tickets: Owner + Status + Priority
            $table->index(['owner_id', 'status', 'priority'], 'idx_tickets_my_work');

            // 4. Búsqueda general por Pipeline + Status
            $table->index(['pipeline_id', 'status'], 'idx_tickets_pipe_status');
        });

        // =====================================================
        // BUILDINGS & AGENTS
        // =====================================================
        Schema::table('inmo_buildings', function (Blueprint $table) {
            $table->index(['country', 'state', 'city', 'name'], 'idx_bldgs_loc_name');
        });

        Schema::table('inmo_agents', function (Blueprint $table) {
             $table->index(['status', 'onboarding_status'], 'idx_agents_status_full');
        });
    }

    public function down()
    {
        // Revertir índices en orden inverso
        Schema::table('inmo_properties', function (Blueprint $table) {
            $table->dropIndex('idx_props_loc_pub');
            $table->dropIndex('idx_props_pub_status');
            $table->dropIndex('idx_props_cat_city_price');
            $table->dropIndex('idx_props_op_pub');
            $table->dropIndex('idx_props_price_loc');
            $table->dropIndex('idx_props_features');
            $table->dropIndex('idx_props_recent');
        });

        Schema::table('inmo_media', function (Blueprint $table) {
            $table->dropIndex('idx_media_gallery_cover');
            $table->dropIndex('idx_media_paging');
        });

        Schema::table('inmo_associations', function (Blueprint $table) {
            $table->dropIndex('idx_assoc_A_lookup');
            $table->dropIndex('idx_assoc_B_lookup');
        });

        Schema::table('inmo_contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_name');
            $table->dropIndex('idx_contacts_loc_status');
            $table->dropIndex('idx_contacts_segment');
            $table->dropIndex('idx_contacts_activity');
            $table->dropIndex('idx_contacts_new_leads');
            $table->dropIndex('idx_contacts_email_simple');
            $table->dropIndex('idx_contacts_mobile_simple');
        });

        Schema::table('inmo_deals', function (Blueprint $table) {
            $table->dropIndex('idx_deals_kanban');
            $table->dropIndex('idx_deals_forecast');
            $table->dropIndex('idx_deals_owner');
        });

        Schema::table('inmo_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_board');
            $table->dropIndex('idx_tickets_owner');
        });

        Schema::table('inmo_buildings', function (Blueprint $table) {
            $table->dropIndex('idx_bldgs_loc_name');
        });

        Schema::table('inmo_agents', function (Blueprint $table) {
            $table->dropIndex('idx_agents_status_full');
        });
    }
};
