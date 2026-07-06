<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FamilySubmission;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    /**
     * Export transactions as CSV (Excel) or a print-friendly PDF page.
     */
    public function transactions(Request $request)
    {
        $filter = $request->query('filter', 'all');

        $query = Transaction::with('user')->latest();
        if (in_array($filter, ['income', 'expense'], true)) {
            $query->where('type', $filter);
        }
        $transactions = $query->get();

        $totalIncome = (float) Transaction::where('type', 'income')->sum('amount');
        $totalExpense = (float) Transaction::where('type', 'expense')->sum('amount');

        if ($request->query('format') === 'pdf') {
            return view('admin.exports.transactions', [
                'transactions' => $transactions,
                'totalIncome' => $totalIncome,
                'totalExpense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense,
                'filter' => $filter,
                'site' => \App\Models\SiteSetting::current(),
                'generatedAt' => now(),
            ]);
        }

        $rows = $transactions->map(fn (Transaction $t) => [
            $t->created_at?->format('d/m/Y H:i'),
            $t->type === 'expense' ? 'Keluar' : 'Masuk',
            $t->description,
            $t->resident_block,
            $t->bank_name,
            $t->account_number,
            (float) $t->amount,
            $t->status,
        ]);

        return $this->streamCsv(
            'transaksi-' . now()->format('Ymd-Hi') . '.csv',
            ['Tanggal', 'Tipe', 'Keterangan', 'Blok/PJ', 'Bank/Metode', 'No Rek/Ref', 'Nominal', 'Status'],
            $rows,
        );
    }

    /**
     * Export family (warga) submissions as CSV (Excel) or a print-friendly PDF page.
     */
    public function familySubmissions(Request $request)
    {
        $event = $this->activeEvent();

        $submissions = $event
            ? $event->familySubmissions()
                ->withCount(['familyMembers', 'contributionItems'])
                ->with(['familyMembers.competition', 'contributionItems'])
                ->latest()
                ->get()
            : collect();

        if ($request->query('format') === 'pdf') {
            return view('admin.exports.family-submissions', [
                'event' => $event,
                'submissions' => $submissions,
                'site' => \App\Models\SiteSetting::current(),
                'generatedAt' => now(),
            ]);
        }

        $rows = $submissions->map(fn (FamilySubmission $s) => [
            $s->reference_code,
            $s->head_of_family_name,
            $s->resident_block,
            $s->phone_number,
            $s->email,
            match ($s->payment_method) {
                'transfer' => 'Transfer',
                'cash' => 'Tunai',
                default => 'Lainnya',
            },
            (float) $s->submitted_total,
            $s->status,
            $s->family_members_count,
            $s->contribution_items_count,
            $s->created_at?->format('d/m/Y H:i'),
            $s->admin_notes,
        ]);

        return $this->streamCsv(
            'pendaftaran-warga-' . now()->format('Ymd-Hi') . '.csv',
            ['Kode', 'Kepala Keluarga', 'Blok', 'No HP', 'Email', 'Metode', 'Total Diajukan', 'Status', 'Jml Anggota', 'Jml Kontribusi', 'Tgl Submit', 'Catatan Panitia'],
            $rows,
        );
    }

    /**
     * Stream a UTF-8 (BOM) CSV download that opens cleanly in Excel.
     */
    protected function streamCsv(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
