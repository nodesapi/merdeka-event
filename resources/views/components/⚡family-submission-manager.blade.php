<?php

use App\Models\CompetitionParticipant;
use App\Models\Event;
use App\Models\FamilySubmission;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public ?string $selectedSubmissionId = null;
    public string $reviewNotes = '';
    public string $successMessage = '';

    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    public function selectSubmission(string $id): void
    {
        $submission = FamilySubmission::with(['contributionItems', 'familyMembers.competition'])->findOrFail($id);

        $this->selectedSubmissionId = $submission->id;
        $this->reviewNotes = $submission->admin_notes ?? '';
    }

    public function verifySubmission(): void
    {
        $submission = $this->currentSubmission();

        if (! $submission) {
            return;
        }

        DB::transaction(function () use ($submission): void {
            $submission->loadMissing(['contributionItems', 'familyMembers.competition']);

            foreach ($submission->contributionItems as $item) {
                Transaction::firstOrCreate(
                    ['contribution_item_id' => $item->id],
                    [
                        'user_id' => null,
                        'amount' => $item->amount,
                        'type' => 'income',
                        'bank_name' => $this->paymentMethodLabel($submission->payment_method),
                        'account_number' => $submission->reference_code,
                        'resident_block' => $submission->resident_block,
                        'description' => trim(($item->label ?: ucfirst($item->type)) . ' - ' . $submission->head_of_family_name . ($item->note ? ' (' . $item->note . ')' : '')),
                        'status' => 'approved',
                    ]
                );
            }

            foreach ($submission->familyMembers->whereNotNull('competition_id') as $member) {
                CompetitionParticipant::firstOrCreate(
                    ['family_member_id' => $member->id],
                    [
                        'competition_id' => $member->competition_id,
                        'name' => $member->name,
                        'resident_block' => $submission->resident_block,
                        'phone_number' => $submission->phone_number,
                        'round' => 1,
                        'status' => 'active',
                        'rank' => null,
                        'notes' => 'Pendaftaran via form warga ' . $submission->reference_code,
                    ]
                );
            }

            $submission->update([
                'status' => 'verified',
                'admin_notes' => $this->reviewNotes,
                'verified_at' => now(),
            ]);
        });

        $this->successMessage = 'Form warga berhasil diverifikasi dan dicatat ke sistem.';
    }

    public function rejectSubmission(): void
    {
        $submission = $this->currentSubmission();

        if (! $submission) {
            return;
        }

        $submission->update([
            'status' => 'rejected',
            'admin_notes' => $this->reviewNotes,
            'verified_at' => null,
        ]);

        $this->successMessage = 'Form warga ditandai ditolak. Catatan panitia sudah disimpan.';
    }

    public function dismissAlert(): void
    {
        $this->successMessage = '';
    }

    public function paymentMethodLabel(?string $method): string
    {
        return match ($method) {
            'transfer' => 'Transfer',
            'cash' => 'Tunai',
            default => 'Lainnya',
        };
    }

    protected function currentSubmission(): ?FamilySubmission
    {
        if (! $this->selectedSubmissionId) {
            return null;
        }

        return FamilySubmission::find($this->selectedSubmissionId);
    }

    public function with(): array
    {
        $event = $this->activeEvent();

        $submissions = $event
            ? $event->familySubmissions()
                ->withCount(['familyMembers', 'contributionItems'])
                ->latest()
                ->get()
            : collect();

        $selectedSubmission = null;

        if ($this->selectedSubmissionId) {
            $selectedSubmission = FamilySubmission::with(['contributionItems', 'familyMembers.competition'])->find($this->selectedSubmissionId);
        } elseif ($submissions->first()) {
            $selectedSubmission = FamilySubmission::with(['contributionItems', 'familyMembers.competition'])->find($submissions->first()->id);
        }

        if ($selectedSubmission && ! $this->selectedSubmissionId) {
            $this->selectedSubmissionId = $selectedSubmission->id;
            $this->reviewNotes = $selectedSubmission->admin_notes ?? '';
        }

        return [
            'event' => $event,
            'submissions' => $submissions,
            'selectedSubmission' => $selectedSubmission,
        ];
    }
};
?>

