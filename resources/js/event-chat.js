function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function appendMessage(container, message, emptyEl) {
    if (container.querySelector(`[data-message-id="${message.id}"]`)) {
        return;
    }

    emptyEl?.remove();

    const time = message.created_at
        ? new Date(message.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        : '';

    const el = document.createElement('div');
    el.className = 'chat-message rounded-xl bg-background px-3 py-2';
    el.dataset.messageId = String(message.id);
    el.innerHTML = `
        <div class="flex items-baseline justify-between gap-2">
            <p class="text-sm font-semibold">${escapeHtml(message.user?.name ?? '')}</p>
            <time class="text-xs text-muted">${escapeHtml(time)}</time>
        </div>
        <p class="mt-1 whitespace-pre-wrap break-words text-sm">${escapeHtml(message.body ?? '')}</p>
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
    const channelName = root.dataset.channel;
    const postUrl = root.dataset.postUrl;
    const csrf = root.dataset.csrf;
    const currentUserId = Number(root.dataset.userId);

    messagesEl.scrollTop = messagesEl.scrollHeight;

    if (window.Echo && channelName) {
        window.Echo.private(channelName).listen('.message.posted', (payload) => {
            if (Number(payload.user?.id) === currentUserId) {
                return;
            }
            appendMessage(messagesEl, payload, emptyEl);
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

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

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

            appendMessage(messagesEl, response.data.data, emptyEl);
            bodyInput.value = '';
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
            submitBtn.disabled = false;
            bodyInput.focus();
        }
    });
});
