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
        Schema::create('family_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('event_id');
            $table->string('reference_code')->unique();
            $table->string('head_of_family_name');
            $table->string('resident_block');
            $table->string('phone_number');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('recommended_amount', 15, 2)->nullable();
            $table->decimal('submitted_total', 15, 2)->default(0);
            $table->enum('payment_method', ['transfer', 'cash', 'other'])->nullable();
            $table->string('proof_file')->nullable();
            $table->text('payment_notes')->nullable();
            $table->enum('status', ['submitted', 'verified', 'rejected'])->default('submitted');
            $table->text('admin_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('event_id')
                ->references('id')
                ->on('events')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_submissions');
    }
};
