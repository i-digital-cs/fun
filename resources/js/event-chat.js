function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function autosize(textarea) {
    textarea.style.height = 'auto';
    const next = Math.min(Math.max(textarea.scrollHeight, 52), 200);
    textarea.style.height = `${next}px`;
}

function formatChatTime(iso) {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleTimeString([], {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
}

function initialsFromName(name) {
    const parts = String(name || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    if (parts.length === 0) {
        return '?';
    }

    return parts.map((part) => part.charAt(0).toUpperCase()).join('');
}

function avatarHtml(user, profileBase) {
    const name = user?.name ?? '';
    const photo = user?.profile_image_path ?? '';
    const publicId = user?.public_id ?? '';
    const initials = user?.initials || initialsFromName(name);
    const inner = photo
        ? `<img src="${escapeHtml(photo)}" alt="" loading="lazy" />`
        : `<span>${escapeHtml(initials)}</span>`;

    if (publicId && profileBase) {
        const href = `${profileBase.replace(/\/$/, '')}/${encodeURIComponent(publicId)}`;
        return `<a href="${escapeHtml(href)}" class="chat-row__avatar" title="${escapeHtml(name)}">${inner}</a>`;
    }

    return `<span class="chat-row__avatar" aria-hidden="true">${inner}</span>`;
}

/** Jump to newest message without fighting layout/images. */
function scrollMessagesToEnd(container, { smooth = false } = {}) {
    if (!container) {
        return;
    }

    const apply = () => {
        const last = container.querySelector('.chat-row:last-child');
        if (last && typeof last.scrollIntoView === 'function') {
            last.scrollIntoView({
                block: 'end',
                inline: 'nearest',
                behavior: smooth ? 'smooth' : 'auto',
            });
        }
        container.scrollTop = container.scrollHeight;
    };

    apply();
    requestAnimationFrame(() => {
        apply();
        requestAnimationFrame(apply);
    });
}

function isNearBottom(container, threshold = 96) {
    if (!container) {
        return true;
    }
    const gap = container.scrollHeight - container.scrollTop - container.clientHeight;
    return gap <= threshold;
}

function appendMessage(container, message, emptyEl, currentUserId, options = {}) {
    if (container.querySelector(`[data-message-id="${message.id}"]`)) {
        return;
    }

    emptyEl?.remove();

    const stickToBottom = options.forceScroll === true || isNearBottom(container);
    const time = formatChatTime(message.created_at);
    const mine = Number(message.user?.id) === Number(currentUserId);
    const isSystem = Boolean(message.is_system);
    const ownerId = options.ownerId != null ? Number(options.ownerId) : null;
    const isHost = ownerId != null && Number(message.user?.id) === ownerId;
    const hostLabel = options.hostLabel || 'Host';
    const reportUrl = options.reportUrl || '';
    const reportLabel = options.reportLabel || 'Report';
    const profileBase = options.profileBase || '/u';

    const el = document.createElement('div');
    el.dataset.messageId = String(message.id);
    el.dataset.mine = mine ? '1' : '0';
    if (isHost) {
        el.dataset.host = '1';
    }

    if (isSystem) {
        el.className = 'chat-row chat-row--system';
        el.dataset.system = '1';
        el.innerHTML = `<p class="chat-row__system-text">${escapeHtml(message.body ?? '')}</p>`;
        container.appendChild(el);
        if (stickToBottom || mine) {
            scrollMessagesToEnd(container);
        }
        return;
    }

    el.className = `chat-row ${mine ? 'chat-row--mine' : 'chat-row--theirs'}`;

    let author = '';
    if (!mine && message.user?.name) {
        const nameLink = message.user?.public_id
            ? `<a href="${escapeHtml(`${profileBase.replace(/\/$/, '')}/${encodeURIComponent(message.user.public_id)}`)}" class="chat-message__author">${escapeHtml(message.user.name)}</a>`
            : `<p class="chat-message__author">${escapeHtml(message.user.name)}</p>`;
        const hostBadge = isHost
            ? `<span class="chat-message__host-badge">${escapeHtml(hostLabel)}</span>`
            : '';
        author = `<div class="chat-message__author-row">${nameLink}${hostBadge}</div>`;
    } else if (mine && isHost) {
        author = `<div class="chat-message__author-row chat-message__author-row--mine"><span class="chat-message__host-badge chat-message__host-badge--mine">${escapeHtml(hostLabel)}</span></div>`;
    }

    let report = '';
    if (!mine && reportUrl) {
        const url = new URL(reportUrl, window.location.origin);
        url.searchParams.set('reportable_type', 'message');
        url.searchParams.set('reportable_id', String(message.id));
        report = `<a href="${escapeHtml(url.pathname + url.search)}" class="chat-message__report">${escapeHtml(reportLabel)}</a>`;
    }

    const bubble = `
        <div class="chat-message ${mine ? 'chat-message--mine' : 'chat-message--theirs'}${isHost ? ' chat-message--host' : ''}">
            ${author}
            <p class="chat-message__body">${escapeHtml(message.body ?? '')}</p>
            <div class="chat-message__footer">
                ${report}
                <time class="chat-message__time">${escapeHtml(time)}</time>
            </div>
        </div>
    `;

    const avatar = avatarHtml(message.user, profileBase);
    el.innerHTML = mine ? `${bubble}${avatar}` : `${avatar}${bubble}`;

    container.appendChild(el);
    if (stickToBottom || mine || options.forceScroll) {
        scrollMessagesToEnd(container);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('event-chat');
    if (!root) {
        return;
    }

    const messagesEl = document.getElementById('chat-messages');
    const form = document.getElementById('chat-form');
    const bodyInput = document.getElementById('chat-body');
    const statusEl = document.getElementById('chat-status');
    const emptyEl = document.getElementById('chat-empty');
    const sendBtn = form?.querySelector('[data-chat-send]');
    const attachBtn = form?.querySelector('[data-chat-attach]');
    const channelName = root.dataset.channel;
    const postUrl = root.dataset.postUrl;
    const csrf = root.dataset.csrf;
    const currentUserId = Number(root.dataset.userId);
    const appendOptions = {
        reportUrl: root.dataset.reportUrl || '',
        reportLabel: root.dataset.reportLabel || 'Report',
        profileBase: '/u',
        ownerId: root.dataset.ownerId || '',
        hostLabel: root.dataset.hostLabel || 'Host',
    };

    // Always land on the newest message when opening the thread.
    scrollMessagesToEnd(messagesEl);
    window.addEventListener('load', () => scrollMessagesToEnd(messagesEl), { once: true });
    setTimeout(() => scrollMessagesToEnd(messagesEl), 120);

    if (bodyInput) {
        autosize(bodyInput);
        bodyInput.addEventListener('input', () => {
            autosize(bodyInput);
            // Keep newest visible while composing if already at bottom.
            if (isNearBottom(messagesEl, 140)) {
                scrollMessagesToEnd(messagesEl);
            }
        });
        bodyInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                form?.requestSubmit();
            }
        });
    }

    attachBtn?.addEventListener('click', () => {
        if (statusEl) {
            statusEl.textContent = attachBtn.title || 'Attachments coming soon.';
            statusEl.classList.remove('hidden');
        }
    });

    if (window.Echo && channelName) {
        window.Echo.private(channelName).listen('.message.posted', (payload) => {
            if (Number(payload.user?.id) === currentUserId) {
                return;
            }
            appendMessage(messagesEl, payload, emptyEl, currentUserId, appendOptions);
        });
    } else if (statusEl) {
        statusEl.textContent = statusEl.dataset.fallback || 'Live updates unavailable.';
        statusEl.classList.remove('hidden');
    }

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = bodyInput.value.trim();
        if (!body) {
            return;
        }

        if (sendBtn) {
            sendBtn.disabled = true;
        }

        try {
            const response = await window.axios.post(
                postUrl,
                { body },
                {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                }
            );

            appendMessage(messagesEl, response.data.data, emptyEl, currentUserId, {
                ...appendOptions,
                forceScroll: true,
            });
            bodyInput.value = '';
            autosize(bodyInput);
            if (statusEl && !statusEl.dataset.fallbackActive) {
                statusEl.classList.add('hidden');
            }
        } catch (error) {
            const message =
                error?.response?.data?.message
                || error?.response?.data?.errors?.body?.[0]
                || 'Unable to send message.';
            if (statusEl) {
                statusEl.textContent = message;
                statusEl.classList.remove('hidden');
            }
        } finally {
            if (sendBtn) {
                sendBtn.disabled = false;
            }
            bodyInput.focus();
        }
    });
});
