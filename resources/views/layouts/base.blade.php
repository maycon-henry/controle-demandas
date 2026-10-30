<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#13284D">
    <title>@yield('titulo', 'Controle de Demandas') · Prazo Certo</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icone.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/icone-192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
</head>
<body class="@yield('classe-body')">
    @yield('corpo')
    @if (session('aviso'))
        <div class="toast visivel" role="status" data-auto-fechar>{{ session('aviso') }}</div>
    @endif
    <script>
        document.querySelectorAll('[data-auto-fechar]').forEach(t => setTimeout(() => t.classList.remove('visivel'), 3000));
        if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});
    </script>
    @stack('scripts')
</body>
</html>
