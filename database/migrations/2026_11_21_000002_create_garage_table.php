<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Garasi dibuat batch oleh Admin; kode: S###, C###, E###.
     * `charging_price` (harga isi listrik per kWh) hanya untuk garasi mobil listrik.
     */
    public function up(): void
    {
        Schema::create('garage', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 4)->unique();
            $table->enum('type', ['BIKE', 'CAR', 'EV']);
            $table->unsignedInteger('daily_price');
            $table->unsignedInteger('charging_price')->nullable();
            $table->enum('status', ['AVAILABLE', 'USED']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garage');
    }
};
