<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('real_estate_properties')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('agent_id')->references('id')->on('users')->onDelete('set null');
            $table->index('property_id');
            $table->index('user_id');
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_contacts');
    }
};
