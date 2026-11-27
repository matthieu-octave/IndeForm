<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Modules') · Form’Studio</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar no-print">
    <a class="brand" href="{{ route('modules.index') }}">Form’Studio <small>une solution Indé’Form</small></a>
    <nav>
        @auth
            <a href="{{ route('modules.index') }}">Bibliothèque</a>
            <form method="post" action="{{ route('logout') }}" class="inline">@csrf
                <button class="linkbtn">{{ auth()->user()->name }} · Se déconnecter</button></form>
        @endauth
    </nav>
</header>
<main class="page">
    @if (session('status'))
        <div class="flash no-print">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>
