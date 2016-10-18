<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTextsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('texts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('guuid');
            $table->string('title');
            $table->string('version');
            $table->string('type');
            $table->boolean('is_private');
            $table->integer('word_count')->unsigned();
            $table->integer('character_count')->unsigned();
            $table->integer('unique_word_count')->unsigned();
            $table->integer('paragraph_count')->unsigned();
            $table->integer('created_by')->unsigned();
            $table->string('document');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
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
        Schema::dropIfExists('texts');
    }
}
