<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontrak sewa yang menghubungkan penyewa, garasi, dan kendaraan.
     * Tanggal dan biaya disimpan di `invoice`.
     */
    public function up(): void
    {
        Schema::create('rental', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_renter');
            $table->unsignedInteger('id_garage');
            $table->unsignedInteger('id_vehicle');
            $table->enum('rental_type', ['DAILY', 'MONTHLY']);

            $table->foreign('id_renter')->references('id')->on('renter');
            $table->foreign('id_garage')->references('id')->on('garage');
            $table->foreign('id_vehicle')->references('id')->on('vehicle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental');
    }
};
