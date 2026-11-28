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
            <a href="{{ route('templates.index') }}">Modèles de séance</a>
            <form method="post" action="{{ route('logout') }}" class="inline">@csrf
                <button class="linkbtn">{{ auth()->user()->name }} · Se déconnecter</button></form>
        @endauth
    </nav>
</header>
<main class="page">
    @if (session('status'))
        <div class="flash no-print">{{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! request()->routeIs('login', 'register', 'modules.create', 'modules.edit'))
        <div class="flash error-box no-print" role="alert">
            <strong>Impossible d’enregistrer :</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
