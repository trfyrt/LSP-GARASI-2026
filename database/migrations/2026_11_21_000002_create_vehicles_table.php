<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kendaraan milik penyewa. Plat wajib kecuali sepeda; kWh hanya mobil listrik.
     */
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['bicycle', 'motorcycle', 'car', 'electric_car']);
            $table->string('plate_number', 15)->nullable();
            $table->string('brand');
            $table->string('model');
            $table->decimal('battery_kwh', 6, 2)->nullable();
            $table->timestamps();

            $table->index('plate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
