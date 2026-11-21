<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Profil penyewa (relasi 1:1 ke `user`). Admin tidak punya baris di sini.
     */
    public function up(): void
    {
        Schema::create('renter', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_user')->unique();
            $table->string('phone_number', 20);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->string('nik', 16);

            $table->foreign('id_user')->references('id')->on('user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renter');
    }
};
