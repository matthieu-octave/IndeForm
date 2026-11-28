@extends('layouts.app')
@section('title', 'Mon compte')
@section('content')
<h1>Mon compte</h1>

<section class="panel">
    <h2>Profil</h2>
    <form class="form" method="post" action="{{ route('account.profile') }}">
        @csrf @method('put')
        <label>Nom<input name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"></label>
        <label>Adresse e-mail<input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"></label>
        <button class="btn primary">Enregistrer</button>
    </form>
</section>

<section class="panel">
    <h2>Mot de passe</h2>
    <form class="form" method="post" action="{{ route('account.password') }}">
        @csrf @method('put')
        <label>Mot de passe actuel<input type="password" name="current_password" required autocomplete="current-password"></label>
        <label>Nouveau mot de passe (10 caractères minimum)<input type="password" name="password" required autocomplete="new-password"></label>
        <label>Confirmer le nouveau mot de passe<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="btn">Changer le mot de passe</button>
    </form>
</section>

<section class="panel">
    <h2>Mes données</h2>
    <p class="muted">Téléchargez l’ensemble de vos modules, séances, déroulés, liens et modèles personnels dans un fichier JSON.
        Chaque module peut ensuite être réimporté depuis la bibliothèque.</p>
    <div><a class="btn" href="{{ route('account.export') }}">Exporter toutes mes données</a></div>
</section>

<section class="panel danger-zone">
    <h2>Supprimer mon compte</h2>
    <p>La suppression est <strong>définitive</strong> : votre compte, vos modules, séances, liens et modèles personnels sont effacés.
        Pensez à exporter vos données avant.</p>
    <form class="form" method="post" action="{{ route('account.destroy') }}" onsubmit="return confirm('Supprimer définitivement votre compte et toutes vos données ?')">
        @csrf @method('delete')
        <label>Mot de passe<input type="password" name="password" required autocomplete="current-password"></label>
        <label class="check"><input type="checkbox" name="confirm" value="1" required> Je comprends que mes données seront supprimées définitivement.</label>
        <button class="btn danger">Supprimer mon compte</button>
    </form>
</section>
@endsection
