@extends('layouts.app')
@section('title', 'Mon compte')
@section('content')
<h1>Mon compte</h1>

<section class="panel">
    <h2>Mes modules</h2>
    <p class="muted">Votre compte Indé’Form vous servira à vous connecter à chaque module de la suite.</p>
    <ul>
        @foreach ($modules as $module)
            <li><a href="{{ $module['url'] }}" rel="noopener"><strong>{{ $module['name'] }}</strong></a> : {{ $module['description'] }}</li>
        @endforeach
    </ul>
</section>

<section class="panel">
    <h2>Profil</h2>
    <form class="form" method="post" action="{{ route('account.profile') }}">
        @csrf @method('put')
        <label>Nom<input name="name" value="{{ old('name', $user->name) }}" required autocomplete="name"></label>
        <label>Adresse e-mail<input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
            <span class="muted small">Si vous la changez, un lien de confirmation sera envoyé à la nouvelle adresse.</span></label>
        <button class="btn primary">Enregistrer</button>
    </form>
</section>

@if ($workspace)
<section class="panel">
    <h2>Mon activité</h2>
    <p class="muted">Ces informations identifient votre activité de formateur ; Form’Pilot pourra les reprendre sur vos documents.</p>
    <form class="form" method="post" action="{{ route('account.workspace') }}">
        @csrf @method('put')
        <label>Nom de l’espace<input name="name" value="{{ old('name', $workspace->name) }}" required></label>
        <label>Raison sociale ou nom commercial<input name="legal_name" value="{{ old('legal_name', $workspace->legal_name) }}"></label>
        <label>SIRET<input name="siret" value="{{ old('siret', $workspace->siret) }}" inputmode="numeric"></label>
        <label>Adresse<textarea name="address" rows="3">{{ old('address', $workspace->address) }}</textarea></label>
        <button class="btn">Enregistrer</button>
    </form>
</section>
@endif

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
@endsection
