<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id'); // PRIMARY KEY - BIGINT UNSIGNED
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->unsignedBigInteger('fee_type_id');
            $table->decimal('amount', 8, 2);
            $table->date('transaction_date');
            $table->string('status')->default('unpaid'); // unpaid, partially_paid, paid
            $table->timestamps();

            // Foreign keys
            $table->foreign('member_id')->references('member_id')->on('members')->cascadeOnDelete();
            $table->foreign('loan_id')->references('loan_id')->on('loans')->nullOnDelete();
            $table->foreign('fee_type_id')->references('fee_type_id')->on('fee_types')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
