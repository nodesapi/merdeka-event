<!DOCTYPE html>
<html lang="id">
<head>
    @php $site = \App\Models\SiteSetting::current(); @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bagan Turnamen — {{ $competition->name }}{{ $site->site_name ? ' - ' . $site->site_name : '' }}</title>
    @if ($site->favicon_url)
        <link rel="icon" href="{{ $site->favicon_url }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white">
    <livewire:bracket-display :competition="$competition" />
</body>
</html>
