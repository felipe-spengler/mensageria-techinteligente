<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->foreignId('api_key_id')->nullable()->change();
            $table->string('ip_address', 45)->nullable()->index();
            $table->boolean('is_free')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->foreignId('api_key_id')->nullable(false)->change();
            $table->dropColumn(['ip_address', 'is_free']);
        });
    }
};
