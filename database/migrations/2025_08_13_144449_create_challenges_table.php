<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('challenges', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('teacher_id');
        $table->string('title');
        $table->timestamp('start_time');
        $table->integer('duration_minutes');
        $table->timestamps();

        $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
    });

    Schema::create('challenge_question', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('challenge_id');
        $table->unsignedBigInteger('question_id');
        $table->timestamps();

        $table->foreign('challenge_id')->references('id')->on('challenges')->onDelete('cascade');
        $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('challenges');
    }
};
