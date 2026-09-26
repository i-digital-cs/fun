function initChatInbox(root = document.getElementById('chat-inbox')) {
    if (!root || root.dataset.chatInboxReady === '1') {
        return;
    }

    const rows = [...root.querySelectorAll('[data-chat-row]')];
    if (rows.length === 0) {
        return;
    }

    const filterButtons = [...root.querySelectorAll('[data-chat-filter]')];
    const searchToggle = root.querySelector('[data-chat-search-toggle]');
    const searchPanel = root.querySelector('[data-chat-search-panel]');
    const searchInput = root.querySelector('[data-chat-search-input]');
    const emptyUnread = root.querySelector('[data-chat-empty-unread]');
    const emptySearch = root.querySelector('[data-chat-empty-search]');

    let filter = 'recent';
    let query = '';

    const apply = () => {
        let visible = 0;

        rows.forEach((row) => {
            const unread = row.dataset.unread === '1';
            const hay = row.dataset.search || '';
            const matchesFilter = filter !== 'unread' || unread;
            const matchesQuery = !query || hay.includes(query);
            const show = matchesFilter && matchesQuery;
            row.hidden = !show;
            if (show) {
                visible += 1;
            }
        });

        if (emptyUnread) {
            emptyUnread.classList.toggle('hidden', !(visible === 0 && filter === 'unread' && !query));
        }
        if (emptySearch) {
            emptySearch.classList.toggle('hidden', !(visible === 0 && query));
        }
    };

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            filter = btn.dataset.chatFilter || 'recent';
            filterButtons.forEach((other) => {
                const active = other === btn;
                other.setAttribute('aria-selected', active ? 'true' : 'false');
                other.classList.toggle('bg-white', active);
                other.classList.toggle('text-indigo-700', active);
                other.classList.toggle('shadow-sm', active);
                other.classList.toggle('text-slate-600', !active);
                other.classList.toggle('hover:text-slate-900', !active);
            });
            apply();
        });
    });

    searchToggle?.addEventListener('click', () => {
        const open = searchPanel?.classList.toggle('hidden') === false;
        searchToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!open && searchInput) {
            searchInput.value = '';
            query = '';
            apply();
        } else if (open) {
            searchInput?.focus();
        }
    });

    searchInput?.addEventListener('input', () => {
        query = searchInput.value.trim().toLowerCase();
        apply();
    });

    root.dataset.chatInboxReady = '1';
    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    initChatInbox();
});
