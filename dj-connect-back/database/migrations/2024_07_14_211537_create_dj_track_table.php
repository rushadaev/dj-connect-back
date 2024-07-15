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
        Schema::create('dj_track', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dj_id')->constrained()->onDelete('cascade');
            $table->foreignId('track_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dj_track');
    }
};