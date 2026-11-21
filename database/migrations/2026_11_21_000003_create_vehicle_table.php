<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kendaraan milik penyewa. Plat wajib kecuali sepeda (BIKE); kWh hanya mobil listrik.
     */
    public function up(): void
    {
        Schema::create('vehicle', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_renter');
            $table->enum('vehicle_type', ['BIKE', 'MOTORCYCLE', 'CAR', 'EV']);
            $table->string('plate_number', 12)->nullable();
            $table->string('brand_model');
            $table->decimal('battery_kwh', 6, 2)->nullable();

            $table->foreign('id_renter')->references('id')->on('renter');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle');
    }
};
