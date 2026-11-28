@extends('layouts.app')
@section('title', 'Mentions légales')
@section('content')
@php($p = config('studio.publisher'))
@php($h = config('studio.host'))
<article class="panel legal">
    <h1>Mentions légales</h1>

    <h2>Éditeur</h2>
    <p>Form’Studio est un service de la solution Indé’Form, édité par<br>
        <strong>{{ $p['name'] }}</strong>, nom commercial <strong>{{ $p['trade_name'] }}</strong><br>
        {{ $p['status'] }}<br>
        SIRET : {{ $p['siret'] }}<br>
        Adresse : {{ $p['address'] }}<br>
        Contact : <a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a></p>
    <p>Directeur de la publication : {{ $p['director'] }}.</p>

    <h2>Hébergement</h2>
    <p>{{ $h['name'] }}, {{ $h['address'] }}<br>
        Téléphone : {{ $h['phone'] }} · <a href="{{ $h['website'] }}" rel="noopener">{{ $h['website'] }}</a><br>
        Les données sont hébergées {{ $h['data_location'] }}.</p>

    <h2>Propriété intellectuelle</h2>
    <p>L’application Form’Studio, sa présentation et ses modèles de séance fournis sont la propriété de {{ $p['trade_name'] }}.
        Les contenus que vous créez (modules, séances, déroulés, liens) restent votre propriété : vous pouvez les exporter à tout moment depuis la page « Mon compte ».</p>

    <h2>Données personnelles</h2>
    <p>Voir la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>

    <p class="muted small">Dernière mise à jour : 28 novembre 2026.</p>
</article>
@endsection
