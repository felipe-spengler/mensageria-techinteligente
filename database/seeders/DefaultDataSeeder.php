<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // Default Plans based on User Request
        $plans = [
            // Categoria: Só Texto
            [
                'name' => 'Starter (Texto)',
                'description' => 'Ideal para quem está começando. Apenas texto.',
                'price' => 100.00,
                'message_limit' => 200,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'text',
            ],
            [
                'name' => 'Premium (Popular)',
                'description' => 'O melhor custo-benefício para textos.',
                'price' => 150.00,
                'message_limit' => 500,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'text',
            ],
            [
                'name' => 'Business (Texto)',
                'description' => 'Para alta demanda de mensagens de texto.',
                'price' => 200.00,
                'message_limit' => 1200,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'text',
            ],
            // Categoria: Com Mídia
            [
                'name' => 'Starter + Mídia',
                'description' => 'Envio de textos e arquivos de mídia (Imagens/PDF).',
                'price' => 180.00,
                'message_limit' => 200,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'media',
            ],
            [
                'name' => 'Premium + Mídia',
                'description' => 'Equilíbrio perfeito para envios com mídia.',
                'price' => 250.00,
                'message_limit' => 500,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'media',
            ],
            [
                'name' => 'Business + Mídia',
                'description' => 'O plano mais robusto com suporte total a mídia.',
                'price' => 350.00,
                'message_limit' => 1200,
                'duration_days' => 30,
                'is_active' => true,
                'type' => 'media',
            ],
        ];

        // Create Admin User early so we can use it in API key seeding
        $adminUser = \App\Models\User::updateOrCreate(
            ['email' => 'admin@techinteligente.site'],
            [
                'name' => 'Administrador',
                'password' => \Illuminate\Support\Facades\Hash::make('teste123'),
                'is_admin' => true,
                'phone' => '5545999999999'
            ]
        );

        foreach ($plans as $planData) {
            $plan = Plan::updateOrCreate(['name' => $planData['name']], $planData);

            if ($plan->name === 'Starter (Texto)' && $adminUser) {
                \App\Models\ApiKey::updateOrCreate(
                    ['key' => 'test_key_master_123'],
                    [
                        'user_id' => $adminUser->id,
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
