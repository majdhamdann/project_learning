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
        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('option_image');
        });
    }
    
    public function down()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->string('option_image')->nullable();
        });
    }
    
};
