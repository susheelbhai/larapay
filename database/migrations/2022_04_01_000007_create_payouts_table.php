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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('payout_beneficiary_id')->nullable()->references('id')->on('payout_beneficiaries');
            $table->integer('payment_gateway_id');
            $table->string('gateway_payout_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('INR');
            $table->string('purpose')->nullable();
            $table->string('mode')->nullable();
            $table->enum('status', ['pending', 'processing', 'processed', 'reversed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->string('utr')->nullable();
            $table->boolean('inline_created')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payouts');
    }
};
