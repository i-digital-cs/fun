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

function reactionsHtml(message, options = {}) {
    if (message.is_system) {
        return '';
    }

    const likes = Number(message.likes_count || 0);
    const dislikes = Number(message.dislikes_count || 0);
    const mine = message.my_reaction || '';
    const likeLabel = options.likeLabel || 'Like';
    const dislikeLabel = options.dislikeLabel || 'Dislike';

    return `
        <div class="chat-message__reactions" data-message-id="${escapeHtml(String(message.id))}">
            <button type="button" class="chat-react-btn${mine === 'like' ? ' is-active' : ''}" data-react="like" aria-label="${escapeHtml(likeLabel)}" aria-pressed="${mine === 'like' ? 'true' : 'false'}">
                <span aria-hidden="true">👍</span>
            </button>
            <button type="button" class="chat-react-count" data-react-list="like" data-react-count="like" aria-label="${escapeHtml(likeLabel)}">${likes}</button>
            <button type="button" class="chat-react-btn${mine === 'dislike' ? ' is-active' : ''}" data-react="dislike" aria-label="${escapeHtml(dislikeLabel)}" aria-pressed="${mine === 'dislike' ? 'true' : 'false'}">
                <span aria-hidden="true">👎</span>
            </button>
            <button type="button" class="chat-react-count" data-react-list="dislike" data-react-count="dislike" aria-label="${escapeHtml(dislikeLabel)}">${dislikes}</button>
        </div>
    `;
}

