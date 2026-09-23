@props([
    'name' => null,
    'label' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
    'required' => false,
    'multiple' => false,
    'mode' => 'upload', // upload | view
    'existingUrl' => null,
    'existingName' => null,
    'existingTag' => null,
    'files' => [], // [{name, url?, tag?, type?}] for view / multi preview
    'hint' => null,
    'inputId' => null,
    'maxVisible' => 5,
])

@php
    $inputId = $inputId ?? ($name ? 'attachment-'.$name : 'attachment-'.uniqid());
    $toggleId = $inputId.'-toggle';
    $hint = $hint ?? __('ui.attachment_hint');
    $fileList = collect($files)->take($maxVisible)->values();
    if ($fileList->isEmpty() && filled($existingUrl)) {
        $fileList = collect([[
            'name' => $existingName ?? basename(parse_url($existingUrl, PHP_URL_PATH) ?: 'file'),
            'url' => $existingUrl,
            'tag' => $existingTag,
        ]]);
    }
    $hasFiles = $fileList->isNotEmpty();
    $count = $hasFiles ? $fileList->count() : 0;
    $palette = ['file-1', 'file-2', 'file-3', 'file-4', 'file-5'];
    $firstPreview = $hasFiles ? ($fileList->first()['url'] ?? null) : null;
@endphp

<div
    {{ $attributes->class(['attachment-folder', 'has-files' => $hasFiles]) }}
    data-attachment-folder
    data-mode="{{ $mode }}"
    data-accept="{{ $accept }}"
    data-multiple="{{ $multiple ? '1' : '0' }}"
    data-add-label="{{ __('ui.attachment_add') }}"
    data-drop-label="{{ __('ui.attachment_drop') }}"
    data-selected-label="{{ __('ui.attachment_selected_count') }}"
>
    @if ($label)
        <p class="attachment-folder__field-label">{{ $label }}</p>
    @endif

    <div class="attachment-folder__stage">
        <label
            class="folder-card"
            @if ($mode === 'upload' && $name)
                for="{{ $inputId }}"
            @else
                for="{{ $toggleId }}"
            @endif
            tabindex="0"
        >
            <input
                type="checkbox"
                id="{{ $toggleId }}"
                class="folder-toggle"
                @checked($hasFiles)
                tabindex="-1"
                aria-hidden="true"
            />

            <div class="hint-wrapper" aria-hidden="true">
                <span class="hint-text">{{ $hint }}</span>
                <svg class="hint-arrow" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M 35 5 C 35 5, 15 5, 10 25 M 10 25 L 3 18 M 10 25 L 18 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </div>

            <div class="folder-container">
                <svg class="folder-back" viewBox="0 0 50 40" fill="none" aria-hidden="true">
                    <path d="M0 4C0 1.79086 1.79086 0 4 0H16.524C17.721 0 18.8415 0.54051 19.574 1.4673L22.426 5.0654C23.1585 5.99219 24.279 6.5327 25.476 6.5327H46C48.2091 6.5327 50 8.32356 50 10.5327V36C50 38.2091 48.2091 40 46 40H4C1.79086 40 0 38.2091 0 36V4Z" fill="currentColor"></path>
                </svg>

                @if ($mode === 'upload' || $hasFiles)
                    <div class="folder-search" onclick="event.stopPropagation()">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" placeholder="{{ __('ui.attachment_search') }}" class="search-input" data-attachment-filter autocomplete="off" />
                    </div>
                @endif

                <div class="attachment-folder__files" data-attachment-files>
                    @forelse ($fileList as $index => $file)
                        @php
                            $fileName = is_array($file) ? ($file['name'] ?? 'file') : (string) $file;
                            $fileUrl = is_array($file) ? ($file['url'] ?? null) : null;
                            $fileTag = is_array($file) ? ($file['tag'] ?? null) : null;
                            $slot = $palette[$index % 5];
                        @endphp
                        <div class="file {{ $slot }} {{ $fileUrl ? 'file--image' : '' }}" data-attachment-file data-name="{{ $fileName }}" @if ($fileUrl) data-url="{{ $fileUrl }}" @endif>
                            @if ($fileUrl)
                                <img class="file-thumb" src="{{ $fileUrl }}" alt="">
                            @endif
                            <div class="shine"></div>
                            <svg class="file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <div class="file-text">{{ \Illuminate\Support\Str::limit($fileName, 18) }}</div>
                            @if ($fileTag)
                                <div class="file-tag">{{ $fileTag }}</div>
                            @endif
                            @if ($fileUrl && $mode === 'view')
                                <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="file-link" onclick="event.stopPropagation()" aria-label="{{ $fileName }}"></a>
                            @endif
                        </div>
                    @empty
                        @if ($mode === 'upload')
                            <div class="file file-1 attachment-folder__placeholder" data-attachment-placeholder>
                                <div class="shine"></div>
                                <svg class="file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                <div class="file-text">{{ __('ui.attachment_add') }}</div>
                                <div class="file-tag">{{ __('ui.attachment_drop') }}</div>
                            </div>
                        @endif
                    @endforelse
                </div>

                <div class="folder-front-wrapper">
                    <svg class="folder-front" viewBox="0 0 50 34" fill="none" aria-hidden="true">
                        <path d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V30C50 32.2091 48.2091 34 46 34H4C1.79086 34 0 32.2091 0 30V4Z" fill="currentColor"></path>
                    </svg>
                    <div class="folder-label"></div>
                    <div class="counter" title="{{ __('ui.attachment_files') }}">
                        <div class="status-dot"></div>
                        <span class="counter-label">{{ __('ui.attachment_files') }}</span>
                        <span class="counter-number" data-attachment-count>{{ str_pad((string) $count, 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>
            </div>
        </label>

        <img
            class="attachment-folder__preview"
            data-attachment-preview
            alt=""
            @if ($firstPreview) src="{{ $firstPreview }}" @else hidden @endif
        >
    </div>

    <p class="attachment-folder__status" data-attachment-status @if (! $hasFiles) hidden @endif>
        @if ($hasFiles)
            {{ $fileList->first()['name'] ?? '' }}
            @if (! empty($fileList->first()['tag']))
                ({{ $fileList->first()['tag'] }})
            @endif
        @endif
    </p>

    @if ($mode === 'upload' && $name)
        <input
            type="file"
            id="{{ $inputId }}"
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            accept="{{ $accept }}"
            @required($required)
            @if ($multiple) multiple @endif
            class="attachment-folder__input"
            data-attachment-input
            aria-label="{{ $label ?? __('ui.attachment_add') }}"
        >
        <button type="button" class="attachment-folder__browse" data-attachment-browse>
            {{ __('ui.attachment_browse') }}
        </button>
    @endif
</div>
