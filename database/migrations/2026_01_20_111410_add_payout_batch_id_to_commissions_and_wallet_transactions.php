<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->foreignId('payout_batch_id')
                ->nullable()
                ->constrained('commission_payout_batches')
                ->nullOnDelete();

            $table->timestamp('withdrawn_at')->nullable()->index();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->foreignId('payout_batch_id')
                ->nullable()
                ->constrained('commission_payout_batches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payout_batch_id');
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->dropIndex(['withdrawn_at']);
            $table->dropColumn('withdrawn_at');
            $table->dropConstrainedForeignId('payout_batch_id');
        });
    }
};
