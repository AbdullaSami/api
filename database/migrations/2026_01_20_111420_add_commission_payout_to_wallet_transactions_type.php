<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE wallet_transactions MODIFY transaction_type ENUM(".
                "'withdrawal','deposit','send_internal_transfer','receive_internal_transfer','direct_credit','buy_package','commission_payout'".
                ") NOT NULL"
            );

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(
                "ALTER TABLE wallet_transactions DROP CONSTRAINT IF EXISTS wallet_transactions_transaction_type_check"
            );
            DB::statement(
                "ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_transaction_type_check CHECK (transaction_type IN (".
                "'withdrawal','deposit','send_internal_transfer','receive_internal_transfer','direct_credit','buy_package','commission_payout'".
                "))"
            );

            return;
        }

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::create('wallet_transactions_tmp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->on('wallets')->onDelete('cascade');
                $table->enum('transaction_type', ['withdrawal', 'deposit', 'send_internal_transfer', 'receive_internal_transfer', 'direct_credit', 'buy_package', 'commission_payout']);
                $table->decimal('amount', 8, 2);
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->integer('sender_member_id')->nullable();
                $table->integer('receive_member_id')->nullable();
                $table->integer('credit_code')->nullable();
                $table->string('package_name')->nullable();
                $table->foreignId('payout_batch_id')->nullable()->constrained('commission_payout_batches')->nullOnDelete();
                $table->timestamps();
            });

            DB::statement(
                "INSERT INTO wallet_transactions_tmp (id, wallet_id, transaction_type, amount, status, sender_member_id, receive_member_id, credit_code, package_name, payout_batch_id, created_at, updated_at) " .
                "SELECT id, wallet_id, transaction_type, amount, status, sender_member_id, receive_member_id, credit_code, package_name, payout_batch_id, created_at, updated_at FROM wallet_transactions"
            );

            Schema::drop('wallet_transactions');
            Schema::rename('wallet_transactions_tmp', 'wallet_transactions');

            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE wallet_transactions MODIFY transaction_type ENUM(".
                "'withdrawal','deposit','send_internal_transfer','receive_internal_transfer','direct_credit','buy_package'".
                ") NOT NULL"
            );

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement(
                "ALTER TABLE wallet_transactions DROP CONSTRAINT IF EXISTS wallet_transactions_transaction_type_check"
            );
            DB::statement(
                "ALTER TABLE wallet_transactions ADD CONSTRAINT wallet_transactions_transaction_type_check CHECK (transaction_type IN (".
                "'withdrawal','deposit','send_internal_transfer','receive_internal_transfer','direct_credit','buy_package'".
                "))"
            );

            return;
        }

        if ($driver === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::create('wallet_transactions_tmp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained()->on('wallets')->onDelete('cascade');
                $table->enum('transaction_type', ['withdrawal', 'deposit', 'send_internal_transfer', 'receive_internal_transfer', 'direct_credit', 'buy_package']);
                $table->decimal('amount', 8, 2);
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->integer('sender_member_id')->nullable();
                $table->integer('receive_member_id')->nullable();
                $table->integer('credit_code')->nullable();
                $table->string('package_name')->nullable();
                $table->foreignId('payout_batch_id')->nullable()->constrained('commission_payout_batches')->nullOnDelete();
                $table->timestamps();
            });

            DB::statement(
                "INSERT INTO wallet_transactions_tmp (id, wallet_id, transaction_type, amount, status, sender_member_id, receive_member_id, credit_code, package_name, payout_batch_id, created_at, updated_at) " .
                "SELECT id, wallet_id, transaction_type, amount, status, sender_member_id, receive_member_id, credit_code, package_name, payout_batch_id, created_at, updated_at FROM wallet_transactions WHERE transaction_type != 'commission_payout'"
            );

            Schema::drop('wallet_transactions');
            Schema::rename('wallet_transactions_tmp', 'wallet_transactions');

            Schema::enableForeignKeyConstraints();
        }
    }
};
