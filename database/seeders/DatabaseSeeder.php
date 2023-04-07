<?php

namespace Susheelbhai\Larapay\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(PaymentGatewaySeeder::class);
    }
}