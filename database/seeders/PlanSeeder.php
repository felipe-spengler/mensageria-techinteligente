<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::create([
            'name' => 'Básico Texto',
            'message_limit' => 500,
            'type' => 'text',
            'price' => 150.00,
        ]);

        Plan::create([
            'name' => 'Básico Mídia',
            'message_limit' => 500,
            'type' => 'media',
            'price' => 250.00,
        ]);

        Plan::create([
            'name' => 'Plus Texto',
            'message_limit' => 1000,
            'type' => 'text',
            'price' => 200.00,
        ]);

        Plan::create([
            'name' => 'Plus Mídia',
            'message_limit' => 1000,
            'type' => 'media',
            'price' => 300.00,
        ]);
    }
}
