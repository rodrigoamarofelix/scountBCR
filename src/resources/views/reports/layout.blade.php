<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'ScountBCR' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .summary { margin: 12px 0; }
        .summary span { display: inline-block; margin-right: 16px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">ScountBCR · Gerado em {{ $generated_at }}</p>
    @yield('content')
</body>
</html>