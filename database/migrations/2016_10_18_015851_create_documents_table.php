<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('guuid')->default(DB::raw('uuid_generate_v4()'))->nullable();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('year')->nullable();
            $table->string('version')->default(1)->nullable();
            $table->string('type')->default('essay')->nullable();
            $table->boolean('is_private')->default(true)->nullable();
			
            $table->integer('word_count')->unsigned();
            $table->integer('character_count')->unsigned();
            $table->integer('unique_word_count')->unsigned();
            $table->integer('paragraph_count')->unsigned();

            $table->longText('text')->nullable();
			$table->integer('created_by')->unsigned();
			$table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('documents');
    }
}
