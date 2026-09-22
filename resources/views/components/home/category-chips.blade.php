@props([
    'categories',
    'publishedTotal' => 0,
    'activeCategoryId' => null,
])

<section class="mb-8" aria-label="{{ __('home.categories') }}">
    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0">
        <a href="{{ route('events.index') }}"
           @class([
               'inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-2 text-sm font-medium shadow-sm transition-colors',
               'bg-primary text-white' => $activeCategoryId === null,
               'border border-border bg-surface text-text hover:bg-background' => $activeCategoryId !== null,
           ])>
            <span>{{ __('home.all_activities') }}</span>
            <span @class([
                'rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none',
                'bg-white/20 text-white' => $activeCategoryId === null,
                'bg-primary/10 text-primary' => $activeCategoryId !== null,
            ])>{{ $publishedTotal }}</span>
        </a>

        @foreach ($categories as $category)
            <a href="{{ route('events.index', ['category_id' => $category->id]) }}"
               @class([
                   'inline-flex shrink-0 items-center gap-1.5 rounded-full px-4 py-2 text-sm font-medium shadow-sm transition-colors',
                   'bg-primary text-white' => (int) $activeCategoryId === (int) $category->id,
                   'border border-border bg-surface text-text hover:bg-background' => (int) $activeCategoryId !== (int) $category->id,
               ])>
                <span>{{ $category->localizedName() }}</span>
                @if ($category->published_events_count > 0)
                    <span @class([
                        'rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none',
                        'bg-white/20 text-white' => (int) $activeCategoryId === (int) $category->id,
                        'bg-background text-muted' => (int) $activeCategoryId !== (int) $category->id,
                    ])>{{ $category->published_events_count }}</span>
                @endif
            </a>
        @endforeach
    </div>
</section>
