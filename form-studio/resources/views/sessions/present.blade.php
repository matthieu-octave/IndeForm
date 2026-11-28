@extends('layouts.app')
@section('title', $session->title)
@section('content')
@php($D = \App\Support\Duration::class)
<div class="head no-print">
    <a class="btn" href="{{ route('modules.show', $session->module) }}#seance-{{ $session->id }}">← Retour au module</a>
    <form class="inline" method="get" action="{{ route('sessions.present', $session) }}">
        <label class="inline-label">Début <input type="time" name="debut" value="{{ $start }}"></label>
        <button class="btn">Afficher les heures</button>
    </form>
    <button class="btn primary" onclick="window.print()">Imprimer</button>
</div>

<article class="present" id="present" data-steps="{{ $session->steps->count() }}">
    <p class="muted">{{ $session->module->title }} @if ($session->module->reference)· {{ $session->module->reference }}@endif</p>
    <h1>{{ $session->title }}</h1>
    @if ($session->objectives)<p class="pre">{{ $session->objectives }}</p>@endif
    <p><strong>Durée totale : {{ $D::format($session->steps->sum('minutes')) }}</strong>
        @if ($start) · de {{ $start }} à {{ $D::clock($start, $session->steps->sum('minutes')) }} @endif</p>

    @if ($session->steps->isNotEmpty())
    <div class="timer no-print" id="timer" hidden>
        <div><span class="muted small">Étape en cours</span><strong id="t-step">00:00</strong><span id="t-left" class="small"></span></div>
        <div><span class="muted small">Séance</span><strong id="t-total">00:00</strong><span id="t-drift" class="small"></span></div>
        <div class="actions">
            <button class="btn primary" id="t-start" type="button">Démarrer</button>
            <button class="btn" id="t-next" type="button" disabled>Étape suivante</button>
            <button class="btn" id="t-pause" type="button" disabled>Pause</button>
            <button class="btn" id="t-stop" type="button" disabled>Terminer</button>
        </div>
    </div>
    @endif

    <ol class="timeline">
    @php($elapsed = 0)
    @foreach ($session->steps as $step)
        <li data-minutes="{{ $step->minutes }}" data-id="{{ $step->id }}">
            <span class="time">{{ $start ? $D::clock($start, $elapsed) : $D::format($elapsed) }}</span>
            <div class="grow"><h2>{{ $step->title }} <small>{{ $D::format($step->minutes) }}</small>
                <small class="live no-print"></small></h2>
            @if ($step->instructions)<p class="pre">{{ $step->instructions }}</p>@endif</div>
        </li>
        @php($elapsed += $step->minutes)
    @endforeach
    </ol>

    @if ($session->resources->isNotEmpty())
        <h2>Supports et exercices</h2>
        <ul class="resources print-urls">
        @foreach ($session->resources as $resource)
            <li><span><span class="tag type-{{ $resource->type }}">{{ $resource->typeLabel() }}</span>
                <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer">{{ $resource->title }}</a></span></li>
        @endforeach
        </ul>
    @endif

    @if ($session->notes)<h2>Notes de la dernière séance</h2><p class="pre">{{ $session->notes }}</p>@endif
</article>

@if ($session->steps->isNotEmpty())
<section class="panel no-print" id="reel">
    <h2>Prévu / réel</h2>
    <p class="muted small">Le chronomètre remplit ces durées quand vous cliquez sur « Terminer ». Vous pouvez aussi les saisir à la main.</p>
    <form class="form" method="post" action="{{ route('sessions.actuals', $session) }}" id="actuals">
        @csrf @method('put')
        <table class="compare">
            <thead><tr><th>Étape</th><th>Prévu</th><th>Réel (min)</th><th>Écart</th></tr></thead>
            <tbody>
            @foreach ($session->steps as $step)
                <tr>
                    <td>{{ $step->title }}</td>
                    <td>{{ $D::format($step->minutes) }}</td>
                    <td><input type="number" min="0" max="1440" name="actual[{{ $step->id }}]" value="{{ $step->actual_minutes }}" data-step="{{ $step->id }}" aria-label="Durée réelle de {{ $step->title }}"></td>
                    <td class="{{ $step->actual_minutes === null ? '' : ($step->actual_minutes > $step->minutes ? 'over' : 'under') }}">
                        {{ $step->actual_minutes === null ? '—' : $D::delta($step->actual_minutes - $step->minutes) }}</td>
                </tr>
            @endforeach
            </tbody>
            @php($actual = $session->actualTotalMinutes())
            <tfoot><tr><th>Total</th><th>{{ $D::format($session->steps->sum('minutes')) }}</th>
                <th>{{ $actual === null ? '—' : $D::format($actual) }}</th>
                <th>{{ $actual === null ? '' : $D::delta($actual - $session->steps->sum('minutes')) }}</th></tr></tfoot>
        </table>
        <div class="actions">
            <button class="btn primary">Enregistrer les durées réelles</button>
            <button class="btn" name="reset" value="1" onclick="return confirm('Effacer les durées réelles ?')">Effacer</button>
        </div>
    </form>
