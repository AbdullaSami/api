<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_payout_batches', function (Blueprint $table) {
            $table->id();
            $table->timestamp('window_start')->nullable();
            $table->timestamp('window_end')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing');
            $table->unsignedInteger('total_commissions')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['window_start', 'window_end']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payout_batches');
    }
};
