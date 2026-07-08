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
        Schema::table('family_submissions', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('proof_file');
            $table->string('payment_invoice_number')->nullable()->index()->after('payment_provider');
            $table->decimal('payment_pay_amount', 15, 2)->nullable()->after('payment_invoice_number');
            $table->text('payment_qris_svg')->nullable()->after('payment_pay_amount');
            $table->string('payment_status')->nullable()->after('payment_qris_svg');
            $table->timestamp('payment_expires_at')->nullable()->after('payment_status');
            $table->timestamp('payment_paid_at')->nullable()->after('payment_expires_at');
        });

        // Izinkan 'qris' pada kolom payment_method. Laravel bikin enum di Postgres sebagai
        // varchar + CHECK constraint; kita drop & buat ulang menyertakan 'qris'.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE family_submissions DROP CONSTRAINT IF EXISTS family_submissions_payment_method_check');
            DB::statement("ALTER TABLE family_submissions ADD CONSTRAINT family_submissions_payment_method_check CHECK (payment_method IN ('transfer', 'cash', 'other', 'qris'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE family_submissions DROP CONSTRAINT IF EXISTS family_submissions_payment_method_check');
            DB::statement("ALTER TABLE family_submissions ADD CONSTRAINT family_submissions_payment_method_check CHECK (payment_method IN ('transfer', 'cash', 'other'))");
        }

        Schema::table('family_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_provider',
                'payment_invoice_number',
                'payment_pay_amount',
                'payment_qris_svg',
                'payment_status',
                'payment_expires_at',
                'payment_paid_at',
            ]);
        });
    }
};
