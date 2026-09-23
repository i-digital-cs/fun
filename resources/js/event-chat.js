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

function appendMessage(container, message, emptyEl, currentUserId) {
    if (container.querySelector(`[data-message-id="${message.id}"]`)) {
        return;
    }

    emptyEl?.remove();

    const time = message.created_at
        ? new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : '';
    const mine = Number(message.user?.id) === Number(currentUserId);

    const el = document.createElement('div');
    el.className = `chat-message ${mine ? 'chat-message--mine' : 'chat-message--theirs'}`;
    el.dataset.messageId = String(message.id);
    el.dataset.mine = mine ? '1' : '0';
    el.innerHTML = `
        <div class="chat-message__meta">
            <p class="chat-message__author">${escapeHtml(message.user?.name ?? '')}</p>
            <div class="chat-message__meta-end">
                <time class="chat-message__time">${escapeHtml(time)}</time>
            </div>
        </div>
        <p class="chat-message__body">${escapeHtml(message.body ?? '')}</p>
    `;
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
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

    messagesEl.scrollTop = messagesEl.scrollHeight;

    if (bodyInput) {
        autosize(bodyInput);
        bodyInput.addEventListener('input', () => autosize(bodyInput));
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
            appendMessage(messagesEl, payload, emptyEl, currentUserId);
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

            appendMessage(messagesEl, response.data.data, emptyEl, currentUserId);
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
