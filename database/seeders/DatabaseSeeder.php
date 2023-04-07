<?php

namespace Susheelbhai\LarapayDatabase\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(PaymentGatewaySeeder::class);
    }
}