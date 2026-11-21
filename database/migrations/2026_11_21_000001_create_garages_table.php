<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garasi dibuat batch oleh Admin; kode: S###, C###, E###.
     */
    public function up(): void
    {
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->unique();
            $table->enum('type', ['bike', 'car', 'electric_car']);
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('charging_price_per_kwh')->nullable();
            $table->enum('status', ['available', 'used'])->default('available');
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
