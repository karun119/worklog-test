<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionBreakTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('correction_break_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('correction_request_id')
                ->constrained()
                ->onDelete('cascade');
            $table->time('new_break_in')->nullable();
            $table->time('new_break_out')->nullable();
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
        Schema::dropIfExists('correction_break_times');
    }
}
