@props([
    'event',
    'favorited' => false,
    'idSuffix' => 'card',
])

@auth
    @if (auth()->user()?->canParticipate())
        @php($inputId = 'favorite-'.$event->id.'-'.$idSuffix)
        <form
            method="POST"
            action="{{ route('events.favorite.toggle', $event) }}"
            class="ui-bookmark"
            title="{{ $favorited ? __('events.unfavorite') : __('events.favorite') }}"
            onclick="event.stopPropagation()"
        >
            @csrf
            <input
                type="checkbox"
                id="{{ $inputId }}"
                @checked($favorited)
                onchange="this.form.submit()"
                aria-label="{{ $favorited ? __('events.unfavorite') : __('events.favorite') }}"
            >
            <label for="{{ $inputId }}" class="bookmark">
                <svg viewBox="0 0 32 32" aria-hidden="true">
                    <g>
                        <path d="M27 4v27a1 1 0 0 1-1.625.781L16 24.281l-9.375 7.5A1 1 0 0 1 5 31V4a4 4 0 0 1 4-4h14a4 4 0 0 1 4 4z"></path>
                    </g>
                </svg>
            </label>
        </form>
    @endif
@endauth
