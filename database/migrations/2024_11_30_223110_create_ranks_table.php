<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Rank name
            $table->string('package'); // Required package level
            $table->integer('left_volume')->default(0); // Required volume for left leg
            $table->integer('right_volume')->default(0); // Required volume for right leg
            $table->integer('direct_referrals')->default(0); // Number of direct referrals required
            $table->json('downline_requirements')->nullable(); // JSON for downline rank requirements
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ranks');
    }
}