</section>

<script>
(() => {
    const items = [...document.querySelectorAll('.timeline li')];
    const box = document.getElementById('timer');
    if (!items.length || !box) return;
    box.hidden = false;
    const $ = id => document.getElementById(id);
    const fmt = s => { s = Math.max(0, Math.round(s)); const h = Math.floor(s / 3600), m = Math.floor(s % 3600 / 60), r = s % 60;
        return (h ? h + ':' + String(m).padStart(2, '0') : String(m).padStart(2, '0')) + ':' + String(r).padStart(2, '0'); };
    const spent = items.map(() => 0);
    let current = -1, running = false, last = 0, timer = null;

    const tick = () => {
        const now = Date.now();
        if (running && current >= 0) { spent[current] += (now - last) / 1000; }
        last = now;
        const planned = current >= 0 ? items[current].dataset.minutes * 60 : 0;
        const stepSpent = current >= 0 ? spent[current] : 0;
        const total = spent.reduce((a, b) => a + b, 0);
        const plannedSoFar = items.slice(0, Math.max(current, 0)).reduce((a, li) => a + li.dataset.minutes * 60, 0) + Math.min(stepSpent, planned);
        $('t-step').textContent = fmt(stepSpent);
        $('t-total').textContent = fmt(total);
        const left = planned - stepSpent;
        $('t-left').textContent = current < 0 ? '' : (left >= 0 ? 'reste ' + fmt(left) : 'dépassement ' + fmt(-left));
        $('t-left').className = 'small ' + (left < 0 ? 'over' : '');
        const drift = (total - plannedSoFar) / 60;
        $('t-drift').textContent = current < 0 || Math.abs(drift) < 1 ? '' : (drift > 0 ? 'retard ' : 'avance ') + Math.round(Math.abs(drift)) + ' min';
        items.forEach((li, i) => {
            li.classList.toggle('current', i === current);
            li.classList.toggle('done', i < current);
            li.querySelector('.live').textContent = spent[i] ? '· ' + fmt(spent[i]) : '';
        });
    };
    const go = i => { current = i; tick(); items[i]?.scrollIntoView({block: 'center', behavior: 'smooth'}); };

    $('t-start').onclick = () => {
        running = true; last = Date.now(); if (current < 0) go(0);
        timer ??= setInterval(tick, 500);
        $('t-start').disabled = true; ['t-next', 't-pause', 't-stop'].forEach(id => $(id).disabled = false);
        $('t-start').textContent = 'Reprendre';
    };
    $('t-pause').onclick = () => { tick(); running = false; $('t-start').disabled = false; $('t-pause').disabled = true; };
    $('t-next').onclick = () => { tick(); if (current < items.length - 1) go(current + 1); else $('t-stop').click(); };
    $('t-stop').onclick = () => {
        tick(); running = false; clearInterval(timer); timer = null;
        items.forEach((li, i) => { if (spent[i] > 0) document.querySelector(`input[data-step="${li.dataset.id}"]`).value = Math.max(1, Math.round(spent[i] / 60)); });
        ['t-next', 't-pause', 't-stop'].forEach(id => $(id).disabled = true);
        $('t-start').disabled = true;
        document.getElementById('reel').scrollIntoView({behavior: 'smooth'});
    };
    window.addEventListener('beforeunload', e => { if (running) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endif
@endsection
