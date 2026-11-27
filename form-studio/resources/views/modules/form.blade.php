@extends('layouts.app')
@section('title', $module->exists ? 'Modifier le module' : 'Nouveau module')
@section('content')
<h1>{{ $module->exists ? 'Modifier le module' : 'Nouveau module' }}</h1>
<form class="panel form" method="post" action="{{ $module->exists ? route('modules.update', $module) : route('modules.store') }}">
    @csrf
    @if ($module->exists) @method('put') @endif
    <label>Intitulé *
        <input name="title" value="{{ old('title', $module->title) }}" required>
        @error('title')<span class="error">{{ $message }}</span>@enderror
    </label>
    <label>Référence (facultative)
        <input name="reference" value="{{ old('reference', $module->reference) }}" placeholder="BC01-UA03">
    </label>
    <label>Objectifs pédagogiques
        <textarea name="objectives" rows="4">{{ old('objectives', $module->objectives) }}</textarea>
    </label>
    <label>Prérequis
        <textarea name="prerequisites" rows="3">{{ old('prerequisites', $module->prerequisites) }}</textarea>
    </label>
    <label>Mots-clés (séparés par des virgules)
        <input name="keywords" value="{{ old('keywords', $module->keywords) }}">
    </label>
    <div class="actions">
        <button class="btn primary">Enregistrer</button>
        <a class="btn" href="{{ $module->exists ? route('modules.show', $module) : route('modules.index') }}">Annuler</a>
    </div>
</form>
@endsection
