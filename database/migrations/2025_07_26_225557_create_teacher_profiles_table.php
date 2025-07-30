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
    Schema::create('teacher_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
       // $table->string('teacher_image')->nullable();
        $table->date('teaching_start_date')->nullable();
        $table->string('province')->nullable();
        $table->text('bio')->nullable();
        $table->string('specialization')->nullable();
        $table->string('age')->nullable();
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
        Schema::dropIfExists('teacher_profiles');
    }
};
