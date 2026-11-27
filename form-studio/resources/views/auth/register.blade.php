@extends('layouts.app')
@section('title', 'Créer un compte')
@section('content')
<h1>Créer un compte</h1>
<form class="panel form" method="post" action="{{ route('register') }}">
    @csrf
    <label>Nom
        <input name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
        @error('name')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Adresse e-mail
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        @error('email')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Mot de passe (10 caractères minimum)
        <input type="password" name="password" required autocomplete="new-password">
        @error('password')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Confirmer le mot de passe
        <input type="password" name="password_confirmation" required autocomplete="new-password">
    </label>
    <div class="actions">
        <button class="btn primary">Créer mon compte</button>
        <a href="{{ route('login') }}">J’ai déjà un compte</a>
    </div>
</form>
@endsection
