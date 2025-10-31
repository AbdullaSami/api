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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->on('wallets')->onDelete('cascade');
            $table->enum('transaction_type', ['withdrawal', 'deposit', 'send_internal_transfer', 'receive_internal_transfer' , 'direct_credit' , 'buy_package']);
            $table->decimal('amount', 8, 2);
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->integer('sender_member_id')->nullable();
            $table->integer('receive_member_id')->nullable();
            $table->integer('credit_code')->nullable();
            $table->string('package_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
