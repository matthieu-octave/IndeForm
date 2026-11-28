@extends('layouts.app')
@section('title', 'Politique de confidentialité')
@section('content')
@php($p = config('compte.publisher'))
@php($h = config('compte.host'))
<article class="panel legal">
    <h1>Politique de confidentialité</h1>

    <h2>Responsable du traitement</h2>
    <p>{{ $p['name'] }} ({{ $p['trade_name'] }}), {{ $p['address'] }}. Contact : <a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a>.</p>

    <h2>Données traitées et finalités</h2>
    <ul>
        <li><strong>Compte</strong> : nom, adresse e-mail, mot de passe (stocké haché, illisible même pour l’éditeur). Pour vous identifier sur tous les modules Indé’Form et vous envoyer les e-mails de service (confirmation d’adresse, réinitialisation du mot de passe).</li>
        <li><strong>Activité</strong> : nom de l’espace, raison sociale, SIRET et adresse, si vous les renseignez. Pour les reprendre dans les modules que vous utilisez.</li>
        <li><strong>Sécurité</strong> : journal des connexions et des changements sensibles (date, adresse IP, navigateur), conservé un an ; cookie de session et, si vous cochez « Rester connecté », cookie de connexion.</li>
    </ul>
    <p>Base légale : l’exécution du contrat d’utilisation du service et, pour le journal de sécurité, l’intérêt légitime de l’éditeur.</p>

    <h2>Cookies</h2>
    <p>Seuls des cookies strictement nécessaires sont utilisés (session, sécurité des formulaires, « rester connecté »). Aucun cookie publicitaire ni outil de mesure d’audience : aucun consentement n’est donc demandé.</p>

    <h2>Destinataires</h2>
    <p>Vos données ne sont ni vendues ni partagées. Seuls y accèdent l’éditeur, pour la maintenance, l’hébergeur ({{ $h['name'] }}) en tant que sous-traitant technique,
        et les modules Indé’Form auxquels vous vous connectez.</p>

    <h2>Lieu d’hébergement</h2>
    <p>Vos données sont hébergées {{ $h['data_location'] }}, au sein de l’Union européenne. L’hébergeur est une société canadienne ; un éventuel accès de son support technique depuis le Canada
        est encadré par la décision d’adéquation de la Commission européenne.</p>

    <h2>Durée de conservation</h2>
    <p>Vos données sont conservées tant que votre compte existe, puis disparaissent des sauvegardes au bout de {{ config('compte.backup_days') }} jours après leur suppression.</p>

    <h2>Vos droits</h2>
    <p>Vous disposez des droits d’accès, de rectification, d’effacement, de limitation, d’opposition et de portabilité. Vous pouvez modifier vos informations depuis la page « Mon compte »
        ou écrire à <a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a>. Vous pouvez aussi adresser une réclamation à la CNIL (<a href="https://www.cnil.fr" rel="noopener">www.cnil.fr</a>).</p>

    <h2>Sécurité</h2>
    <p>Connexion chiffrée (HTTPS), mots de passe hachés, limitation des tentatives de connexion, journal de sécurité et sauvegardes quotidiennes.</p>

    <p class="muted small">Dernière mise à jour : 28 novembre 2026.</p>
</article>
@endsection
