<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\CompetitionParticipant;
use App\Models\Event;
use App\Models\FamilySubmission;
use App\Models\Transaction;
use App\Support\AgeCategory;
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
     * Export data warga (per anggota, dikelompokkan per keluarga) sebagai CSV (Excel) atau PDF cetak.
     */
    public function residents(Request $request)
    {
        $event = $this->activeEvent();

        $submissions = $event
            ? $event->familySubmissions()
                ->with(['familyMembers' => fn ($q) => $q->withCount('competitionParticipations')->orderBy('registration_number')])
                ->latest()
                ->get()
            : collect();

        if ($request->query('format') === 'pdf') {
            return view('admin.exports.residents', [
                'event' => $event,
                'submissions' => $submissions,
                'site' => \App\Models\SiteSetting::current(),
                'generatedAt' => now(),
            ]);
        }

        $rows = collect();
        foreach ($submissions as $submission) {
            foreach ($submission->familyMembers->values() as $index => $member) {
                $rows->push([
                    $member->registration_number,
                    $member->name,
                    $index === 0 ? 'Kepala Keluarga' : ucfirst($member->relationship),
                    $member->age,
                    $member->gender_label,
                    $submission->head_of_family_name,
                    $submission->resident_block,
                    $submission->reference_code,
                    match ($submission->status) {
                        'verified' => 'Terverifikasi',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu',
                    },
                    $member->competition_participations_count,
                    $submission->phone_number,
                ]);
            }
        }

        return $this->streamCsv(
            'data-warga-' . now()->format('Ymd-Hi') . '.csv',
            ['No Daftar', 'Nama', 'Hubungan', 'Umur', 'Gender', 'Kepala Keluarga', 'Blok', 'No Ref', 'Status', 'Jml Lomba', 'No HP'],
            $rows,
        );
    }

    /**
     * Export daftar peserta satu lomba (dikelompokkan per kategori umur) sebagai CSV (Excel) atau PDF cetak.
     */
    public function participants(Request $request)
    {
        $event = $this->activeEvent();

        $competition = $event
            ? $event->competitions()->where('slug', $request->query('lomba'))->first()
                ?? $event->competitions()->orderBy('name')->first()
            : null;

        abort_unless($competition, 404, 'Lomba tidak ditemukan.');

        $participants = CompetitionParticipant::where('competition_id', $competition->id)
            ->with('familyMember:id,registration_number')
            ->orderBy('name')
            ->get();

        $byCategory = $participants
            ->groupBy(fn ($p) => $p->age_category_key ?? 'none')
            ->sortBy(fn ($group, $key) => AgeCategory::order($key === 'none' ? null : $key));

        if ($request->query('format') === 'pdf') {
            return view('admin.exports.participants', [
                'event' => $event,
                'competition' => $competition,
                'byCategory' => $byCategory,
                'totalParticipants' => $participants->count(),
                'site' => \App\Models\SiteSetting::current(),
                'generatedAt' => now(),
            ]);
        }

        $statusOf = fn (CompetitionParticipant $p) => $p->rank
            ? 'Juara ' . $p->rank
            : ($p->status === 'eliminated' ? 'Gugur' : 'Lolos');

        $rows = collect();
        foreach ($byCategory as $group) {
            foreach ($group as $p) {
                $rows->push([
                    $p->familyMember?->registration_number ?: '-',
                    $p->name,
                    $p->age_category_label,
                    $p->age,
                    'Babak ' . $p->round . ($p->round == $competition->total_rounds ? ' (Final)' : ''),
                    $statusOf($p),
                    $p->resident_block,
                    $p->phone_number,
                ]);
            }
        }

        return $this->streamCsv(
            'peserta-' . $competition->slug . '-' . now()->format('Ymd-Hi') . '.csv',
            ['No Daftar', 'Nama / Regu', 'Kategori Umur', 'Umur', 'Babak', 'Status', 'Blok', 'No HP'],
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
