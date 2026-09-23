function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function currentChatEventId() {
    const root = document.getElementById('event-chat');
    if (!root) {
        return null;
    }
    const match = String(root.dataset.channel || '').match(/events\.(\d+)\.chat/);
    return match ? Number(match[1]) : null;
}

function bumpChatBadge() {
    document.querySelectorAll('[data-chat-unread-dot]').forEach((el) => {
        el.hidden = false;
        el.classList.remove('hidden');
    });
}

function showChatToast(payload, host) {
    const eventId = Number(payload.event_id);
    if (currentChatEventId() === eventId) {
        return;
    }

    const user = payload.user || {};
    const name = user.name || '';
    const initials = user.initials || (name ? name.trim().charAt(0).toUpperCase() : '?');
    const photo = user.profile_image_path || '';
    const body = payload.body || '';
    const eventTitle = payload.event_title || '';
    const chatUrl = payload.chat_url || `/events/${eventId}/chat`;
    const labelNew = host.dataset.labelNew || 'New message';
    const labelJustNow = host.dataset.labelJustNow || 'Just now';

    const toast = document.createElement('div');
    toast.className = 'chat-toast';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="chat-toast__header">
            <span class="chat-toast__eyebrow">${escapeHtml(labelNew)}</span>
            <button type="button" class="chat-toast__close" aria-label="Close">
                <svg class="h-3 w-3" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
            </button>
        </div>
        <a href="${escapeHtml(chatUrl)}" class="chat-toast__body">
            <div class="chat-toast__avatar-wrap">
                ${photo
                    ? `<img src="${escapeHtml(photo)}" alt="" class="chat-toast__avatar-img">`
                    : `<div class="chat-toast__avatar">${escapeHtml(initials)}</div>`}
                <span class="chat-toast__badge" aria-hidden="true">
                    <svg class="h-3 w-3 text-white" viewBox="0 0 20 18" fill="currentColor" aria-hidden="true">
                        <path d="M18 4H16V9C16 10.0609 15.5786 11.0783 14.8284 11.8284C14.0783 12.5786 13.0609 13 12 13H9L6.846 14.615C7.17993 14.8628 7.58418 14.9977 8 15H11.667L15.4 17.8C15.5731 17.9298 15.7836 18 16 18C16.2652 18 16.5196 17.8946 16.7071 17.7071C16.8946 17.5196 17 17.2652 17 17V15H18C18.5304 15 19.0391 14.7893 19.4142 14.4142C19.7893 14.0391 20 13.5304 20 13V6C20 5.46957 19.7893 4.96086 19.4142 4.58579C19.0391 4.21071 18.5304 4 18 4Z"/>
                        <path d="M12 0H2C1.46957 0 0.960859 0.210714 0.585786 0.585786C0.210714 0.960859 0 1.46957 0 2V9C0 9.53043 0.210714 10.0391 0.585786 10.4142C0.960859 10.7893 1.46957 11 2 11H3V13C3 13.1857 3.05171 13.3678 3.14935 13.5257C3.24698 13.6837 3.38668 13.8114 3.55279 13.8944C3.71889 13.9775 3.90484 14.0126 4.08981 13.996C4.27477 13.9793 4.45143 13.9114 4.6 13.8L8.333 11H12C12.5304 11 13.0391 10.7893 13.4142 10.4142C13.7893 10.0391 14 9.53043 14 9V2C14 1.46957 13.7893 0.960859 13.4142 0.585786C13.0391 0.210714 12.5304 0 12 0Z"/>
                    </svg>
                </span>
            </div>
            <div class="chat-toast__copy">
                <div class="chat-toast__name">${escapeHtml(name)}</div>
                <div class="chat-toast__text">${escapeHtml(body)}</div>
                <div class="chat-toast__meta">
                    <span class="chat-toast__event">${escapeHtml(eventTitle)}</span>
                    <span class="chat-toast__time">${escapeHtml(labelJustNow)}</span>
                </div>
            </div>
        </a>
    `;

    const close = () => {
        toast.classList.add('chat-toast--out');
        window.setTimeout(() => toast.remove(), 220);
    };

    toast.querySelector('.chat-toast__close')?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        close();
    });

    host.appendChild(toast);
    bumpChatBadge();

    window.setTimeout(close, 7000);
}

export function initChatToasts() {
    const host = document.getElementById('chat-toast-host');
    if (!host || !window.Echo) {
        return;
    }

    const userId = host.dataset.userId;
    if (!userId) {
        return;
    }

    window.Echo.private(`App.Models.User.${userId}`).listen('.chat.message', (payload) => {
        showChatToast(payload || {}, host);
    });
}
