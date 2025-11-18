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
        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->numeric('amount');
            $table->foreign('token_wallet_id')->references('id')->on('token_wallets')->onDelete('cascade');
            $table->enum('transaction_type', ['send','receive']);
            $table->enum('status', ['sent', 'received', 'failed']);
            $table->foreign('sender_member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('receive_member_id')->references('id')->on('members')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_transactions');
    }
};
