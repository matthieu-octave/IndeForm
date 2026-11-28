@extends('layouts.app')
@section('title', 'Modèles de séance')
@section('content')
<div class="head">
    <div>
        <h1>Modèles de séance</h1>
        <p class="muted">Choisissez un modèle à l’ajout d’une séance dans un module : son déroulé est copié, puis la séance vit sa vie.
            Pour créer vos propres modèles, ouvrez une séance et cliquez sur « Enregistrer comme modèle ».</p>
    </div>
</div>

<section class="panel">
    <h2>Mes modèles</h2>
    @forelse ($mine as $template)
        @include('templates._card', ['template' => $template])
    @empty
        <p class="muted">Aucun modèle personnel pour le moment.</p>
    @endforelse
</section>

<section class="panel">
    <h2>Modèles fournis par Indé’Form</h2>
    @foreach ($provided as $template)
        @include('templates._card', ['template' => $template])
    @endforeach
</section>
@endsection
