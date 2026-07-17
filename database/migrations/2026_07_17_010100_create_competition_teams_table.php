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
        Schema::create('competition_teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('competition_id');
            $table->uuid('family_submission_id');
            $table->string('team_name')->nullable();
            $table->integer('round')->default(1);
            $table->enum('status', ['active', 'eliminated'])->default('active');
            $table->integer('rank')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('competition_id')
                ->references('id')
                ->on('competitions')
                ->onDelete('cascade');

            $table->foreign('family_submission_id')
                ->references('id')
                ->on('family_submissions')
                ->onDelete('cascade');

            // Satu keluarga hanya boleh membentuk satu tim per lomba grup.
            $table->unique(['competition_id', 'family_submission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_teams');
    }
};
