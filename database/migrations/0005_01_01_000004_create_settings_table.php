<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings — one row per user (1:1).
 * Includes electricity tariff + engine power draw for cost computation.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->integer('free_minutes_per_week')->default(12);
            $table->float('price_per_minute', 8, 4)->default(0.5);
            $table->boolean('auto_reset_weekly')->default(true);
            $table->tinyInteger('reset_day')->default(6); // Saturday
            $table->timestamp('last_auto_reset')->nullable();

            // Electricity cost inputs
            $table->float('electricity_tariff', 8, 4)->nullable(); // shekel per kWh
            $table->float('engine_power_kw', 8, 4)->nullable();     // ghatas pump power draw (kW)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
