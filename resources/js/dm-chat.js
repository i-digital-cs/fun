/**
 * Direct message thread — Echo realtime + reply / react / voice / attachments.
 */
function whenEchoReady(callback) {
    if (window.Echo) {
        callback(window.Echo);
        return;
    }
    const onReady = () => window.Echo && callback(window.Echo);
    window.addEventListener('funnity:echo-ready', onReady, { once: true });
    let tries = 0;
    const timer = window.setInterval(() => {
        tries += 1;
        if (window.Echo) {
            window.clearInterval(timer);
            callback(window.Echo);
        } else if (tries >= 50) {
            window.clearInterval(timer);
        }
    }, 100);
}

function uuid() {
    if (crypto?.randomUUID) return crypto.randomUUID().slice(0, 36);
    return `c${Date.now()}${Math.random().toString(16).slice(2, 10)}`;
}

function appendDmRow(log, payload, currentUserId) {
    if (!payload?.id || log.querySelector(`[data-message-id="${payload.id}"]`)) return;
    document.getElementById('dm-empty')?.remove();

    const mine = Number(payload.user?.id) === currentUserId;
    const row = document.createElement('div');
    row.className = `chat-row ${mine ? 'chat-row--mine' : 'chat-row--theirs'}`;
    row.dataset.messageId = String(payload.id);
    row.dataset.body = payload.body || '';
    row.dataset.sender = payload.user?.name || '';

    if (!mine) {
        const avatar = document.createElement('span');
        avatar.className = 'chat-row__avatar';
        if (payload.user?.profile_image_path) {
            const img = document.createElement('img');
            img.src = payload.user.profile_image_path;
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

    if (payload.reply_to) {
        const quote = document.createElement('button');
        quote.type = 'button';
        quote.className = 'chat-reply-quote mb-1 w-full rounded-lg bg-black/10 px-2 py-1 text-start text-[11px]';
        quote.dataset.jumpTo = String(payload.reply_to.id);
        quote.innerHTML = `<span class="font-bold"></span><span class="block truncate opacity-80"></span>`;
        quote.querySelector('.font-bold').textContent = payload.reply_to.user?.name || '';
        quote.querySelector('.truncate').textContent = payload.reply_to.body || '';
        bubble.appendChild(quote);
    }

    if (payload.message_type === 'voice' && payload.media_url) {
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.preload = 'none';
        audio.className = 'max-w-full';
        audio.src = payload.media_url;
        bubble.appendChild(audio);
    } else if (payload.message_type === 'image' && payload.media_url) {
        const a = document.createElement('a');
        a.href = payload.media_url;
        a.target = '_blank';
        a.rel = 'noopener';
        const img = document.createElement('img');
        img.src = payload.media_url;
        img.alt = '';
        img.className = 'max-h-48 rounded-lg object-cover';
        a.appendChild(img);
        bubble.appendChild(a);
    } else {
        const body = document.createElement('p');
        body.className = 'chat-row__body whitespace-pre-wrap break-words';
        body.textContent = payload.body || '';
        bubble.appendChild(body);
    }

    const foot = document.createElement('div');
    foot.className = 'mt-1 flex items-center gap-2';
    const meta = document.createElement('p');
    meta.className = 'chat-row__meta text-[10px] opacity-70';
    meta.textContent = payload.created_at
        ? new Date(payload.created_at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true })
        : '';
    foot.appendChild(meta);
    const replyBtn = document.createElement('button');
    replyBtn.type = 'button';
    replyBtn.className = 'text-[10px] font-bold opacity-70 hover:opacity-100';
    replyBtn.dataset.replyTo = String(payload.id);
    replyBtn.textContent = 'Reply';
    foot.appendChild(replyBtn);
    const reactBtn = document.createElement('button');
    reactBtn.type = 'button';
    reactBtn.className = 'text-[10px] font-bold opacity-70 hover:opacity-100';
    reactBtn.dataset.reactOpen = String(payload.id);
    reactBtn.textContent = 'React';
    foot.appendChild(reactBtn);
    bubble.appendChild(foot);

    row.appendChild(bubble);
    log.appendChild(row);
    log.scrollTop = log.scrollHeight;
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('dm-chat');
    if (!root) return;

    const channelName = root.dataset.channel;
    const userId = Number(root.dataset.userId);
    const log = document.getElementById('dm-messages');
    const form = document.getElementById('dm-form');
    const bodyInput = document.getElementById('dm-body');
    const postUrl = root.dataset.postUrl;
    const csrf = root.dataset.csrf;
    const replyInput = document.getElementById('dm-reply-to');
    const clientUuidInput = document.getElementById('dm-client-uuid');
    const replyBar = document.getElementById('dm-reply-bar');
    const mediaInput = document.getElementById('dm-media');
    const mediaKind = document.getElementById('dm-media-kind');
    const durationInput = document.getElementById('dm-duration-ms');
    const voiceBtn = document.getElementById('dm-voice-btn');
    const voiceStatus = document.getElementById('dm-voice-status');
    const reactTemplate = root.dataset.reactUrlTemplate || '';
    const emojis = (root.dataset.emojis || '❤️,👍,😂,😮,😢,👏').split(',');

    let mediaRecorder = null;
    let recordedChunks = [];
    let recordStartedAt = 0;

    const setReply = (id, name, body) => {
        replyInput.value = id || '';
        if (!id) {
            replyBar.hidden = true;
            return;
        }
        replyBar.hidden = false;
        replyBar.querySelector('[data-reply-name]').textContent = name || '';
        replyBar.querySelector('[data-reply-body]').textContent = body || '';
    };

    document.getElementById('dm-reply-cancel')?.addEventListener('click', () => setReply('', '', ''));

    log?.addEventListener('click', (e) => {
        const replyTo = e.target.closest('[data-reply-to]');
        if (replyTo) {
            const row = replyTo.closest('[data-message-id]');
            setReply(replyTo.dataset.replyTo, row?.dataset.sender || '', row?.dataset.body || '');
            bodyInput?.focus();
            return;
        }
        const jump = e.target.closest('[data-jump-to]');
        if (jump) {
            const el = log.querySelector(`[data-message-id="${jump.dataset.jumpTo}"]`);
            el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            el?.classList.add('ring-2', 'ring-indigo-300');
            setTimeout(() => el?.classList.remove('ring-2', 'ring-indigo-300'), 1200);
            return;
        }
        const reactOpen = e.target.closest('[data-react-open]');
        if (reactOpen && reactTemplate) {
            const mid = reactOpen.dataset.reactOpen;
            const pick = window.prompt(`React: ${emojis.join(' ')}`, emojis[0]);
            if (!pick) return;
            fetch(reactTemplate.replace('__MID__', mid), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ emoji: pick }),
            }).catch(() => {});
        }
    });

    voiceBtn?.addEventListener('click', async () => {
        if (mediaRecorder && mediaRecorder.state === 'recording') {
            mediaRecorder.stop();
            voiceBtn.textContent = '🎤';
            return;
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            recordedChunks = [];
            recordStartedAt = Date.now();
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = (ev) => {
                if (ev.data.size > 0) recordedChunks.push(ev.data);
            };
            mediaRecorder.onstop = () => {
                stream.getTracks().forEach((t) => t.stop());
                const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                const file = new File([blob], `voice-${Date.now()}.webm`, { type: blob.type });
                const dt = new DataTransfer();
                dt.items.add(file);
                mediaInput.files = dt.files;
                mediaKind.value = 'voice';
                durationInput.value = String(Date.now() - recordStartedAt);
                voiceStatus.hidden = false;
                voiceStatus.textContent = 'Voice ready — tap Send';
            };
            mediaRecorder.start();
            voiceBtn.textContent = '⏹';
            voiceStatus.hidden = false;
            voiceStatus.textContent = 'Recording… tap mic to stop';
        } catch {
            voiceStatus.hidden = false;
            voiceStatus.textContent = 'Microphone permission denied';
        }
    });

    mediaInput?.addEventListener('change', () => {
        if (!mediaInput.files?.length) return;
        const file = mediaInput.files[0];
        mediaKind.value = file.type.startsWith('audio/') ? 'voice' : file.type.startsWith('image/') ? 'image' : 'file';
    });

    whenEchoReady((Echo) => {
        if (!channelName || !log) return;
        Echo.private(channelName).listen('.message.posted', (e) => {
            appendDmRow(log, e.message || e.data || e, userId);
        });
    });

    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = bodyInput?.value?.trim() || '';
        const hasMedia = mediaInput?.files?.length > 0;
        if (!body && !hasMedia) return;

        clientUuidInput.value = uuid();
        const fd = new FormData(form);
        if (!hasMedia) fd.delete('media');

        try {
            const res = await fetch(postUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: fd,
            });
            if (!res.ok) throw new Error('send failed');
            const json = await res.json();
            const payload = json.message || json.data || json;
            appendDmRow(log, payload, userId);
            form.reset();
            setReply('', '', '');
            mediaKind.value = '';
            durationInput.value = '';
            voiceStatus.hidden = true;
            clientUuidInput.value = '';
        } catch {
            form.submit();
        }
    });
});
