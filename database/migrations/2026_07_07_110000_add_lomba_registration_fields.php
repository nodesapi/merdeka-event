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
        // Batas umur per lomba (mis. balita hanya 1-6 tahun). Null = tanpa batas.
        Schema::table('competitions', function (Blueprint $table) {
            $table->integer('min_age')->nullable()->after('target_participants');
            $table->integer('max_age')->nullable()->after('min_age');
        });

        // Snapshot umur peserta saat mendaftar (dipakai untuk kategori fairness).
        Schema::table('competition_participants', function (Blueprint $table) {
            $table->integer('age')->nullable()->after('phone_number');
        });

        // Nomor Pendaftaran (No Daftar) unik per acara untuk tiap anggota keluarga.
        // Dipakai untuk daftar lomba, undian doorprize, dan registrasi ulang hari-H.
        Schema::table('family_members', function (Blueprint $table) {
            $table->uuid('event_id')->nullable()->after('family_submission_id');
            $table->string('registration_number')->nullable()->after('event_id');

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->nullOnDelete();

            $table->unique(['event_id', 'registration_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_members', function (Blueprint $table) {
            $table->dropUnique(['event_id', 'registration_number']);
            $table->dropForeign(['event_id']);
            $table->dropColumn(['event_id', 'registration_number']);
        });

        Schema::table('competition_participants', function (Blueprint $table) {
            $table->dropColumn('age');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['min_age', 'max_age']);
        });
    }
};
