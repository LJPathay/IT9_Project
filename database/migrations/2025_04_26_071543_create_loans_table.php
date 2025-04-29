<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id('loan_id');
            $table->unsignedBigInteger('copy_id');
            $table->unsignedBigInteger('member_id');
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            
            $table->foreign('copy_id')->references('copy_id')->on('book_copies');
            $table->foreign('member_id')->references('member_id')->on('members');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loans');
    }
};
