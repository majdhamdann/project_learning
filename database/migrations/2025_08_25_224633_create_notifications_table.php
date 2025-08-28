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
<<<<<<< HEAD:database/migrations/2025_08_21_235835_create_device_tokens_table.php
        Schema::create('device_tokens', function (Blueprint $table) {
             $table->id();
             $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
             $table->string('token')->unique();
             $table->string('platform')->nullable(); 
             $table->timestamps();
=======
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
>>>>>>> 36979332aa29d0be12ae3af1b0efb6de9b16da38:database/migrations/2025_08_25_224633_create_notifications_table.php
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
};
