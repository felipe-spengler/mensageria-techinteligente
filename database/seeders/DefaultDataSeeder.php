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
                'description' => '10 mensagens para testar a integração da plataforma.',
                'price' => 0.00,
                'message_limit' => 10,
                'duration_days' => 7,
                'is_active' => true,
                'type' => 'text',
            ],
            [
                'name' => 'Básico',
                'description' => 'Ideal para pequenos negócios. 1.000 mensagens/mês.',
                'price' => 49.90,
                'message_limit' => 1000,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'text',
            ],
            [
                'name' => 'Profissional',
                'description' => 'Disparos ilimitados (999k) e suporte prioritário.',
                'price' => 149.90,
                'message_limit' => 999999,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'text',
            ],
        ];

        foreach ($plans as $planData) {
            $plan = Plan::updateOrCreate(['name' => $planData['name']], $planData);
            
            // If it's the professional plan, let's create a default API key for testing
            if ($plan->name === 'Profissional') {
                \App\Models\ApiKey::updateOrCreate(
                    ['key' => 'pro_test_key_12345'],
                    [
                        'name' => 'Chave de Teste Master',
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'expires_at' => now()->addYears(1),
                    ]
                );
            }
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
