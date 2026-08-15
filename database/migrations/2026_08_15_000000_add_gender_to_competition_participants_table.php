<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peserta manual (non-warga) tidak punya FamilyMember, jadi tidak bisa
     * ambil gender dari sana. Kolom ini isinya cuma dipakai kalau
     * family_member_id NULL — kalau terhubung ke warga, gender tetap dari
     * FamilyMember (lihat CompetitionParticipant::getGenderAttribute()).
     */
    public function up(): void
    {
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->enum('gender', ['L', 'P'])->nullable()->after('age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
