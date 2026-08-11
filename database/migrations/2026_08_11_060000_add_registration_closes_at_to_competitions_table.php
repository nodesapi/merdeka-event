<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal tutup pendaftaran otomatis (opsional), pelengkap saklar manual
     * registration_open — lomba otomatis dianggap tertutup begitu waktu ini lewat.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->timestamp('registration_closes_at')->nullable()->after('registration_open');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('registration_closes_at');
        });
    }
};
