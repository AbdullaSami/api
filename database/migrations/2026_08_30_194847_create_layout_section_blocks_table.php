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
        Schema::create('layout_section_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_section_id')->constrained('layout_sections')->onDelete('cascade');
            $table->string('type');
            $table->unsignedInteger('order')->default(0);
            $table->json('content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['layout_section_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_section_blocks');
    }
};
