<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tim lomba grup ternyata tidak selalu satu KK (mis. tarik tambang bapak-bapak
     * tidak mungkin anak ikut) — panitia yang menyusun anggota tim lintas keluarga,
     * jadi keterikatan tim ke satu family_submission dihapus.
     */
    public function up(): void
    {
        Schema::table('competition_teams', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'family_submission_id']);
            $table->dropForeign(['family_submission_id']);
            $table->dropColumn('family_submission_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_teams', function (Blueprint $table) {
            $table->uuid('family_submission_id')->nullable()->after('competition_id');

            $table->foreign('family_submission_id')
                ->references('id')
                ->on('family_submissions')
                ->onDelete('cascade');

            $table->unique(['competition_id', 'family_submission_id']);
        });
    }
};
