<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // Default Plans
        $plans = [
            [
                'name' => 'Grátis (Teste)',
                'description' => '10 mensagens para testar a plataforma',
                'price' => 0.00,
                'message_limit' => 10,
                'duration_days' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Básico',
                'description' => 'Ideal para pequenos negócios',
                'price' => 49.90,
                'message_limit' => 1000,
                'duration_days' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Profissional',
                'description' => 'Disparos ilimitados e suporte prioritário',
                'price' => 149.90,
                'message_limit' => 999999,
                'duration_days' => 30,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }

        // Default Settings
        $settings = [
            ['key' => 'asaas_api_key', 'value' => '', 'group' => 'asaas'],
            ['key' => 'asaas_mode', 'value' => 'sandbox', 'group' => 'asaas'],
            ['key' => 'wpp_bridge_key', 'value' => '7caeb868-3d08-4761-b126-4f601cd05f7a', 'group' => 'bridge'],
            ['key' => 'webhook_token', 'value' => bin2hex(random_bytes(16)), 'group' => 'security'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
