<form class="form inline-form resource-form" method="post" action="{{ route('resources.store', $module) }}">
    @csrf
    @if ($sessionId)<input type="hidden" name="teaching_session_id" value="{{ $sessionId }}">@endif
    <select name="type" aria-label="Type">
        @foreach (\App\Models\Resource::TYPES as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
    <input name="title" placeholder="Titre (ex. Diaporama, TP 1)" required aria-label="Titre">
    <input name="url" type="url" placeholder="Lien https:// (Google Drive…)" required aria-label="Lien https">
    <button class="btn small">Ajouter le lien</button>
</form>
