<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel activity_log pada sebagian environment terlanjur dibuat tanpa kolom
     * attribute_changes (versi migration sebelumnya). Pastikan kolomnya ada.
     */
    public function up(): void
    {
        if (Schema::hasTable('activity_log') && ! Schema::hasColumn('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->json('attribute_changes')->nullable()->after('causer_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_log') && Schema::hasColumn('activity_log', 'attribute_changes')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropColumn('attribute_changes');
            });
        }
    }
};
