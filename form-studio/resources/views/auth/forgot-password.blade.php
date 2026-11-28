@extends('layouts.app')
@section('title', 'Mot de passe oublié')
@section('content')
<h1>Mot de passe oublié</h1>
<form class="panel form" method="post" action="{{ route('password.email') }}">
    @csrf
    <p class="muted">Indiquez l’adresse de votre compte : vous recevrez un lien pour choisir un nouveau mot de passe.</p>
    <label>Adresse e-mail
        <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        @error('email')<span class="error">{{ $message }}</span>@enderror
    </label>
    <div class="actions">
        <button class="btn primary">Envoyer le lien</button>
        <a href="{{ route('login') }}">Retour à la connexion</a>
    </div>
</form>
@endsection
