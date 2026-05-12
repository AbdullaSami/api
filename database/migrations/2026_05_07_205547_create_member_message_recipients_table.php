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
        Schema::create('member_message_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('message_id')->constrained('member_messages')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('deleted_by_recipient')->default(false);
            $table->boolean('deleted_by_sender')->default(false);
            $table->boolean('recipient_archived')->default(false);
            $table->boolean('sender_archived')->default(false);
            $table->boolean('started')->default(false);
            $table->boolean('important')->default(false);
            $table->boolean('muted')->default(false);
            $table->timestamps();
            $table->index(['recipient_id', 'is_read'], 'msg_recipients_read_idx');
            $table->index(['recipient_id', 'deleted_by_recipient'], 'msg_recipients_deleted_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_message_recipients');
    }
};
