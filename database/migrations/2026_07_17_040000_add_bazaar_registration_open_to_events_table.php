<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar manual bagi panitia untuk membuka/menutup pendaftaran lapak
     * bazaar tanpa perlu menunggu kuota penuh atau tanggal tertentu.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('bazaar_registration_open')->default(true)->after('bazaar_poster_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('bazaar_registration_open');
        });
    }
};