function applyReactionToRow(row, payload, { isActor = false } = {}) {
    if (!row) {
        return;
    }

    const likes = Number(payload.likes_count || 0);
    const dislikes = Number(payload.dislikes_count || 0);
    const likeBtn = row.querySelector('[data-react="like"]');
    const dislikeBtn = row.querySelector('[data-react="dislike"]');
    const likeCount = row.querySelector('[data-react-count="like"]');
    const dislikeCount = row.querySelector('[data-react-count="dislike"]');

    if (likeCount) {
        likeCount.textContent = String(likes);
    }
    if (dislikeCount) {
        dislikeCount.textContent = String(dislikes);
    }

    if (isActor) {
        const mine = payload.my_reaction || null;
        likeBtn?.classList.toggle('is-active', mine === 'like');
        dislikeBtn?.classList.toggle('is-active', mine === 'dislike');
        likeBtn?.setAttribute('aria-pressed', mine === 'like' ? 'true' : 'false');
        dislikeBtn?.setAttribute('aria-pressed', mine === 'dislike' ? 'true' : 'false');

        const bubble = row.querySelector('.chat-message');
        bubble?.classList.toggle('chat-message--reacted-like', mine === 'like');
        bubble?.classList.toggle('chat-message--reacted-dislike', mine === 'dislike');
    }
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

    const myReaction = message.my_reaction || '';
    const reactedClass = myReaction === 'like'
        ? ' chat-message--reacted-like'
        : (myReaction === 'dislike' ? ' chat-message--reacted-dislike' : '');

    const bubble = `
        <div class="chat-message ${mine ? 'chat-message--mine' : 'chat-message--theirs'}${isHost ? ' chat-message--host' : ''}${reactedClass}">
            ${author}
            <p class="chat-message__body">${escapeHtml(message.body ?? '')}</p>
            <div class="chat-message__footer">
                ${reactionsHtml(message, options)}
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
        likeLabel: root.dataset.likeLabel || 'Like',
        dislikeLabel: root.dataset.dislikeLabel || 'Dislike',
    };
    const reactUrlTemplate = root.dataset.reactUrlTemplate || '';
    const reactionsUrlTemplate = root.dataset.reactionsUrlTemplate || '';
    const reactionsTitle = root.dataset.reactionsTitle || 'Reactions';
    const likedByLabel = root.dataset.likedByLabel || 'Liked by';
    const dislikedByLabel = root.dataset.dislikedByLabel || 'Disliked by';
    const noLikesLabel = root.dataset.noLikesLabel || 'No likes yet.';
    const noDislikesLabel = root.dataset.noDislikesLabel || 'No dislikes yet.';

    function closeReactionsModal() {
        document.getElementById('chat-reactions-modal')?.remove();
    }

    function personRowHtml(entry) {
        const user = entry.user || {};
        const name = user.name || '';
        const initials = user.initials || (name ? name.trim().charAt(0).toUpperCase() : '?');
        const photo = user.profile_image_path || '';
        const href = user.profile_url || (user.public_id ? `/u/${encodeURIComponent(user.public_id)}` : '');
        const avatar = photo
            ? `<img src="${escapeHtml(photo)}" alt="" class="chat-reactions-modal__avatar-img">`
            : `<span class="chat-reactions-modal__avatar">${escapeHtml(initials)}</span>`;
        const inner = `
            <div class="chat-reactions-modal__person">
                ${avatar}
                <span class="chat-reactions-modal__name">${escapeHtml(name)}</span>
            </div>
        `;
        return href
            ? `<a href="${escapeHtml(href)}" class="chat-reactions-modal__link">${inner}</a>`
            : inner;
    }

    function openReactionsModal(lists, focusType) {
        closeReactionsModal();
        const likes = Array.isArray(lists.likes) ? lists.likes : [];
        const dislikes = Array.isArray(lists.dislikes) ? lists.dislikes : [];
        const likesHtml = likes.length
            ? likes.map(personRowHtml).join('')
            : `<p class="chat-reactions-modal__empty">${escapeHtml(noLikesLabel)}</p>`;
        const dislikesHtml = dislikes.length
            ? dislikes.map(personRowHtml).join('')
            : `<p class="chat-reactions-modal__empty">${escapeHtml(noDislikesLabel)}</p>`;

        const modal = document.createElement('div');
        modal.id = 'chat-reactions-modal';
        modal.className = 'chat-reactions-modal';
        modal.innerHTML = `
            <div class="chat-reactions-modal__backdrop" data-close></div>
            <div class="chat-reactions-modal__panel" role="dialog" aria-modal="true" aria-label="${escapeHtml(reactionsTitle)}">
                <div class="chat-reactions-modal__header">
                    <h3>${escapeHtml(reactionsTitle)}</h3>
                    <button type="button" class="chat-reactions-modal__close" data-close aria-label="Close">×</button>
                </div>
                <div class="chat-reactions-modal__tabs">
                    <button type="button" class="chat-reactions-modal__tab${focusType !== 'dislike' ? ' is-active' : ''}" data-tab="like">👍 ${escapeHtml(likedByLabel)} (${likes.length})</button>
                    <button type="button" class="chat-reactions-modal__tab${focusType === 'dislike' ? ' is-active' : ''}" data-tab="dislike">👎 ${escapeHtml(dislikedByLabel)} (${dislikes.length})</button>
                </div>
                <div class="chat-reactions-modal__body" data-pane="like" ${focusType === 'dislike' ? 'hidden' : ''}>${likesHtml}</div>
                <div class="chat-reactions-modal__body" data-pane="dislike" ${focusType === 'dislike' ? '' : 'hidden'}>${dislikesHtml}</div>
            </div>
        `;
        document.body.appendChild(modal);
        modal.addEventListener('click', (event) => {
            if (event.target.closest('[data-close]')) {
                closeReactionsModal();
                return;
            }
            const tab = event.target.closest('[data-tab]');
            if (!tab) {
                return;
            }
            const key = tab.dataset.tab;
            modal.querySelectorAll('[data-tab]').forEach((el) => {
                el.classList.toggle('is-active', el.dataset.tab === key);
            });
            modal.querySelectorAll('[data-pane]').forEach((el) => {
                el.hidden = el.dataset.pane !== key;
            });
        });
    }

    async function submitReaction(messageId, type) {
        if (!reactUrlTemplate || !messageId) {
            return;
        }
        const url = reactUrlTemplate.replace('__MID__', encodeURIComponent(String(messageId)));
        const response = await window.axios.post(
            url,
            { type },
            {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            }
        );
        const payload = response.data?.data || response.data || {};
        const row = messagesEl.querySelector(`.chat-row[data-message-id="${messageId}"]`);
        applyReactionToRow(row, payload, { isActor: true });
    }

    async function showWhoReacted(messageId, focusType) {
        if (!reactionsUrlTemplate || !messageId) {
            return;
        }
        const url = reactionsUrlTemplate.replace('__MID__', encodeURIComponent(String(messageId)));
        const response = await window.axios.get(url, {
            headers: { Accept: 'application/json' },
        });
        const lists = response.data?.data || response.data || {};
        openReactionsModal(lists, focusType);
    }

    messagesEl?.addEventListener('click', (event) => {
        const listBtn = event.target.closest('.chat-react-count');
        if (listBtn && messagesEl.contains(listBtn)) {
            event.preventDefault();
            const wrap = listBtn.closest('.chat-message__reactions');
            const messageId = wrap?.dataset.messageId;
            const type = listBtn.dataset.reactList || 'like';
            if (!messageId) {
                return;
            }
            listBtn.disabled = true;
            showWhoReacted(messageId, type)
                .catch(() => {})
                .finally(() => {
                    listBtn.disabled = false;
                });
            return;
        }

        const btn = event.target.closest('.chat-react-btn');
        if (!btn || !messagesEl.contains(btn)) {
            return;
        }
        event.preventDefault();
        const wrap = btn.closest('.chat-message__reactions');
        const messageId = wrap?.dataset.messageId;
        const type = btn.dataset.react;
        if (!messageId || !type) {
            return;
        }
        const nextType = btn.classList.contains('is-active') ? null : type;
        btn.disabled = true;
        submitReaction(messageId, nextType)
            .catch(() => {})
            .finally(() => {
                btn.disabled = false;
            });
    });

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
        const channel = window.Echo.private(channelName);
        channel.listen('.message.posted', (payload) => {
            if (Number(payload.user?.id) === currentUserId) {
                return;
            }
            appendMessage(messagesEl, payload, emptyEl, currentUserId, appendOptions);
        });
        channel.listen('.message.reacted', (payload) => {
            const row = messagesEl.querySelector(`.chat-row[data-message-id="${payload.id}"]`);
            applyReactionToRow(row, payload, {
                isActor: Number(payload.actor_id) === currentUserId,
            });
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
