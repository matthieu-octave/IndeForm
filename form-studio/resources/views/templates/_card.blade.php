<details class="template">
    <summary>
        <strong>{{ $template->name }}</strong>
        <span class="tag muted">{{ \App\Support\Duration::format($template->totalMinutes()) }}</span>
        <span class="muted small">{{ count($template->steps) }} étape(s)</span>
        @if ($template->description)<br><span class="muted small">{{ $template->description }}</span>@endif
    </summary>
    <ol class="steps">
        @foreach ($template->steps as $step)
            <li><div><strong>{{ $step['title'] }}</strong> <span class="tag muted">{{ \App\Support\Duration::format((int) $step['minutes']) }}</span>
                @if (! empty($step['instructions']))<p class="pre">{{ $step['instructions'] }}</p>@endif</div></li>
        @endforeach
    </ol>
    @unless ($template->isProvided())
    <div class="actions">
        <form class="inline" method="post" action="{{ route('templates.update', $template) }}">
            @csrf @method('put')
            <input name="name" value="{{ $template->name }}" required aria-label="Nom du modèle">
            <input name="description" value="{{ $template->description }}" placeholder="Description" aria-label="Description">
            <button class="btn small">Renommer</button>
        </form>
        <form class="inline" method="post" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Supprimer ce modèle ? Les séances déjà créées ne changent pas.')">
            @csrf @method('delete')
            <button class="btn small danger">Supprimer</button>
        </form>
    </div>
    @endunless
</details>
