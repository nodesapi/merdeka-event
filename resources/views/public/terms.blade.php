<x-layouts.public title="Syarat & Ketentuan" :eventName="$event?->name">
    <div class="mx-auto max-w-3xl text-left">
        <div class="flex items-center gap-3">
            <span class="hidden h-8 w-1.5 rounded-full bg-red-600 md:block"></span>
            <div class="text-center md:text-left">
                <span class="merdeka-badge">Ketentuan</span>
                <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Syarat &amp; Ketentuan</h1>
                <p class="mt-1 text-sm text-stone-500">Mohon dibaca sebelum mengisi form warga.</p>
            </div>
        </div>

        <div class="merdeka-card mt-6 p-6 sm:p-8">
            <div class="text-sm leading-7 text-stone-700">
                {!! nl2br(e($site?->terms_conditions_text ?? \App\Models\SiteSetting::DEFAULT_TERMS)) !!}
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('public.family-form') }}" class="inline-flex items-center gap-2 rounded-xl bg-red-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-800">
                <x-icon name="clipboard" class="h-4 w-4" /> Kembali ke Form Warga
            </a>
        </div>
    </div>
</x-layouts.public>
