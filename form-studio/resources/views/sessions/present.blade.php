@extends('layouts.app')
@section('title', $session->title)
@section('content')
<div class="head no-print">
    <a class="btn" href="{{ route('modules.show', $session->module) }}">← Retour au module</a>
    <button class="btn primary" onclick="window.print()">Imprimer</button>
</div>
<article class="present">
    <p class="muted">{{ $session->module->title }} @if ($session->module->reference)· {{ $session->module->reference }}@endif</p>
    <h1>{{ $session->title }}</h1>
    @if ($session->objectives)<p class="pre">{{ $session->objectives }}</p>@endif
    <p><strong>Durée totale : {{ \App\Support\Duration::format($session->steps->sum('minutes')) }}</strong></p>
    <ol class="timeline">
    @php($elapsed = 0)
    @foreach ($session->steps as $step)
        <li>
            <span class="time">{{ \App\Support\Duration::format($elapsed) }}</span>
            <div><h2>{{ $step->title }} <small>{{ \App\Support\Duration::format($step->minutes) }}</small></h2>
            @if ($step->instructions)<p class="pre">{{ $step->instructions }}</p>@endif</div>
        </li>
        @php($elapsed += $step->minutes)
    @endforeach
    </ol>
    @if ($session->notes)<h2>Notes de la dernière séance</h2><p class="pre">{{ $session->notes }}</p>@endif
</article>
@endsection
