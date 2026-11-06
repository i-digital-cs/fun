{{-- Event Quick View shell — centered listing modal; content via events.quick-view --}}
<dialog
    id="event-quick-view"
    class="event-qv-dialog"
    aria-labelledby="event-qv-title"
    aria-modal="true"
    data-event-quick-view
>
    <div class="event-qv-shell" data-event-qv-shell>
        <div class="event-qv-toolbar">
            <div class="event-qv-nav">
                <button type="button" class="event-qv-icon-btn" data-event-qv-prev aria-label="{{ __('events.previous_event') }}">
                    <svg class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
                </button>
                <button type="button" class="event-qv-icon-btn" data-event-qv-next aria-label="{{ __('events.next_event') }}">
                    <svg class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>
            <button type="button" class="event-qv-icon-btn event-qv-close" data-event-qv-close aria-label="{{ __('ui.close') }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div class="event-qv-body fancy-scroll" data-event-qv-body tabindex="-1">
            <div class="event-qv-loading" data-event-qv-loading hidden>
                <div class="event-qv-skeleton" aria-hidden="true">
                    <div class="event-qv-skeleton__hero"></div>
                    <div class="event-qv-skeleton__line event-qv-skeleton__line--lg"></div>
                    <div class="event-qv-skeleton__line"></div>
                    <div class="event-qv-skeleton__line event-qv-skeleton__line--sm"></div>
                    <div class="event-qv-skeleton__pills">
                        <span></span><span></span>
                    </div>
                </div>
                <p class="event-qv-loading__label">{{ __('events.quick_view_loading') }}</p>
            </div>
            <div data-event-qv-content></div>
        </div>
    </div>
</dialog>
