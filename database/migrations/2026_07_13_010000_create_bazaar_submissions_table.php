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
        Schema::create('bazaar_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->uuid('family_submission_id')->unique();
            $table->string('reference_code')->unique();
            $table->string('name');
            $table->string('resident_block');
            $table->string('phone_number');
            $table->string('jenis_jualan');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('family_submission_id')->references('id')->on('family_submissions')->onDelete('cascade');
        });

        // Cegah dua warga daftar jenis jualan yang sama (case-insensitive, abaikan spasi)
        // dalam satu event yang sama, sebagai jaring pengaman di level DB.
        DB::statement('CREATE UNIQUE INDEX bazaar_submissions_event_jenis_unique ON bazaar_submissions (event_id, lower(trim(jenis_jualan)))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bazaar_submissions');
    }
};
