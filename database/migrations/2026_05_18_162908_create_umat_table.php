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
        Schema::create('umat', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap')->nullable();
            $table->string('nama_panggilan')->nullable();
            $table->string('nomor_telepon')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('status_perkawinan')->nullable();
            $table->string('hub_kk')->nullable();
            $table->string('golongan_darah')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('kemah_id')->nullable()->constrained('kemah')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('area')->nullOnDelete();
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('domisili')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umat');
    }
};
