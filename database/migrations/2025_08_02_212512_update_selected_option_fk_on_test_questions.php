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
    Schema::table('test_questions', function (Blueprint $table) {
        // نحذف الـ Foreign Key القديم
        $table->dropForeign(['selected_option_id']);

        // نضيفه مع onDelete('cascade')
        $table->foreign('selected_option_id')
              ->references('id')
              ->on('options')
              ->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('test_questions', function (Blueprint $table) {
        $table->dropForeign(['selected_option_id']);
        $table->foreign('selected_option_id')
              ->references('id')
              ->on('options');
    });
}
};
