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
        Schema::table('courses', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['category_id']);
            // Drop the category_id column
            $table->dropColumn('category_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            // Add sub_category_id column with foreign key constraint
            $table->foreignId('sub_category_id')->constrained('course_subcategories')->onDelete('cascade')->after('instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            // Drop the foreign key constraint for sub_category_id
            $table->dropForeign(['sub_category_id']);
            // Drop the sub_category_id column
            $table->dropColumn('sub_category_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            // Add back category_id column with foreign key constraint
            $table->foreignId('category_id')->constrained('courses_categories')->onDelete('cascade')->after('instructor_id');
        });
    }
};
