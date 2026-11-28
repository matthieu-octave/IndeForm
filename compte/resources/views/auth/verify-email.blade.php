@extends('layouts.app')
@section('title', 'Confirmer votre adresse')
@section('content')
<h1>Confirmez votre adresse e-mail</h1>
<section class="panel">
    <p>Un lien de confirmation a été envoyé à <strong>{{ auth()->user()->email }}</strong>.
        Cliquez dessus pour accéder à votre compte (pensez à regarder dans les indésirables).</p>
    <form method="post" action="{{ route('verification.send') }}" class="actions">
        @csrf
        <button class="btn primary">Renvoyer le lien</button>
    </form>
</section>
@endsection
