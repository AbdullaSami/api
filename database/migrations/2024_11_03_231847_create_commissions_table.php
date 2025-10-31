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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sponsor_id')->constrained('members', 'id')->onDelete('cascade'); 
            $table->decimal('commission_value', 10, 2); 
            $table->enum('commission_type' , ['direct' , 'binary']); 
            $table->foreignId('referral_id')->nullable()->constrained('members' , 'id')->onDelete('cascade');  
            $table->boolean('withdrawn')->default(false); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
