@extends('layouts.app')
@section('title', 'Politique de confidentialité')
@section('content')
@php($p = config('studio.publisher'))
@php($h = config('studio.host'))
<article class="panel legal">
    <h1>Politique de confidentialité</h1>

    <h2>Responsable du traitement</h2>
    <p>{{ $p['name'] }} ({{ $p['trade_name'] }}), {{ $p['address'] }}. Contact : <a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a>.</p>

    <h2>Données traitées et finalités</h2>
    <ul>
        <li><strong>Compte</strong> : nom, adresse e-mail, mot de passe (stocké chiffré, illisible même pour l’éditeur). Pour vous identifier et vous envoyer les e-mails de service (réinitialisation du mot de passe).</li>
        <li><strong>Contenus</strong> : modules, séances, déroulés, notes, durées, liens vers vos supports. Pour fournir le service. Vos fichiers ne sont pas stockés : seuls les liens que vous saisissez le sont.</li>
        <li><strong>Données techniques</strong> : cookie de session et, si vous cochez « Rester connecté », cookie de connexion ; journaux techniques du serveur (adresse IP, date, page demandée) pour la sécurité.</li>
    </ul>
    <p>Base légale : l’exécution du contrat d’utilisation du service et, pour les journaux de sécurité, l’intérêt légitime de l’éditeur.</p>

    <h2>Cookies</h2>
    <p>Form’Studio n’utilise que des cookies strictement nécessaires à son fonctionnement (session, sécurité des formulaires, « rester connecté »).
        Aucun cookie publicitaire ni outil de mesure d’audience : aucun consentement n’est donc demandé.</p>

    <h2>Destinataires</h2>
    <p>Vos données ne sont ni vendues ni partagées. Seuls y accèdent l’éditeur, pour la maintenance, et l’hébergeur ({{ $h['name'] }}) en tant que sous-traitant technique.
        Chaque compte est isolé : un utilisateur ne voit jamais les contenus d’un autre.</p>

    <h2>Durée de conservation</h2>
    <p>Vos données sont conservées tant que votre compte existe. Si vous supprimez votre compte, elles sont effacées immédiatement de l’application,
        puis disparaissent des sauvegardes au bout de {{ config('studio.backup_days') }} jours.</p>

    <h2>Vos droits</h2>
    <p>Vous disposez des droits d’accès, de rectification, d’effacement, de limitation, d’opposition et de portabilité. Vous pouvez les exercer directement depuis la page « Mon compte »
        (modifier vos informations, exporter toutes vos données, supprimer votre compte) ou en écrivant à <a href="mailto:{{ $p['email'] }}">{{ $p['email'] }}</a>.
        Vous pouvez aussi adresser une réclamation à la CNIL (<a href="https://www.cnil.fr" rel="noopener">www.cnil.fr</a>).</p>

    <h2>Sécurité</h2>
    <p>Connexion chiffrée (HTTPS), mots de passe hachés, limitation des tentatives de connexion, isolement des données par compte et sauvegardes quotidiennes.</p>

    <p class="muted small">Dernière mise à jour : 28 novembre 2026.</p>
</article>
@endsection
