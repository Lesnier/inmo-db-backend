<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_proposal_properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('inmo_proposals')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('inmo_properties')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['proposal_id', 'property_id']);
            $table->index('proposal_id');
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_proposal_properties');
    }
};
