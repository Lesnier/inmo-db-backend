<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up()
    {
        /**
         * ============================================================
         * 1. FULLTEXT INDEXES (MariaDB compatible)
         * ============================================================
         */

        DB::statement("DROP INDEX IF EXISTS idx_properties_search_title ON inmo_properties");
        DB::statement("ALTER TABLE inmo_properties ADD FULLTEXT idx_properties_search_title (title)");

        DB::statement("DROP INDEX IF EXISTS idx_properties_search_full ON inmo_properties");
        DB::statement("
            ALTER TABLE inmo_properties
            ADD FULLTEXT idx_properties_search_full (
                title,
                street_address,
                city,
                district
            )
        ");

        /**
         * ============================================================
         * 2. ÍNDICES COMPUESTOS
         * ============================================================
         */

        DB::statement("DROP INDEX IF EXISTS idx_properties_operation_city ON inmo_properties");
        DB::statement("DROP INDEX IF EXISTS idx_properties_operation_category_city ON inmo_properties");
        DB::statement("DROP INDEX IF EXISTS idx_properties_district ON inmo_properties");
        DB::statement("DROP INDEX IF EXISTS idx_properties_zipcode ON inmo_properties");

        Schema::table('inmo_properties', function (Blueprint $table) {
            // Index for: Search by operation + location +/- publisher type (e.g. "Rent in Madrid from Agents")
            $table->index(['operation_type', 'status', 'city', 'price', 'publisher_type'], 'idx_properties_operation_city');
            
            // Index for: Deep search with category
            $table->index(['operation_type', 'category_id', 'status', 'city', 'price', 'publisher_type'], 'idx_properties_op_cat_city');
            
            $table->index(['city', 'district', 'status', 'price'], 'idx_properties_district');
            $table->index(['zip_code', 'status', 'price'], 'idx_properties_zipcode');
        });

        /**
         * ============================================================
         * 3. GEO: PROPERTIES → POINT NOT NULL + TRIGGERS + SPATIAL INDEX
         * ============================================================
         */

        // 1. Eliminar índice previo si existe
        DB::statement("DROP INDEX IF EXISTS idx_properties_location_point ON inmo_properties");

        // 2. Crear columna POINT con DEFAULT temporal (necesario para MariaDB)
        if (!Schema::hasColumn('inmo_properties', 'location')) {
            DB::statement("ALTER TABLE inmo_properties ADD COLUMN location POINT NOT NULL DEFAULT POINT(0,0)");
        }

        // 3. Actualizar valores existentes
        DB::statement("UPDATE inmo_properties SET location = POINT(lng, lat)");

        // 4. Quitar DEFAULT (MariaDB no permite DEFAULT en POINT)
        DB::statement("ALTER TABLE inmo_properties MODIFY COLUMN location POINT NOT NULL");

        // 5. Crear triggers
        DB::statement("DROP TRIGGER IF EXISTS trg_properties_insert");
        DB::statement("DROP TRIGGER IF EXISTS trg_properties_update");

        DB::unprepared("
            CREATE TRIGGER trg_properties_insert
            BEFORE INSERT ON inmo_properties
            FOR EACH ROW
            BEGIN
                SET NEW.location = POINT(NEW.lng, NEW.lat);
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_properties_update
            BEFORE UPDATE ON inmo_properties
            FOR EACH ROW
            BEGIN
                SET NEW.location = POINT(NEW.lng, NEW.lat);
            END
        ");

        // 6. Crear SPATIAL INDEX (solo posible ahora que es NOT NULL)
        DB::statement("
            ALTER TABLE inmo_properties
            ADD SPATIAL INDEX idx_properties_location_point (location)
        ");

        /**
         * ============================================================
         * 4. GEO: BUILDINGS → MISMA LÓGICA QUE PROPERTIES
         * ============================================================
         */

        DB::statement("DROP INDEX IF EXISTS idx_buildings_location_point ON inmo_buildings");

        if (!Schema::hasColumn('inmo_buildings', 'location')) {
            DB::statement("ALTER TABLE inmo_buildings ADD COLUMN location POINT NOT NULL DEFAULT POINT(0,0)");
        }

        DB::statement("UPDATE inmo_buildings SET location = POINT(lng, lat)");

        DB::statement("ALTER TABLE inmo_buildings MODIFY COLUMN location POINT NOT NULL");

        DB::statement("DROP TRIGGER IF EXISTS trg_buildings_insert");
        DB::statement("DROP TRIGGER IF EXISTS trg_buildings_update");

        DB::unprepared("
            CREATE TRIGGER trg_buildings_insert
            BEFORE INSERT ON inmo_buildings
            FOR EACH ROW
            BEGIN
                SET NEW.location = POINT(NEW.lng, NEW.lat);
            END
        ");

        DB::unprepared("
            CREATE TRIGGER trg_buildings_update
            BEFORE UPDATE ON inmo_buildings
            FOR EACH ROW
            BEGIN
                SET NEW.location = POINT(NEW.lng, NEW.lat);
            END
        ");

        DB::statement("
            ALTER TABLE inmo_buildings
            ADD SPATIAL INDEX idx_buildings_location_point (location)
        ");

        /**
         * ============================================================
         * 5. CONTACTS INDEX
         * ============================================================
         */

        DB::statement("DROP INDEX IF EXISTS idx_contacts_fullname ON inmo_contacts");

        Schema::table('inmo_contacts', function (Blueprint $table) {
            $table->index(['first_name', 'last_name'], 'idx_contacts_fullname');
        });
    }

    public function down()
    {
        /**
         * FULLTEXT
         */
        DB::statement("DROP INDEX IF EXISTS idx_properties_search_title ON inmo_properties");
        DB::statement("DROP INDEX IF EXISTS idx_properties_search_full ON inmo_properties");

        /**
         * GEO properties
         */
        DB::statement("DROP INDEX IF EXISTS idx_properties_location_point ON inmo_properties");
        DB::statement("DROP TRIGGER IF EXISTS trg_properties_insert");
        DB::statement("DROP TRIGGER IF EXISTS trg_properties_update");
        DB::statement("ALTER TABLE inmo_properties DROP COLUMN location");

        /**
         * GEO buildings
         */
        DB::statement("DROP INDEX IF EXISTS idx_buildings_location_point ON inmo_buildings");
        DB::statement("DROP TRIGGER IF EXISTS trg_buildings_insert");
        DB::statement("DROP TRIGGER IF EXISTS trg_buildings_update");
        DB::statement("ALTER TABLE inmo_buildings DROP COLUMN location");

        /**
         * Composite indexes
         */
        Schema::table('inmo_properties', function (Blueprint $table) {
            $table->dropIndex('idx_properties_operation_city');
            $table->dropIndex('idx_properties_op_cat_city');
            $table->dropIndex('idx_properties_district');
            $table->dropIndex('idx_properties_zipcode');
        });

        /**
         * Contacts
         */
        Schema::table('inmo_contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_fullname');
        });
    }
};
