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
        $submission = FamilySubmission::with(['contributionItems', 'familyMembers' => fn ($q) => $q->withCount('competitionParticipations')->orderBy('registration_number')])->findOrFail($id);

        $this->selectedSubmissionId = $submission->id;
        $this->reviewNotes = $submission->admin_notes ?? '';
    }

    public function verifySubmission(): void
    {
        $submission = $this->currentSubmission();

        if (! $submission) {
            return;
        }

        $submission->approveAndRecord($this->reviewNotes);

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
        // Sumber tunggal label metode (termasuk 'qris') ada di model.
        return FamilySubmission::paymentMethodLabel($method);
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
            $selectedSubmission = FamilySubmission::with(['contributionItems', 'familyMembers' => fn ($q) => $q->withCount('competitionParticipations')->orderBy('registration_number')])->find($this->selectedSubmissionId);
        } elseif ($submissions->first()) {
            $selectedSubmission = FamilySubmission::with(['contributionItems', 'familyMembers' => fn ($q) => $q->withCount('competitionParticipations')->orderBy('registration_number')])->find($submissions->first()->id);
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

                        {{-- Kirim data ke warga (WhatsApp Web manual) & Bukti Pendaftaran (PDF/cetak). --}}
                        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-5 py-3 sm:px-6">
                            @if ($selectedSubmission->whatsappUrl())
                                <a href="{{ $selectedSubmission->whatsappUrl() }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.71.306 1.263.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.29.173-1.414-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.548 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    Kirim WhatsApp
                                </a>
                            @endif
                            <a href="{{ route('public.registration-receipt', $selectedSubmission->reference_code) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                <x-icon name="calendar" class="h-4 w-4" /> Bukti / PDF
                            </a>
                            @if ($selectedSubmission->payment_method === 'qris' && $selectedSubmission->payment_status !== 'paid')
                                <a href="{{ route('public.qris-payment', $selectedSubmission->reference_code) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                    Link Pembayaran QRIS
                                </a>
                            @endif
                            @if ($selectedSubmission->payment_status === 'paid')
                                <span class="ml-auto rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Pembayaran LUNAS (QRIS)</span>
                            @endif
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
                                                    @if ($member->competition_participations_count > 0)
                                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700">Ikut {{ $member->competition_participations_count }} lomba</span>
                                                    @else
                                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] text-slate-500">Belum ikut</span>
                                                    @endif
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
