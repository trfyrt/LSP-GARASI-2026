<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transaksi sewa garasi.
     */
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('garage_id')->constrained();
            $table->foreignId('vehicle_id')->constrained();
            $table->enum('rental_type', ['daily', 'monthly']);
            $table->unsignedSmallInteger('duration_days');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('rent_fee');
            $table->unsignedBigInteger('charging_fee')->default(0);
            $table->unsignedBigInteger('total_fee');
            $table->string('payment_proof_path');
            $table->timestamps();

            $table->index(['garage_id', 'end_date']);
            $table->index('user_id');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
