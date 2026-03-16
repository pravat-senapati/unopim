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
        Schema::create('job_warnings', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('job_track_id')->unsigned();

            $table->text('reason');

            $table->text('item')->nullable();

            $table->foreign('job_track_id')->references('id')->on('job_track')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_warnings');
    }
};
