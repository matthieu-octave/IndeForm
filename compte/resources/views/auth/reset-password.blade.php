@extends('layouts.app')
@section('title', 'Nouveau mot de passe')
@section('content')
<h1>Choisir un nouveau mot de passe</h1>
<form class="panel form" method="post" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label>Adresse e-mail
        <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
        @error('email')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Nouveau mot de passe (10 caractères minimum)
        <input type="password" name="password" required autofocus autocomplete="new-password">
        @error('password')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Confirmer le mot de passe
        <input type="password" name="password_confirmation" required autocomplete="new-password">
    </label>
    <button class="btn primary">Enregistrer</button>
</form>
@endsection
