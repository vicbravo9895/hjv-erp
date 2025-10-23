<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentScaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentScales = [
            ['trips_count' => 6, 'payment_amount' => 1200.00],
            ['trips_count' => 7, 'payment_amount' => 1400.00],
            ['trips_count' => 8, 'payment_amount' => 1600.00],
            ['trips_count' => 9, 'payment_amount' => 1800.00],
            ['trips_count' => 10, 'payment_amount' => 2000.00],
            ['trips_count' => 11, 'payment_amount' => 2200.00],
            ['trips_count' => 12, 'payment_amount' => 2400.00],
        ];

        foreach ($paymentScales as $scale) {
            \App\Models\PaymentScale::create($scale);
        }
    }
}
