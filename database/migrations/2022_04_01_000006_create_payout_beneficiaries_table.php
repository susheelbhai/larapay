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
        Schema::create('payout_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_type')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('gateway_beneficiary_id')->nullable();
            $table->string('gateway_contact_id')->nullable();
            $table->string('gateway_fund_account_id')->nullable();
            $table->enum('account_type', ['bank_account', 'vpa']);
            $table->string('account_number')->nullable();
            $table->string('ifsc')->nullable();
            $table->string('vpa')->nullable();
            $table->string('gateway_name');
            $table->integer('payment_gateway_id');
            $table->boolean('is_active')->default(true);

            $table->unique(['account_number', 'ifsc', 'gateway_name']);
            $table->unique(['vpa', 'gateway_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payout_beneficiaries');
    }
};
