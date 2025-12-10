<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inmo_activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // call, email, meeting, note, etc
            $table->text('content')->nullable(); // description/notes
            
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            
            // Who performed the activity?
            $table->unsignedBigInteger('created_by'); // User ID
            $table->foreign('created_by')->references('id')->on('users');

            $table->string('status', 50)->default('completed'); // pending, completed
            
            $table->json('data')->default(DB::raw('(JSON_OBJECT())'));
            $table->timestamps();

            $table->index('type');
            $table->index('created_by');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inmo_activities');
    }
};
