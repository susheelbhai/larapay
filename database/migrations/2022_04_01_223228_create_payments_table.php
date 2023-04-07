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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('payment_gateway_id')->nullable()->references('id')->on('payment_gateways');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('amount')->nullable();
            $table->string('order_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('product_id')->nullable();
            $table->string('payment_gateway')->nullable();
            $table->string('receipt')->nullable();
            $table->boolean('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
