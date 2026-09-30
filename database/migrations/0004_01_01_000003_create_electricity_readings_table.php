<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ElectricityReading = photo-based or manual reading of the electricity meter
 * (ساعة الكهرباء) captured before/after each water-fill session.
 *
 * phase:
 *   BEFORE     — captured at session start
 *   AFTER      — captured at session stop (triggers cost computation)
 *   STANDALONE — ad-hoc, not linked to any session
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('electricity_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('filling_session_id')->nullable()->constrained()->onDelete('set null');

            $table->float('reading', 12, 4); // meter value at capture time (kWh)
            $table->float('previous_reading', 12, 4)->nullable();
            $table->float('consumption', 12, 4)->nullable(); // reading - previousReading

            $table->string('phase')->default('STANDALONE'); // BEFORE | AFTER | STANDALONE
            $table->string('source')->default('MANUAL');    // OCR | MANUAL
            $table->float('confidence')->nullable();        // OCR confidence 0..1
            $table->string('engine')->nullable();           // tesseract-js | easyocr-py | null
            $table->text('photo_thumb')->nullable();        // base64 JPEG thumbnail
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->index(['family_id', 'created_at']);
            $table->index(['filling_session_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_readings');
    }
};
