<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('umat', function (Blueprint $table) {
            $table->string('status')->default('aktif')->index()->after('domisili');
            $table->date('tanggal_masuk')->nullable()->after('status');
            $table->date('tanggal_keluar')->nullable()->after('tanggal_masuk');
            $table->text('keterangan')->nullable()->after('tanggal_keluar');
        });

        // Backfill data lama: anggap aktif, tanggal masuk = tanggal record dibuat.
        DB::table('umat')->update([
            'status' => 'aktif',
            'tanggal_masuk' => DB::raw('date(created_at)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('umat', function (Blueprint $table) {
            $table->dropColumn(['status', 'tanggal_masuk', 'tanggal_keluar', 'keterangan']);
        });
    }
};
