/**
 * Chat V2 inbox filters + search (conversation list).
 */
function initChatV2(root = document.querySelector('[data-chat-v2]')) {
    if (!root || root.dataset.chatV2Ready === '1') return;
    root.dataset.chatV2Ready = '1';

    const rows = [...root.querySelectorAll('[data-chat-v2-row]')];
    const filterButtons = [...root.querySelectorAll('[data-chat-v2-filter]')];
    const searchInput = root.querySelector('[data-chat-v2-search]');
    const emptyFilter = root.querySelector('[data-chat-v2-empty-filter]');

    let filter = 'all';
    let query = '';

    const apply = () => {
        let visible = 0;
        rows.forEach((row) => {
            const type = row.dataset.type || '';
            const unread = row.dataset.unread === '1';
            const hay = row.dataset.search || '';
            const matchesType =
                filter === 'all' ||
                (filter === 'unread' && unread) ||
                (filter === 'direct' && type === 'direct') ||
                (filter === 'events' && type === 'event');
            const matchesQuery = !query || hay.includes(query);
            const show = matchesType && matchesQuery;
            row.hidden = !show;
            if (show) visible += 1;
        });
        if (emptyFilter) {
            emptyFilter.classList.toggle('hidden', !(visible === 0 && rows.length > 0));
        }
    };

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            filter = btn.dataset.chatV2Filter || 'all';
            filterButtons.forEach((other) => {
                const active = other === btn;
                other.setAttribute('aria-selected', active ? 'true' : 'false');
                other.classList.toggle('bg-[#7138F5]', active);
                other.classList.toggle('text-white', active);
                other.classList.toggle('bg-slate-100', !active);
                other.classList.toggle('text-slate-600', !active);
            });
            apply();
        });
    });

    searchInput?.addEventListener('input', () => {
        query = searchInput.value.trim().toLowerCase();
        apply();
    });

    apply();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initChatV2());
} else {
    initChatV2();
}

export { initChatV2 };
