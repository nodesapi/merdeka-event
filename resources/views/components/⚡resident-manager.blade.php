<?php

use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\FamilySubmission;
use App\Traits\ConfirmsDeletion;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    use ConfirmsDeletion;

    public string $search = '';
    public string $success_message = '';

    // Form "Tambah Anggota" per keluarga (tanpa iuran tambahan).
    public ?string $addingToId = null;
    public string $newMemberName = '';
    public string $newMemberRelationship = 'anak';
    public $newMemberAge = null;
    public string $newMemberGender = '';

    protected function activeEvent(): ?Event
    {
        return Event::where('status', 'active')->latest('start_date')->first()
            ?? Event::latest('start_date')->first();
    }

    public function delete(string $id): void
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403, 'Hanya admin yang boleh menghapus data warga.');

        // Menghapus pendaftaran keluarga sekaligus seluruh anggotanya (cascade di DB).
        FamilySubmission::whereKey($id)->delete();
        $this->success_message = 'Data warga (pendaftaran keluarga) dihapus.';
    }

    public function startAdd(string $submissionId): void
    {
        $this->addingToId = $submissionId;
        $this->reset(['newMemberName', 'newMemberAge', 'newMemberGender']);
        $this->newMemberRelationship = 'anak';
        $this->resetErrorBag();
    }

    public function cancelAdd(): void
    {
        $this->addingToId = null;
        $this->reset(['newMemberName', 'newMemberAge', 'newMemberGender']);
    }

    /**
     * Tambah anggota ke keluarga yang sudah terdaftar — dapat No. Daftar baru
     * (lanjut nomor urut tertinggi di acara ini), TANPA iuran tambahan.
     */
    public function addMember(): void
    {
        $submission = FamilySubmission::find($this->addingToId);

        if (! $submission) {
            $this->addingToId = null;
            return;
        }

        $this->validate([
            'newMemberName' => 'required|string|max:255',
            'newMemberRelationship' => 'required|in:ayah,ibu,anak,lainnya',
            'newMemberAge' => 'nullable|integer|min:0|max:120',
            'newMemberGender' => 'nullable|in:L,P',
        ], [], ['newMemberName' => 'nama anggota']);

        // No. Daftar mengikuti nomor terakhir (tertinggi) di acara ini, lalu +1.
        $sequence = (int) FamilyMember::where('event_id', $submission->event_id)
            ->max(DB::raw('CAST(registration_number AS INTEGER)'));
        $registrationNumber = str_pad((string) ($sequence + 1), 4, '0', STR_PAD_LEFT);

        FamilyMember::create([
            'family_submission_id' => $submission->id,
            'event_id' => $submission->event_id,
            'registration_number' => $registrationNumber,
            'name' => $this->newMemberName,
            'relationship' => $this->newMemberRelationship,
            'age' => ($this->newMemberAge !== null && $this->newMemberAge !== '') ? (int) $this->newMemberAge : null,
            'gender' => $this->newMemberGender ?: null,
        ]);

        $name = $this->newMemberName;
        $this->cancelAdd();
        $this->success_message = 'Anggota "' . $name . '" ditambahkan ke keluarga ' . $submission->head_of_family_name . ' dengan No. Daftar ' . $registrationNumber . ' (tanpa iuran tambahan).';
    }

    public function dismissAlert(): void
    {
        $this->success_message = '';
    }

    public function with(): array
    {
        $event = $this->activeEvent();

        $scoped = fn ($query) => $event ? $query->where('event_id', $event->id) : $query;

        // Data Warga hanya memuat pendaftaran yang belum/valid — pengajuan yang DITOLAK
        // tidak dianggap warga, jadi dikecualikan dari daftar & rekap.
        $notRejected = fn ($q) => $q->where('status', '!=', 'rejected');

        $query = FamilySubmission::query()
            ->tap($scoped)
            ->tap($notRejected)
            ->with(['familyMembers' => fn ($q) => $q->withCount('competitionParticipations')->orderBy('registration_number')])
            ->latest();

        if ($this->search !== '') {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('head_of_family_name', 'like', $term)
                    ->orWhere('resident_block', 'like', $term)
                    ->orWhere('reference_code', 'like', $term);
            });
        }

        $memberNotRejected = fn ($q) => $q->whereHas('familySubmission', $notRejected);

        return [
            'submissions' => $query->take(100)->get(),
            'totalHouseholds' => FamilySubmission::query()->tap($scoped)->tap($notRejected)->count(),
            'totalMembers' => FamilyMember::query()->tap($scoped)->tap($memberNotRejected)->count(),
            'totalChildren' => FamilyMember::query()->tap($scoped)->tap($memberNotRejected)->where('relationship', 'anak')->count(),
            'activeEventName' => $event?->name,
        ];
    }
};
?>

