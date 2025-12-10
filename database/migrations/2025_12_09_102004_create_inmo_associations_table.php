<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inmo_associations', function (Blueprint $table) {
            $table->id();
            
            // Object A
            $table->string('object_type_a', 50);
            $table->unsignedBigInteger('object_id_a');
            
            // Object B
            $table->string('object_type_b', 50);
            $table->unsignedBigInteger('object_id_b');
            
            // Association Type
            $table->string('type', 50)->default('related');

            $table->timestamps();

            // Indexes for bidirectional lookups
            $table->index(['object_type_a', 'object_id_a'], 'idx_assoc_a');
            $table->index(['object_type_b', 'object_id_b'], 'idx_assoc_b');
            
            // Unique constraint to prevent duplicate same-type relations
            $table->unique(['object_type_a', 'object_id_a', 'object_type_b', 'object_id_b', 'type'], 'idx_assoc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_associations');
    }
};
