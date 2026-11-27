@extends('layouts.app')
@section('title', 'Bibliothèque de modules')
@section('content')
<div class="head">
    <h1>Bibliothèque de modules</h1>
    <a class="btn primary" href="{{ route('modules.create') }}">Nouveau module</a>
</div>
<form class="panel search" method="get" action="{{ route('modules.index') }}">
    <input type="search" name="q" value="{{ $q }}" placeholder="Rechercher par intitulé, référence ou mot-clé">
    <button class="btn">Rechercher</button>
</form>
<div class="panel">
@forelse ($modules as $module)
    <a class="row" href="{{ route('modules.show', $module) }}">
        <span>
            <strong>{{ $module->title }}</strong>
            @if ($module->reference)<span class="tag">{{ $module->reference }}</span>@endif
            @if ($module->duplicated_from_id)<span class="tag muted">copie</span>@endif
        </span>
        <span class="muted">{{ $module->sessions_count }} séance(s) · {{ \App\Support\Duration::format($module->totalMinutes()) }}</span>
    </a>
@empty
    <p class="muted">{{ $q ? 'Aucun module ne correspond à la recherche.' : 'Aucun module pour le moment. Créez le premier pour commencer.' }}</p>
@endforelse
</div>
@endsection
