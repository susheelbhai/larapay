<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Susheelbhai\Larapay\Models\PaymentGateway;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->boolean('is_active');
        });
        $payment_gateways = array(
            array('id' => '1', 'name' => 'Razorpay', 'is_active' => '1'),
            array('id' => '2', 'name' => 'PayUMoney', 'is_active' => '0'),
          );
        PaymentGateway::insert($payment_gateways);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_gateways');
    }
};
