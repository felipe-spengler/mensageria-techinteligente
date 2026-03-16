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
        Schema::table('plans', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->integer('duration_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->string('type')->nullable()->change(); // Alterando para string flexível
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['description', 'duration_days', 'is_active']);
        });
    }
};
