<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inmo_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->text('description')->nullable();
            
            $table->string('meeting_type', 50)->default('virtual'); // in_person, virtual, phone, property_viewing
            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(30);
            
            $table->unsignedBigInteger('created_by'); // User ID
            $table->foreign('created_by')->references('id')->on('users');

            $table->unsignedBigInteger('host_id')->nullable(); // User ID (Agent)
            $table->foreign('host_id')->references('id')->on('users')->onDelete('set null');
            
            $table->string('location')->nullable();
            
            $table->json('data')->default('{}');
            $table->timestamps();

            $table->index('scheduled_at');
            $table->index('host_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_meetings');
    }
};
