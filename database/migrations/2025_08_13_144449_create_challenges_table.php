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
        $table->foreignId('teacher_id')->references('id')->on('users')->onDelete('cascade');;
        $table->string('title');
        $table->boolean('points_transferred')->default(false);
        $table->timestamp('start_time');
        $table->integer('duration_minutes');
        $table->timestamps();

       
    });

    Schema::create('challenge_question', function (Blueprint $table) {
        $table->id();
        
        $table->timestamps();

        $table->foreignId('challenge_id')->references('id')->on('challenges')->onDelete('cascade');
        $table->foreignId('question_id')->references('id')->on('questions')->onDelete('cascade');
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
