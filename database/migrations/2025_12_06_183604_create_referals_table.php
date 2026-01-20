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
        Schema::create('referals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('members', 'id')->onDelete('cascade');
            $table->enum('commission_type' , ['direct' , 'binary']);
            $table->enum('leg', ['left','right']);
            $table->foreignId('referral_id')->nullable()->constrained('members' , 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referals');
    }
};
