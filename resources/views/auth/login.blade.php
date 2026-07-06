@php
    $siteName = $site?->site_name ?: config('app.name', 'Portal Warga');
    $tagline = $site?->tagline ?: 'Portal Informasi Warga';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Panitia - {{ $siteName }}</title>
    @if ($site?->favicon_url)
        <link rel="icon" href="{{ $site->favicon_url }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="merdeka-shell flex min-h-screen flex-col">
        <div class="merdeka-ribbon h-3 w-full"></div>

        <div class="flex flex-1 items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <div class="text-center">
                    <p class="text-[11px] font-black uppercase tracking-[0.35em] text-red-700">{{ $tagline }}</p>
                    <h1 class="mt-2 text-2xl font-black tracking-tight text-stone-900">Masuk Panel Panitia</h1>
                    <p class="mt-2 text-sm text-stone-600">Silakan masuk untuk mengelola {{ $siteName }}, panitia, lomba, dan data peserta.</p>
                </div>

                <div class="merdeka-section mt-7 px-6 pb-7 pt-9 lg:px-8">
                    @if ($errors->any())
                        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-[0.16em] text-stone-600">Username</label>
                            <input type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                                   class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                   placeholder="superadmin">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-[0.16em] text-stone-600">Kata Sandi</label>
                            <input type="password" name="password" required
                                   class="mt-2 w-full rounded-xl border border-stone-300 px-4 py-2.5 text-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                   placeholder="********">
                        </div>
                        <label class="flex items-center gap-2 text-sm text-stone-600">
                            <input type="checkbox" name="remember" class="rounded border-stone-300 text-red-700 focus:ring-red-500">
                            Ingat saya
                        </label>
                        <button type="submit" class="w-full rounded-xl bg-red-700 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-800">
                            Masuk
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm text-stone-500">
                    <a href="{{ route('public.home') }}" class="font-semibold text-red-700 hover:underline">&larr; Kembali ke halaman warga</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
