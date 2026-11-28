@if ($resources->isNotEmpty())
<ul class="resources">
    @foreach ($resources as $resource)
    <li>
        <div class="grow">
            <span class="tag type-{{ $resource->type }}">{{ $resource->typeLabel() }}</span>
            <a href="{{ $resource->url }}" target="_blank" rel="noopener noreferrer">{{ $resource->title }}</a>
            <span class="muted small">{{ $resource->host() }}</span>
            <details class="edit">
                <summary>Modifier</summary>
                <form class="form inline-form" method="post" action="{{ route('resources.update', $resource) }}">
                    @csrf @method('put')
                    <input type="hidden" name="teaching_session_id" value="{{ $resource->teaching_session_id }}">
                    <select name="type" aria-label="Type">
                        @foreach (\App\Models\Resource::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected($resource->type === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="title" value="{{ $resource->title }}" required aria-label="Titre">
                    <input name="url" type="url" value="{{ $resource->url }}" required aria-label="Lien https">
                    <button class="btn small primary">Enregistrer</button>
                </form>
            </details>
        </div>
        <form method="post" action="{{ route('resources.destroy', $resource) }}" class="inline">
            @csrf @method('delete')
            <button class="btn small danger" aria-label="Supprimer le lien" title="Supprimer">×</button>
        </form>
    </li>
    @endforeach
</ul>
@endif
