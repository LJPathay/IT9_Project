<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('book_copies', function (Blueprint $table) {
            $table->id('copy_id');
            $table->unsignedBigInteger('book_id');
            $table->date('acquisition_date')->nullable();
            $table->string('status')->default('available'); // available, on_loan, reserved, damaged, lost
            
            $table->foreign('book_id')->references('book_id')->on('books')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('book_copies');
    }
};
