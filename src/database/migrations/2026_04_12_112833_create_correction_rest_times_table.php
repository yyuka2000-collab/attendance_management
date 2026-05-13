<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCorrectionRestTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('correction_rest_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stamp_correction_request_id')->constrained()->cascadeOnDelete();
            $table->dateTime('rest_start')->nullable();
            $table->dateTime('rest_end')->nullable();
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
        Schema::dropIfExists('correction_rest_times');
    }
}
