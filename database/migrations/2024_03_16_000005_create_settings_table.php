<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        // Default settings
        DB::table('settings')->insert([
            ['key' => 'wpp_delay_minutes', 'value' => '2', 'group' => 'warming_up'],
            ['key' => 'wpp_max_hourly_msgs', 'value' => '30', 'group' => 'warming_up'],
            ['key' => 'asaas_api_key', 'value' => '', 'group' => 'asaas'],
            ['key' => 'asaas_mode', 'value' => 'sandbox', 'group' => 'asaas'],
            ['key' => 'asaas_webhook_token', 'value' => '', 'group' => 'asaas'],
            ['key' => 'wpp_bridge_key', 'value' => Str::random(32), 'group' => 'bridge'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
