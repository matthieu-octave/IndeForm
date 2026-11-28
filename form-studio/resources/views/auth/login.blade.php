@extends('layouts.app')
@section('title', 'Connexion')
@section('content')
<h1>Connexion</h1>
<form class="panel form" method="post" action="{{ route('login') }}">
    @csrf
    <label>Adresse e-mail
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        @error('email')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Mot de passe
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <label class="check"><input type="checkbox" name="remember" value="1"> Rester connecté</label>
    <div class="actions">
        <button class="btn primary">Se connecter</button>
        @if (\App\Http\Controllers\AuthController::registrationAvailable())
            <a href="{{ route('register') }}">Créer un compte</a>
        @endif
    </div>
</form>
@endsection
