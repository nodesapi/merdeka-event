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
        Schema::create('family_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('family_submission_id');
            $table->string('name');
            $table->enum('relationship', ['ayah', 'ibu', 'anak', 'lainnya'])->default('anak');
            $table->integer('age')->nullable();
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->uuid('competition_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('family_submission_id')
                ->references('id')
                ->on('family_submissions')
                ->onDelete('cascade');

            $table->foreign('competition_id')
                ->references('id')
                ->on('competitions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
