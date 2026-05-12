<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_messages', function (Blueprint $table) {


            $table->enum('delivery_type', [
                'direct',
                'upline',
                'downline',
            ])->default('direct');

            $table->enum('tree_side', [
                'left',
                'right',
                'both',
            ])->default('both');

            $table->boolean('include_sender')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('member_messages', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'tree_side',
                'include_sender',
            ]);
        });
    }
};
