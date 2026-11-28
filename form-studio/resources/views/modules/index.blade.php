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
<details class="panel no-print">
    <summary>Importer un module (fichier .json exporté depuis Form’Studio)</summary>
    <form class="inline" method="post" action="{{ route('modules.import') }}" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" accept=".json,application/json" required>
        <button class="btn">Importer</button>
    </form>
</details>
@if ($modules->hasPages())
<nav class="pager actions" aria-label="Pagination">
    @if ($modules->onFirstPage())<span class="btn" aria-disabled="true">← Précédent</span>@else<a class="btn" href="{{ $modules->previousPageUrl() }}">← Précédent</a>@endif
    <span class="muted">Page {{ $modules->currentPage() }} sur {{ $modules->lastPage() }}</span>
    @if ($modules->hasMorePages())<a class="btn" href="{{ $modules->nextPageUrl() }}">Suivant →</a>@else<span class="btn" aria-disabled="true">Suivant →</span>@endif
</nav>
@endif
@endsection
