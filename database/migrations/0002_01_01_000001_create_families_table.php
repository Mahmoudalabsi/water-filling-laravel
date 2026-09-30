<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Family = a water account (e.g. "بيت أحمد", "بيت خالد").
 * Single-family simplified model: each user owns their own family accounts.
 * No GHATAS_OWNER / Building hierarchy — kept the schema flat like the original
 * app before the ghatas-owners feature was added.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
