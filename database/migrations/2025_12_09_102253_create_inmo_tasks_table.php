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
        Schema::create('inmo_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            
            $table->dateTime('due_date')->nullable();
            $table->string('status', 50)->default('open'); // open, in_progress, completed, canceled
            $table->string('priority', 50)->default('medium'); // low, medium, high, urgent
            
            $table->unsignedBigInteger('assigned_to')->nullable(); // User ID (Agent)
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');

            $table->unsignedBigInteger('created_by'); // User ID
            $table->foreign('created_by')->references('id')->on('users'); // No cascade to keep history? or cascade? usually keep history.
            
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            $table->index('assigned_to');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_tasks');
    }
};
