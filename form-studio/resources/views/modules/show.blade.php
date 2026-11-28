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

<section class="panel" id="ressources">
    <h2>Supports et exercices du module</h2>
    @include('resources._list', ['resources' => $module->resources->whereNull('teaching_session_id')])
    @include('resources._form', ['sessionId' => null])
</section>

@foreach ($module->sessions as $session)
<section class="panel" id="seance-{{ $session->id }}">
    <div class="head">
        <h2>Séance {{ $loop->iteration }} — {{ $session->title }}
            <span class="tag muted">{{ \App\Support\Duration::format($session->steps->sum('minutes')) }}</span></h2>
        <div class="actions">
            <form method="post" action="{{ route('sessions.move', $session) }}" class="inline">
                @csrf
                <button class="btn small" name="direction" value="up" @disabled($loop->first) aria-label="Monter la séance" title="Monter">↑</button>
                <button class="btn small" name="direction" value="down" @disabled($loop->last) aria-label="Descendre la séance" title="Descendre">↓</button>
            </form>
            <a class="btn" href="{{ route('sessions.present', $session) }}">Vue pour animer</a>
            <form method="post" action="{{ route('sessions.destroy', $session) }}" class="inline" onsubmit="return confirm('Supprimer cette séance ?')">
                @csrf @method('delete')
                <button class="btn danger">Supprimer</button>
            </form>
        </div>
    </div>
    @if ($session->objectives)<p class="pre">{{ $session->objectives }}</p>@endif

    <details class="edit">
        <summary>Modifier ou copier la séance</summary>
        <form class="form" method="post" action="{{ route('sessions.update', $session) }}">
            @csrf @method('put')
            <label>Intitulé *<input name="title" value="{{ $session->title }}" required></label>
            <label>Objectifs de la séance<textarea name="objectives" rows="2">{{ $session->objectives }}</textarea></label>
            <div class="actions"><button class="btn small primary">Enregistrer</button></div>
        </form>
        <form class="inline" method="post" action="{{ route('sessions.duplicate', $session) }}">
            @csrf
            <select name="course_module_id" aria-label="Module de destination">
                <option value="">Dans ce module</option>
                @foreach ($otherModules as $other)
                    <option value="{{ $other->id }}">Vers « {{ $other->title }} »</option>
                @endforeach
            </select>
            <button class="btn small">Copier la séance</button>
        </form>
    </details>

    <ol class="steps">
    @forelse ($session->steps as $step)
        <li>
            <div class="grow">
                <strong>{{ $step->title }}</strong> <span class="tag muted">{{ \App\Support\Duration::format($step->minutes) }}</span>
                @if ($step->instructions)<p class="pre">{{ $step->instructions }}</p>@endif
                <details class="edit">
                    <summary>Modifier</summary>
                    <form class="form inline-form" method="post" action="{{ route('steps.update', $step) }}">
                        @csrf @method('put')
                        <input name="title" value="{{ $step->title }}" required aria-label="Intitulé de l’étape">
                        <input name="minutes" type="number" min="0" max="1440" value="{{ $step->minutes }}" required aria-label="Minutes">
                        <textarea name="instructions" rows="2" aria-label="Consignes" placeholder="Consignes">{{ $step->instructions }}</textarea>
                        <button class="btn small primary">Enregistrer</button>
                    </form>
                </details>
            </div>
            <div class="step-actions">
                <form method="post" action="{{ route('steps.move', $step) }}" class="inline">
                    @csrf
                    <button class="btn small" name="direction" value="up" @disabled($loop->first) aria-label="Monter l’étape" title="Monter">↑</button>
                    <button class="btn small" name="direction" value="down" @disabled($loop->last) aria-label="Descendre l’étape" title="Descendre">↓</button>
                </form>
                <form method="post" action="{{ route('steps.destroy', $step) }}" class="inline">
                    @csrf @method('delete')
                    <button class="btn small danger" aria-label="Supprimer l’étape" title="Supprimer">×</button>
                </form>
            </div>
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

    <div class="resources-block">
        <h3>Supports et exercices de la séance</h3>
        @include('resources._list', ['resources' => $module->resources->where('teaching_session_id', $session->id)])
        @include('resources._form', ['sessionId' => $session->id])
    </div>

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
