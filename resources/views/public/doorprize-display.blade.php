<!DOCTYPE html>
<html lang="id">
<head>
    @php $site = \App\Models\SiteSetting::current(); @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Undian Doorprize{{ $site->site_name ? ' - ' . $site->site_name : '' }}</title>
    @if ($site->favicon_url)
        <link rel="icon" href="{{ $site->favicon_url }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-red-900 via-red-800 to-red-950">
    <livewire:doorprize-display />
</body>
</html>
