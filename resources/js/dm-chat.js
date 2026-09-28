/**
 * Direct message thread — waits for Echo (loaded async by app.js) then
 * subscribes to private-dms.{id} `.message.posted`.
 */
function whenEchoReady(callback) {
    if (window.Echo) {
        callback(window.Echo);
        return;
    }

    const onReady = () => {
        if (window.Echo) {
            callback(window.Echo);
        }
    };
    window.addEventListener('funnity:echo-ready', onReady, { once: true });

    let tries = 0;
    const timer = window.setInterval(() => {
        tries += 1;
        if (window.Echo) {
            window.clearInterval(timer);
            window.removeEventListener('funnity:echo-ready', onReady);
            callback(window.Echo);
            return;
        }
        if (tries >= 50) {
            window.clearInterval(timer);
            window.removeEventListener('funnity:echo-ready', onReady);
        }
    }, 100);
}

function appendDmRow(log, payload, currentUserId) {
    if (!payload?.id || log.querySelector(`[data-message-id="${payload.id}"]`)) {
        return;
    }

    const empty = document.getElementById('dm-empty');
    empty?.remove();

    const mine = Number(payload.user?.id) === currentUserId;
    const row = document.createElement('div');
    row.className = `chat-row ${mine ? 'chat-row--mine' : 'chat-row--theirs'}`;
    row.dataset.messageId = String(payload.id);

    if (!mine) {
        const avatar = document.createElement('span');
        avatar.className = 'chat-row__avatar';
        avatar.setAttribute('aria-hidden', 'true');
        const photo = payload.user?.profile_image_path || '';
        if (photo) {
            const img = document.createElement('img');
            img.src = photo;
            img.alt = '';
            img.loading = 'lazy';
            avatar.appendChild(img);
        } else {
            const initials = document.createElement('span');
            initials.textContent = payload.user?.initials || '?';
            avatar.appendChild(initials);
        }
        row.appendChild(avatar);
    }

    const bubble = document.createElement('div');
    bubble.className = 'chat-row__bubble';
    const body = document.createElement('p');
    body.className = 'chat-row__body whitespace-pre-wrap break-words';
    body.textContent = payload.body || '';
    bubble.appendChild(body);

    if (payload.created_at) {
        const meta = document.createElement('p');
        meta.className = 'chat-row__meta text-[10px] opacity-70';
        meta.textContent = new Date(payload.created_at).toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
        bubble.appendChild(meta);
    }

    row.appendChild(bubble);
    log.appendChild(row);
    log.scrollTop = log.scrollHeight;
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('dm-chat');
    if (!root) {
        return;
    }

    const channelName = root.dataset.channel;
    const userId = Number(root.dataset.userId);
    const log = document.getElementById('dm-messages');
    const form = document.getElementById('dm-form');
    const bodyInput = document.getElementById('dm-body');
    const postUrl = root.dataset.postUrl;
    const csrf = root.dataset.csrf;

    whenEchoReady(() => {
        if (!window.Echo || !channelName || !log) {
            return;
        }
        window.Echo.private(channelName).listen('.message.posted', (payload) => {
            appendDmRow(log, payload || {}, userId);
        });
    });

    form?.addEventListener('submit', async (event) => {
        if (!window.axios || !postUrl) {
            return;
        }
        event.preventDefault();
        const body = bodyInput?.value?.trim() || '';
        if (!body) {
            return;
        }

        const sendBtn = form.querySelector('button[type="submit"]');
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
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );
            const payload = response.data?.message || response.data?.data || null;
            if (payload) {
                appendDmRow(log, {
                    id: payload.id,
                    body: payload.body,
                    created_at: payload.created_at,
                    user: payload.user || { id: userId },
                }, userId);
            } else {
                // Fallback: reload if API shape unexpected.
                window.location.reload();
                return;
            }
            if (bodyInput) {
                bodyInput.value = '';
                bodyInput.focus();
            }
        } catch (error) {
            // Fall back to classic form submit on failure.
            form.submit();
            return;
        } finally {
            if (sendBtn) {
                sendBtn.disabled = false;
            }
        }
    });
});