<div>
    @if ($success_message)
        <div class="mb-6 flex items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-emerald-800 shadow-sm">
            <span class="text-sm font-medium">{{ $success_message }}</span>
            <button wire:click="dismissAlert" class="text-lg font-bold leading-none text-emerald-500 hover:text-emerald-800">&times;</button>
        </div>
    @endif

    {{-- Sumber data: Data Warga dibaca langsung dari pendaftaran form warga publik. --}}
    <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-sky-200 bg-sky-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <p class="text-sm font-semibold text-sky-900">Data warga otomatis dari Form Warga</p>
                <p class="mt-0.5 text-xs leading-5 text-sky-800">Daftar di bawah diambil dari pendaftaran keluarga di halaman <span class="font-semibold">Form Warga</span> publik. Verifikasi tiap pengajuan di menu <span class="font-semibold">Pendaftaran Warga</span>.</p>
                @if ($activeEventName)
                    <p class="mt-1 text-xs text-sky-700">Acara aktif: <span class="font-semibold">{{ $activeEventName }}</span></p>
                @endif
            </div>
        </div>
        <a href="{{ route('public.family-form') }}" target="_blank" class="shrink-0 rounded-xl border border-sky-300 bg-white px-4 py-2 text-center text-sm font-semibold text-sky-700 transition hover:bg-sky-100">Buka Form Warga &rarr;</a>
    </div>

    {{-- List --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Daftar Warga</h3>
                    <p class="text-xs text-slate-500">{{ $totalHouseholds }} keluarga · {{ $totalMembers }} anggota · {{ $totalChildren }} anak</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.residents.export', ['format' => 'csv']) }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        <x-icon name="wallet" class="h-4 w-4" /> Excel
                    </a>
                    <a href="{{ route('admin.residents.export', ['format' => 'pdf']) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-3.5 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100">
                        <x-icon name="calendar" class="h-4 w-4" /> PDF
                    </a>
                </div>
            </div>
            <input type="text" wire:model.live.debounce.300ms="search" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 sm:max-w-xs" placeholder="Cari nama / blok / No. Ref...">
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 w-28">No. Daftar</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Hubungan</th>
                        <th class="px-4 py-3">Umur</th>
                        <th class="px-4 py-3">L/P</th>
                        <th class="px-4 py-3">Lomba</th>
                    </tr>
                </thead>
                @forelse ($submissions as $submission)
                    <tbody class="border-t-4 border-slate-100">
                        {{-- Header grup per keluarga --}}
                        <tr>
                            <td colspan="6" class="border-l-4 border-red-500 bg-slate-100 px-4 py-2.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                        <span class="text-sm font-bold text-slate-900">Keluarga {{ $submission->head_of_family_name }}</span>
                                        <span class="rounded-md border border-red-100 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Blok {{ $submission->resident_block ?: '-' }}</span>
                                        <span class="h-3.5 w-px bg-slate-300"></span>
                                        <span class="font-mono text-xs text-slate-500">{{ $submission->reference_code }}</span>
                                        <span class="h-3.5 w-px bg-slate-300"></span>
                                        <span class="text-xs text-slate-400">{{ $submission->familyMembers->count() }} anggota</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($submission->status === 'verified')
                                            <span class="rounded-full border border-emerald-100 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Terverifikasi</span>
                                        @elseif ($submission->status === 'rejected')
                                            <span class="rounded-full border border-red-100 bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-700">Ditolak</span>
                                        @else
                                            <span class="rounded-full border border-amber-100 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Menunggu</span>
                                        @endif
                                        <button wire:click="startAdd('{{ $submission->id }}')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">+ Tambah Anggota</button>
                                        @if (auth()->user()?->hasRole('admin'))
                                            <button wire:click="confirmDelete('{{ $submission->id }}', 'data warga ini beserta seluruh anggotanya')" class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-600 transition hover:bg-red-50">Hapus Keluarga</button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        {{-- Satu baris per anggota keluarga, tiap anggota punya No. Daftar sendiri --}}
                        @foreach ($submission->familyMembers as $member)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-2.5">
                                    <span class="rounded-md bg-red-700 px-2 py-0.5 font-mono text-xs font-bold tracking-wider text-white">{{ $member->registration_number ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-2.5 font-medium text-slate-900">
                                    {{ $member->name }}
                                    @if ($loop->first)
                                        <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">Kepala</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 capitalize text-slate-600">{{ $member->relationship }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $member->age !== null ? $member->age . ' th' : '-' }}</td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $member->gender ?: '-' }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($member->competition_participations_count > 0)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Ikut {{ $member->competition_participations_count }} lomba</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-500">Belum ikut</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        {{-- Form tambah anggota (muncul saat "+ Tambah Anggota" diklik untuk keluarga ini) --}}
                        @if ($addingToId === $submission->id)
                            <tr class="bg-emerald-50/40">
                                <td colspan="6" class="px-4 py-3">
                                    <p class="mb-2 text-xs font-semibold text-slate-600">Tambah anggota ke keluarga {{ $submission->head_of_family_name }} <span class="font-normal text-slate-400">— No. Daftar baru (lanjut nomor terakhir), tanpa iuran</span></p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input type="text" wire:model="newMemberName" placeholder="Nama anggota" class="min-w-[160px] flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                                        <select wire:model="newMemberRelationship" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                                            <option value="ayah">Ayah</option>
                                            <option value="ibu">Ibu</option>
                                            <option value="anak">Anak</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                        <select wire:model="newMemberGender" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                                            <option value="">L/P</option>
                                            <option value="L">Laki-laki</option>
                                            <option value="P">Perempuan</option>
                                        </select>
                                        <input type="number" wire:model="newMemberAge" placeholder="Umur" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500">
                                        <button wire:click="addMember" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-800">Tambah</button>
                                        <button wire:click="cancelAdd" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Batal</button>
                                    </div>
                                    @error('newMemberName') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                </td>
                            </tr>
                        @endif
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada warga terdaftar. Data akan muncul otomatis setelah warga mengisi Form Warga.</td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>

    <x-confirm-delete-modal :id="$confirmDeleteId" :label="$confirmDeleteLabel" />
</div>
