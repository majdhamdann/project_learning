<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        DB::table('roles')->insert([
            ['name' => 'student'],
            ['name' => 'teacher'],
            ['name' => 'admin'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
