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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dj_id')->constrained('djs')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('yookassa_payout_id')->nullable();
            $table->enum('payout_type', ['bank_card', 'sbp', 'yoo_money']);
            $table->json('payout_details')->nullable();
            $table->enum('status', ['pending', 'processed']);
            $table->timestamps();
            $table->timestamp('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
