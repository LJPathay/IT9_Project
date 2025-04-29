<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id'); // PRIMARY KEY - BIGINT UNSIGNED
            $table->unsignedBigInteger('transaction_id'); // Foreign key to transactions
            $table->decimal('payment_amount', 8, 2);
            $table->date('payment_date');
            $table->string('payment_method');
            $table->timestamps();
            $table->foreign('transaction_id')->references('transaction_id')->on('transactions')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
