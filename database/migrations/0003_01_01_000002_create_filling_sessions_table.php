<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FillingSession = a single water-fill session for a family.
 *
 * kwh_consumed, electricity_cost, price_per_minute are computed from
 * BEFORE + AFTER ElectricityReadings on the same session.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('filling_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->default(0); // seconds

            // Electricity cost tracking
            $table->float('kwh_consumed', 12, 4)->nullable();
            $table->float('electricity_cost', 12, 4)->nullable();
            $table->float('price_per_minute', 12, 4)->nullable();

            $table->timestamps();
            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('filling_sessions');
    }
};
