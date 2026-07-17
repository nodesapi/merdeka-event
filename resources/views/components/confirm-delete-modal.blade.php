@props(['id', 'label' => 'data ini'])

@if ($id)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4">
        <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
            <h3 class="text-base font-bold text-slate-900">Hapus {{ $label }}?</h3>
            <p class="mt-1.5 text-sm text-slate-500">Tindakan ini tidak bisa dibatalkan. Ketik <span class="font-mono font-bold text-red-600">HAPUS</span> untuk konfirmasi.</p>
            <input type="text" wire:model="confirmDeleteInput" wire:keydown.enter="executeDelete" autofocus autocomplete="off" class="mt-3 w-full rounded-md border border-slate-300 px-3 py-2 text-sm uppercase tracking-wide focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Ketik HAPUS">
            @error('confirmDeleteInput') <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50">Batal</button>
                <button type="button" wire:click="executeDelete" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">Ya, Hapus</button>
            </div>
        </div>
    </div>
@endif
