<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tagihan per periode sewa. Sewa baru membuat invoice `initial`;
     * perpanjangan membuat invoice `extension` untuk rental (dan garasi) yang sama.
     */
    public function up(): void
    {
        Schema::create('invoice', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_rental');
            $table->enum('type', ['INITIAL', 'EXTENSION']);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('rent_fee');
            $table->unsignedInteger('charging_fee');
            $table->unsignedInteger('total_fee');
            $table->string('payment_proof_path');

            $table->foreign('id_rental')->references('id')->on('rental');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice');
    }
};
