@extends('layouts.app')
@section('title', $module->title)
@section('content')
<div class="head">
    <div>
        <h1>{{ $module->title }} @if ($module->reference)<span class="tag">{{ $module->reference }}</span>@endif</h1>
        <p class="muted">Durée prévue : {{ \App\Support\Duration::format($module->totalMinutes()) }}
            @if ($module->keywords) · {{ $module->keywords }} @endif</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('modules.edit', $module) }}">Modifier</a>
        <form method="post" action="{{ route('modules.duplicate', $module) }}" class="inline">
            @csrf
            <input name="title" placeholder="Titre de la copie (ex. Architecture hexagonale – EADL)" size="38">
            <button class="btn">Dupliquer</button>
        </form>
        <form method="post" action="{{ route('modules.destroy', $module) }}" class="inline" onsubmit="return confirm('Supprimer ce module et ses séances ?')">
            @csrf @method('delete')
            <button class="btn danger">Supprimer</button>
        </form>
    </div>
</div>

@if ($module->objectives || $module->prerequisites)
<div class="panel">
    @if ($module->objectives)<h2>Objectifs</h2><p class="pre">{{ $module->objectives }}</p>@endif
    @if ($module->prerequisites)<h2>Prérequis</h2><p class="pre">{{ $module->prerequisites }}</p>@endif
</div>
@endif

@foreach ($module->sessions as $session)
<section class="panel">
    <div class="head">
        <h2>Séance {{ $loop->iteration }} — {{ $session->title }}
            <span class="tag muted">{{ \App\Support\Duration::format($session->steps->sum('minutes')) }}</span></h2>
        <div class="actions">
            <a class="btn" href="{{ route('sessions.present', $session) }}">Vue pour animer</a>
            <form method="post" action="{{ route('sessions.destroy', $session) }}" class="inline" onsubmit="return confirm('Supprimer cette séance ?')">
                @csrf @method('delete')
                <button class="btn danger">Supprimer</button>
            </form>
        </div>
    </div>
    @if ($session->objectives)<p class="pre">{{ $session->objectives }}</p>@endif

    <ol class="steps">
    @forelse ($session->steps as $step)
        <li>
            <div>
                <strong>{{ $step->title }}</strong> <span class="tag muted">{{ \App\Support\Duration::format($step->minutes) }}</span>
                @if ($step->instructions)<p class="pre">{{ $step->instructions }}</p>@endif
            </div>
            <form method="post" action="{{ route('steps.destroy', $step) }}">
                @csrf @method('delete')
                <button class="btn small danger" aria-label="Supprimer l’étape">×</button>
            </form>
        </li>
    @empty
        <li class="muted">Aucune étape : ajoutez le déroulé ci-dessous.</li>
    @endforelse
    </ol>

    <form class="form inline-form" method="post" action="{{ route('steps.store', $session) }}">
        @csrf
        <input name="title" placeholder="Nouvelle étape (ex. Démonstration)" required>
        <input name="minutes" type="number" min="0" max="1440" value="30" required aria-label="Minutes">
        <input name="instructions" placeholder="Consignes (facultatif)">
        <button class="btn">Ajouter l’étape</button>
    </form>

    <form class="form notes" method="post" action="{{ route('sessions.update', $session) }}">
        @csrf @method('put')
        <label>Notes après la séance
            <textarea name="notes" rows="3" placeholder="Ce qui a pris plus de temps, les difficultés, ce que je modifie la prochaine fois">{{ $session->notes }}</textarea>
        </label>
        <button class="btn small">Enregistrer les notes</button>
    </form>
</section>
@endforeach

<section class="panel">
    <h2>Ajouter une séance</h2>
    <form class="form" method="post" action="{{ route('sessions.store', $module) }}">
        @csrf
        <label>Intitulé *<input name="title" required></label>
        <label>Objectifs de la séance<textarea name="objectives" rows="2"></textarea></label>
        <button class="btn primary">Ajouter la séance</button>
    </form>
</section>
@endsection
