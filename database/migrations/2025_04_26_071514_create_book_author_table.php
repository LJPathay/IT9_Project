<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('book_author', function (Blueprint $table) {
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('author_id');
            
            $table->foreign('book_id')->references('book_id')->on('books')->onDelete('cascade');
            $table->foreign('author_id')->references('author_id')->on('authors')->onDelete('cascade');
            
            $table->primary(['book_id', 'author_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_author');
    }
};