<div>
    @if ($successMessage)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
            <div>
                <p class="text-sm font-semibold">Perubahan berhasil disimpan</p>
                <p class="mt-1 text-sm">{{ $successMessage }}</p>
            </div>
            <button wire:click="dismissAlert" class="text-lg font-bold leading-none text-emerald-500 transition hover:text-emerald-800" aria-label="Tutup notifikasi">&times;</button>
        </div>
    @endif

    @if (! $event)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800">
            <p class="text-sm font-semibold">Belum ada acara aktif, jadi belum ada form warga yang bisa direview.</p>
        </div>
    @else
        <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Pendaftaran Warga</h3>
                <p class="text-xs text-slate-500">{{ $submissions->count() }} pendaftaran masuk · total Rp{{ number_format($submissions->sum('submitted_total'), 0, ',', '.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.family-submissions.export', ['format' => 'csv']) }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    <x-icon name="wallet" class="h-4 w-4" /> Excel
                </a>
                <a href="{{ route('admin.family-submissions.export', ['format' => 'pdf']) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                    <x-icon name="calendar" class="h-4 w-4" /> PDF
                </a>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(320px,0.85fr)_minmax(0,1.15fr)]">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Daftar Form Warga</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $submissions->count() }} submit masuk</h3>
                    <p class="mt-1 text-sm text-slate-500">Klik salah satu untuk melihat detail kontribusi dan anggota keluarga.</p>
                </div>

                <div class="max-h-[72vh] overflow-y-auto divide-y divide-slate-100">
                    @forelse ($submissions as $submission)
                        @php
                            $statusClass = match ($submission->status) {
                                'verified' => 'bg-emerald-50 text-emerald-700',
                                'rejected' => 'bg-red-50 text-red-700',
                                default => 'bg-amber-50 text-amber-700',
                            };
                        @endphp
                        <button type="button" wire:click="selectSubmission('{{ $submission->id }}')" class="w-full px-5 py-4 text-left transition hover:bg-slate-50 {{ $selectedSubmissionId === $submission->id ? 'bg-red-50/70' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-slate-900">{{ $submission->head_of_family_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $submission->resident_block }} · {{ $submission->phone_number }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $statusClass }}">
                                    {{ $submission->status }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                <span class="rounded-full bg-slate-100 px-2 py-1">{{ $submission->reference_code }}</span>
                                <span class="rounded-full bg-slate-100 px-2 py-1">{{ $submission->family_members_count }} anggota</span>
                                <span class="rounded-full bg-slate-100 px-2 py-1">{{ $submission->contribution_items_count }} kontribusi</span>
                                <span class="rounded-full bg-slate-100 px-2 py-1">Rp{{ number_format($submission->submitted_total, 0, ',', '.') }}</span>
                            </div>
                        </button>
                    @empty
                        <p class="px-5 py-6 text-sm text-slate-400">Belum ada form warga yang masuk.</p>
                    @endforelse
                </div>
            </section>

            <section class="space-y-6">
                @if ($selectedSubmission)
                    <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Detail Pengajuan</p>
                                    <h3 class="mt-2 text-2xl font-bold text-slate-900">{{ $selectedSubmission->head_of_family_name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $selectedSubmission->reference_code }} · {{ $selectedSubmission->resident_block }}</p>
                                </div>
                                <div class="text-sm text-slate-500">
                                    <p>Metode: <span class="font-semibold text-slate-900">{{ $this->paymentMethodLabel($selectedSubmission->payment_method) }}</span></p>
                                    <p class="mt-1">Total diajukan: <span class="font-semibold text-slate-900">Rp{{ number_format($selectedSubmission->submitted_total, 0, ',', '.') }}</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-6 px-5 py-5 sm:px-6 xl:grid-cols-2">
                            <div class="space-y-4">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Kontak</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $selectedSubmission->phone_number }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $selectedSubmission->email ?: 'Tidak mengisi email' }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Keluarga</p>
                                    <p class="mt-2 text-sm text-slate-700">{{ $selectedSubmission->notes ?: '-' }}</p>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Pembayaran</p>
                                    <p class="mt-2 text-sm text-slate-700">{{ $selectedSubmission->payment_notes ?: '-' }}</p>
                                    @if ($selectedSubmission->proof_file_url)
                                        <a href="{{ $selectedSubmission->proof_file_url }}" target="_blank" class="mt-3 inline-flex rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-white">Lihat bukti pembayaran</a>
                                    @endif
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Rincian Kontribusi</p>
                                    <div class="mt-3 space-y-3">
                                        @foreach ($selectedSubmission->contributionItems as $item)
                                            <div class="rounded-xl bg-slate-50 p-3">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-900">{{ $item->label ?: ucfirst($item->type) }}</p>
                                                        <p class="mt-1 text-xs text-slate-500">{{ $item->note ?: 'Tanpa catatan tambahan' }}</p>
                                                    </div>
                                                    <span class="text-sm font-bold text-slate-900">Rp{{ number_format($item->amount, 0, ',', '.') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Anggota Keluarga</p>
                                    <div class="mt-3 space-y-3">
                                        @foreach ($selectedSubmission->familyMembers as $member)
                                            <div class="rounded-xl bg-slate-50 p-3">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                                                        <p class="mt-1 text-xs text-slate-500">{{ ucfirst($member->relationship) }} · {{ $member->age ? $member->age . ' tahun' : 'Usia belum diisi' }} · {{ $member->gender ?: '-' }}</p>
                                                    </div>
                                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700">
                                                        {{ $member->competition?->name ?: 'Tidak ikut lomba' }}
                                                    </span>
                                                </div>
                                                @if ($member->notes)
                                                    <p class="mt-2 text-xs text-slate-500">{{ $member->notes }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Review Panitia</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-900">Verifikasi form warga</h3>

                        <div class="mt-5">
                            <label class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Catatan Panitia</label>
                            <textarea wire:model="reviewNotes" rows="4" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-red-500 focus:ring-2 focus:ring-red-100" placeholder="Catatan verifikasi atau alasan penolakan"></textarea>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <button type="button" wire:click="rejectSubmission" class="rounded-xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                Tolak Form
                            </button>
                            <button type="button" wire:click="verifySubmission" class="rounded-xl bg-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-800">
                                Verifikasi & Catat
                            </button>
                        </div>
                    </div>
                @else
                    <div class="rounded-3xl border border-slate-200 bg-white px-5 py-8 text-sm text-slate-400 shadow-sm">
                        Pilih salah satu submit di sebelah kiri untuk melihat detailnya.
                    </div>
                @endif
            </section>
        </div>
    @endif
</div>
