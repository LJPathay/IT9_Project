<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id('book_id');
            $table->string('book_title');
            $table->string('isbn')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('publisher')->nullable();
            $table->foreignId('category_id')->constrained('categories', 'category_id');
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('books');
    }
};