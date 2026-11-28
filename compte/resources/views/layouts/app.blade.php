<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Mon compte') · Compte Indé’Form</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('home') }}">Indé’Form <small>mon compte</small></a>
    <nav>
        @auth
            <a href="{{ route('account.show') }}">{{ auth()->user()->name }}</a>
            <form method="post" action="{{ route('logout') }}" class="inline">@csrf
                <button class="linkbtn">Se déconnecter</button></form>
        @endauth
    </nav>
</header>
<main class="page">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! request()->routeIs('login', 'register', 'password.*'))
        <div class="flash error-box" role="alert">
            <strong>Impossible d’enregistrer :</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
<footer class="footer">
    <span>Indé’Form · {{ config('compte.publisher.trade_name') }}</span>
    <a href="{{ route('legal.notice') }}">Mentions légales</a>
    <a href="{{ route('legal.privacy') }}">Confidentialité</a>
</footer>
</body>
</html>
