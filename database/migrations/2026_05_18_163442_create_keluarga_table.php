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
        Schema::create('keluarga', function (Blueprint $table) {
            $table->id();
            $table->string('no_keluarga');
            $table->timestamps();
        });

        Schema::table('umat', function (Blueprint $table) {
            $table->foreignId('keluarga_id')->nullable()->constrained('keluarga')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umat', function (Blueprint $table) {
            $table->dropConstrainedForeignId('keluarga_id');
        });

        Schema::dropIfExists('keluarga');
    }
};
