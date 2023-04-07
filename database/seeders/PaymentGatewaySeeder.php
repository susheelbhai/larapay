<?php

namespace Susheelbhai\LarapayDatabase\Seeders;

use Susheelbhai\Larapay\Http\Models\PaymentGateway;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $payment_gateways = array(
            array('id' => '1', 'name' => 'Career', 'is_active' => '1'),
            array('id' => '2', 'name' => 'Help & Support', 'is_active' => '1'),
            array('id' => '3', 'name' => 'Privecy Policy', 'is_active' => '1'),
            array('id' => '4', 'name' => 'Terms of uses', 'is_active' => '1'),
          );
        PaymentGateway::insert($payment_gateways);
    }
}
