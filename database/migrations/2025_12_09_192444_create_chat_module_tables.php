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
        // 1. Chats (Rooms)
        Schema::create('inmo_chats', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique(); // For public channels or identifying via socket
            $table->string('type')->default('private'); // 'private', 'group', 'support'
            $table->string('subject')->nullable(); // For group chats or ticket-based chats
            
            // Link to a primary Contact (optional, but requested for CRM context)
            $table->unsignedBigInteger('contact_id')->nullable(); 
            
            $table->timestamps();
            
            $table->index('contact_id');
            $table->index('type');
        });

        // 2. Participants
        Schema::create('inmo_chat_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id'); // The agent/admin user or the user account of the contact
            
            $table->timestamp('last_read_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            $table->unique(['chat_id', 'user_id']);
            
            $table->foreign('chat_id')->references('id')->on('inmo_chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. Messages
        Schema::create('inmo_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id')->nullable(); // Sender. Nullable for system messages.
            
            $table->text('content')->nullable();
            $table->string('type')->default('text'); // 'text', 'image', 'file', 'system'
            $table->json('data')->nullable(); // For file paths, metadata, etc.
            
            $table->timestamp('read_at')->nullable(); // Global read status (or use participants pivot for individual)
            $table->timestamps();

            $table->index(['chat_id', 'created_at']);
            
            $table->foreign('chat_id')->references('id')->on('inmo_chats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inmo_messages');
        Schema::dropIfExists('inmo_chat_participants');
        Schema::dropIfExists('inmo_chats');
    }
};
