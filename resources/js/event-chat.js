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

    let quote = '';
    if (message.reply_to) {
        quote = `<button type="button" class="chat-reply-quote mb-1 w-full rounded-lg bg-black/10 px-2 py-1 text-start text-[11px]" data-jump-to="${escapeHtml(String(message.reply_to.id))}">
            <span class="font-bold">${escapeHtml(message.reply_to.user?.name || '')}</span>
            <span class="block truncate opacity-80">${escapeHtml(message.reply_to.body || '')}</span>
        </button>`;
    }

    let bodyHtml = `<p class="chat-message__body">${escapeHtml(message.body ?? '')}</p>`;
    if (message.message_type === 'voice' && message.media_url) {
        bodyHtml = `<audio controls preload="none" class="mb-1 max-w-full" src="${escapeHtml(message.media_url)}"></audio>`;
    } else if (message.message_type === 'image' && message.media_url) {
        bodyHtml = `<a href="${escapeHtml(message.media_url)}" target="_blank" rel="noopener" class="mb-1 block"><img src="${escapeHtml(message.media_url)}" alt="" class="max-h-48 rounded-lg object-cover" loading="lazy"></a>`;
    }

    el.dataset.body = message.body || '';
    el.dataset.sender = message.user?.name || '';

    const bubble = `
        <div class="chat-message ${mine ? 'chat-message--mine' : 'chat-message--theirs'}${isHost ? ' chat-message--host' : ''}${reactedClass}">
            ${author}
            ${quote}
            ${bodyHtml}
            <div class="chat-message__footer">
                ${reactionsHtml(message, options)}
                <button type="button" class="text-[10px] font-bold opacity-70" data-reply-to="${escapeHtml(String(message.id))}">Reply</button>
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
    const sendBtn = form?.querySelector('#chat-send, [data-chat-send]');
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

    const replyInput = document.getElementById('chat-reply-to');
    const clientUuidInput = document.getElementById('chat-client-uuid');
    const replyBar = document.getElementById('chat-reply-bar');
    const mediaInput = document.getElementById('chat-media');
    const mediaKind = document.getElementById('chat-media-kind');
    const durationInput = document.getElementById('chat-duration-ms');
    const voiceBtn = document.getElementById('chat-voice-btn');
    const voicePanel = document.getElementById('chat-voice-panel');
    const voicePreview = document.getElementById('chat-voice-preview');
    const voiceTimer = document.getElementById('chat-voice-timer');
    const voiceStateEl = document.getElementById('chat-voice-state');
    const voiceRecordBtn = document.getElementById('chat-voice-record');
    const voicePauseBtn = document.getElementById('chat-voice-pause');
    const voiceResumeBtn = document.getElementById('chat-voice-resume');
    const voiceStopBtn = document.getElementById('chat-voice-stop');
    const voiceAgainBtn = document.getElementById('chat-voice-again');
    const voiceDiscardBtn = document.getElementById('chat-voice-discard');
    const voiceSendBtn = document.getElementById('chat-voice-send');
    let mediaRecorder = null;
    let mediaStream = null;
    let recordedChunks = [];
    let recordAccumMs = 0;
    let segmentStartedAt = 0;
    let recordingLock = false;
    let voicePhase = 'idle';
    let voiceObjectUrl = null;
    let timerId = null;
    const stopTracks = () => {
        mediaStream?.getTracks()?.forEach((t) => t.stop());
        mediaStream = null;
    };
    const stopTimer = () => {
        if (timerId) window.clearInterval(timerId);
        timerId = null;
    };
    const formatTimer = (ms) => {
        const s = Math.max(0, Math.floor(ms / 1000));
        return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    };
    const elapsedMs = () => {
        let total = recordAccumMs;
        if (voicePhase === 'recording' && segmentStartedAt) total += Date.now() - segmentStartedAt;
        return total;
    };

    const setReply = (id, name, body) => {
        if (!replyInput) return;
        replyInput.value = id || '';
        if (!replyBar) return;
        if (!id) {
            replyBar.hidden = true;
            return;
        }
        replyBar.hidden = false;
        replyBar.querySelector('[data-reply-name]').textContent = name || '';
        replyBar.querySelector('[data-reply-body]').textContent = body || '';
    };

    document.getElementById('chat-reply-cancel')?.addEventListener('click', () => setReply('', '', ''));

    messagesEl?.addEventListener('click', (e) => {
        const replyTo = e.target.closest('[data-reply-to]');
        if (replyTo) {
            const row = replyTo.closest('[data-message-id]');
            setReply(replyTo.dataset.replyTo, row?.dataset.sender || '', row?.dataset.body || '');
            bodyInput?.focus();
            return;
        }
        const jump = e.target.closest('[data-jump-to]');
        if (jump) {
            const el = messagesEl.querySelector(`[data-message-id="${jump.dataset.jumpTo}"]`);
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    const pickRecorderMime = () => {
        if (typeof MediaRecorder === 'undefined' || !MediaRecorder.isTypeSupported) return '';
        return (
            ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus', 'audio/ogg'].find(
                (t) => MediaRecorder.isTypeSupported(t)
            ) || ''
        );
    };
    const extForMime = (mime) => {
        const base = (mime || '').split(';')[0];
        if (base.includes('mp4') || base.includes('m4a')) return 'm4a';
        if (base.includes('ogg')) return 'ogg';
        return 'webm';
    };
    const isHeic = (file) => {
        const type = (file?.type || '').toLowerCase();
        const name = (file?.name || '').toLowerCase();
        return type.includes('heic') || type.includes('heif') || /\.heic$|\.heif$/.test(name);
    };
    const formI18n = {
        heic: form?.dataset.i18nHeic || 'HEIC/HEIF photos are not supported. Please send a JPEG or PNG.',
        voiceReady: form?.dataset.i18nVoiceReady || 'Preview ready — Send or delete',
        voiceRecording: form?.dataset.i18nVoiceRecording || 'Recording…',
        voicePaused: form?.dataset.i18nVoicePaused || 'Paused',
        micDenied: form?.dataset.i18nMicDenied || 'Microphone permission denied.',
        micUnsupported: form?.dataset.i18nMicUnsupported || 'Voice notes are not supported in this browser.',
        voiceEmpty: form?.dataset.i18nVoiceEmpty || 'Recording failed or was empty.',
    };
    const setVoiceStatus = (msg) => {
        if (!statusEl) return;
        statusEl.textContent = msg || '';
        statusEl.classList.toggle('hidden', !msg);
    };
    const syncVoiceUi = () => {
        if (voicePanel) {
            voicePanel.hidden = voicePhase === 'idle';
            voicePanel.dataset.voiceState = voicePhase;
        }
        const show = (el, on) => {
            if (el) el.hidden = !on;
        };
        show(voiceRecordBtn, voicePhase === 'idle');
        show(voicePauseBtn, voicePhase === 'recording');
        show(voiceResumeBtn, voicePhase === 'paused');
        show(voiceStopBtn, voicePhase === 'recording' || voicePhase === 'paused');
        show(voiceAgainBtn, voicePhase === 'preview');
        show(voiceDiscardBtn, voicePhase !== 'idle');
        show(voiceSendBtn, voicePhase === 'preview');
        if (voicePreview) voicePreview.classList.toggle('hidden', voicePhase !== 'preview');
        if (voiceStateEl) {
            voiceStateEl.textContent =
                voicePhase === 'recording'
                    ? formI18n.voiceRecording
                    : voicePhase === 'paused'
                      ? formI18n.voicePaused
                      : voicePhase === 'preview'
                        ? formI18n.voiceReady
                        : '';
        }
        if (voiceTimer) voiceTimer.textContent = formatTimer(elapsedMs());
        if (voiceBtn) voiceBtn.textContent = voicePhase === 'recording' || voicePhase === 'paused' ? '⏹' : '🎤';
    };
    const applyDraft = (file, durationMs) => {
        const dt = new DataTransfer();
        dt.items.add(file);
        if (mediaInput) mediaInput.files = dt.files;
        if (mediaKind) mediaKind.value = 'voice';
        if (durationInput) durationInput.value = String(Math.max(1, durationMs));
        if (voiceObjectUrl) URL.revokeObjectURL(voiceObjectUrl);
        voiceObjectUrl = URL.createObjectURL(file);
        if (voicePreview) {
            voicePreview.src = voiceObjectUrl;
            voicePreview.load();
        }
        recordAccumMs = durationMs;
        voicePhase = 'preview';
        syncVoiceUi();
        setVoiceStatus(formI18n.voiceReady);
    };
    const clearVoice = () => {
        stopTimer();
        stopTracks();
        if (voiceObjectUrl) URL.revokeObjectURL(voiceObjectUrl);
        voiceObjectUrl = null;
        recordedChunks = [];
        recordAccumMs = 0;
        segmentStartedAt = 0;
        mediaRecorder = null;
        if (mediaKind?.value === 'voice') {
            if (mediaInput) mediaInput.value = '';
            mediaKind.value = '';
            if (durationInput) durationInput.value = '';
        }
        if (voicePreview) {
            voicePreview.removeAttribute('src');
            voicePreview.load();
        }
        voicePhase = 'idle';
        syncVoiceUi();
        setVoiceStatus('');
    };
    const startRecording = async () => {
        if (recordingLock) return;
        if (typeof MediaRecorder === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
            setVoiceStatus(formI18n.micUnsupported);
            return;
        }
        recordingLock = true;
        try {
            clearVoice();
            mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: { echoCancellation: true, noiseSuppression: true },
            });
            recordedChunks = [];
            recordAccumMs = 0;
            segmentStartedAt = Date.now();
            const mime = pickRecorderMime();
            mediaRecorder = mime
                ? new MediaRecorder(mediaStream, { mimeType: mime })
                : new MediaRecorder(mediaStream);
            mediaRecorder.ondataavailable = (ev) => {
                if (ev.data?.size) recordedChunks.push(ev.data);
            };
            mediaRecorder.onstop = () => {
                stopTracks();
                stopTimer();
                const type = (mediaRecorder?.mimeType || mime || 'audio/webm').split(';')[0];
                const blob = new Blob(recordedChunks, { type });
                mediaRecorder = null;
                if (blob.size < 256) {
                    voicePhase = 'idle';
                    syncVoiceUi();
                    setVoiceStatus(formI18n.voiceEmpty);
                    return;
                }
                applyDraft(
                    new File([blob], `voice-${Date.now()}.${extForMime(type)}`, { type }),
                    Math.max(1, elapsedMs())
                );
            };
            mediaRecorder.start(250);
            voicePhase = 'recording';
            if (voicePanel) voicePanel.hidden = false;
            syncVoiceUi();
            timerId = window.setInterval(() => {
                if (voiceTimer) voiceTimer.textContent = formatTimer(elapsedMs());
            }, 200);
            setVoiceStatus(formI18n.voiceRecording);
        } catch {
            stopTracks();
            voicePhase = 'idle';
            syncVoiceUi();
            setVoiceStatus(formI18n.micDenied);
        } finally {
            recordingLock = false;
        }
    };

    voiceRecordBtn?.addEventListener('click', () => startRecording());
    voicePauseBtn?.addEventListener('click', () => {
        if (!mediaRecorder || mediaRecorder.state !== 'recording') return;
        mediaRecorder.pause();
        mediaRecorder.requestData?.();
        recordAccumMs += Date.now() - segmentStartedAt;
        segmentStartedAt = 0;
        voicePhase = 'paused';
        syncVoiceUi();
        setVoiceStatus(formI18n.voicePaused);
    });
    voiceResumeBtn?.addEventListener('click', () => {
        if (!mediaRecorder || mediaRecorder.state !== 'paused') return;
        mediaRecorder.resume();
        segmentStartedAt = Date.now();
        voicePhase = 'recording';
        syncVoiceUi();
        setVoiceStatus(formI18n.voiceRecording);
    });
    voiceStopBtn?.addEventListener('click', () => {
        if (!mediaRecorder) return;
        if (mediaRecorder.state === 'paused') {
            try {
                mediaRecorder.resume();
                segmentStartedAt = Date.now();
                voicePhase = 'recording';
            } catch {
                /* ignore */
            }
        }
        if (mediaRecorder.state === 'recording') {
            recordAccumMs += Date.now() - segmentStartedAt;
            segmentStartedAt = 0;
        }
        mediaRecorder.requestData?.();
        mediaRecorder.stop();
    });
    voiceAgainBtn?.addEventListener('click', () => startRecording());
    voiceDiscardBtn?.addEventListener('click', () => {
        if (mediaRecorder && (mediaRecorder.state === 'recording' || mediaRecorder.state === 'paused')) {
            mediaRecorder.onstop = () => clearVoice();
            try {
                mediaRecorder.stop();
            } catch {
                clearVoice();
            }
            return;
        }
        clearVoice();
    });
    voiceSendBtn?.addEventListener('click', () => form?.requestSubmit());
    voiceBtn?.addEventListener('click', async () => {
        if (voicePhase === 'recording' || voicePhase === 'paused') {
            voiceStopBtn?.click();
            return;
        }
        await startRecording();
    });

    window.addEventListener('pagehide', () => {
        try {
            if (mediaRecorder && (mediaRecorder.state === 'recording' || mediaRecorder.state === 'paused')) {
                mediaRecorder.stop();
            }
        } catch {
            /* ignore */
        }
        stopTracks();
        stopTimer();
    });

    mediaInput?.addEventListener('change', () => {
        if (!mediaInput.files?.length || !mediaKind) return;
        const file = mediaInput.files[0];
        if (isHeic(file)) {
            mediaInput.value = '';
            mediaKind.value = '';
            setVoiceStatus(formI18n.heic);
            return;
        }
        mediaKind.value = file.type.startsWith('audio/')
            ? 'voice'
            : file.type.startsWith('image/')
              ? 'image'
              : 'file';
    });

    attachBtn?.addEventListener('click', () => {
        mediaInput?.click();
    });

    const bindRealtime = () => {
        if (!window.Echo || !channelName) {
            if (statusEl) {
                statusEl.textContent = statusEl.dataset.fallback || 'Live updates unavailable.';
                statusEl.classList.remove('hidden');
            }
            return;
        }
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
        statusEl?.classList.add('hidden');
    };

    if (window.Echo) {
        bindRealtime();
    } else {
        const onReady = () => {
            window.removeEventListener('funnity:echo-ready', onReady);
            bindRealtime();
        };
        window.addEventListener('funnity:echo-ready', onReady, { once: true });
        let tries = 0;
        const timer = window.setInterval(() => {
            tries += 1;
            if (window.Echo) {
                window.clearInterval(timer);
                window.removeEventListener('funnity:echo-ready', onReady);
                bindRealtime();
                return;
            }
            if (tries >= 50) {
                window.clearInterval(timer);
                window.removeEventListener('funnity:echo-ready', onReady);
                bindRealtime();
            }
        }, 100);
    }

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = bodyInput?.value?.trim() || '';
        const hasMedia = mediaInput?.files?.length > 0;
        if (!body && !hasMedia) {
            return;
        }

        if (sendBtn) {
            sendBtn.disabled = true;
        }

        if (clientUuidInput) {
            clientUuidInput.value = crypto?.randomUUID
                ? crypto.randomUUID().slice(0, 36)
                : `c${Date.now()}${Math.random().toString(16).slice(2, 10)}`;
        }

        const fd = new FormData(form);
        if (!hasMedia) fd.delete('media');

        try {
            const response = await fetch(postUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: fd,
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(json.message || json.errors?.body?.[0] || 'Unable to send message.');
            }

            appendMessage(messagesEl, json.data || json.message || json, emptyEl, currentUserId, {
                ...appendOptions,
                forceScroll: true,
            });
            form.reset();
            setReply('', '', '');
            if (mediaKind) mediaKind.value = '';
            if (durationInput) durationInput.value = '';
            if (clientUuidInput) clientUuidInput.value = '';
            if (bodyInput) autosize(bodyInput);
            if (statusEl && !statusEl.dataset.fallbackActive) {
                statusEl.classList.add('hidden');
            }
        } catch (error) {
            const message = error?.message || 'Unable to send message.';
            if (statusEl) {
                statusEl.textContent = message;
                statusEl.classList.remove('hidden');
            }
        } finally {
            if (sendBtn) {
                sendBtn.disabled = false;
            }
            bodyInput?.focus();
        }
    });
});
