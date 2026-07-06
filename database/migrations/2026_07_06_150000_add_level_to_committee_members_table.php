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
        Schema::table('committee_members', function (Blueprint $table) {
            // 1 = Pimpinan (Ketua/Wakil), 2 = Pengurus Inti (Sekretaris/Bendahara),
            // 3 = Koordinator & Seksi.
            $table->unsignedTinyInteger('level')->default(3)->after('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('committee_members', function (Blueprint $table) {
            $table->dropColumn('level');
        });
    }
};
