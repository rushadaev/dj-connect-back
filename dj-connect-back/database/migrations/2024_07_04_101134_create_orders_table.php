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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('dj_id')->constrained()->onDelete('cascade');
            $table->foreignId('track_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 8, 2);
            $table->string('message')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('time_slot')->nullable(); // Time slot for playing the track
            $table->boolean('reminder_sent')->default(false); // Indicates if reminder was sent to DJ
            $table->boolean('notification_sent')->default(false); // Indicates if notification was sent to the client
            $table->boolean('track_played')->default(false); // Indicates if the track has been played
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};