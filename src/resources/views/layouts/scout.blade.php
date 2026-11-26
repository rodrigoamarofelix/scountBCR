<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f59e0b">
    <link rel="manifest" href="{{ asset('manifest-scout.webmanifest') }}">
    <title>{{ $title ?? 'Scouting' }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/scout.js'])
    @livewireStyles
</head>
<body class="bg-zinc-950 antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>