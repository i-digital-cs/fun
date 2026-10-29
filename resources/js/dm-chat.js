/**
 * Direct message thread — Echo realtime + reply / react / voice V3 / attachments.
 * HTTP response is authoritative; realtime notifies peers and reconciles by id/client_uuid.
 *
 * Voice draft retention: the in-memory Blob/File (`voiceDraftFile`) is kept after
 * network failures so Retry can reuse the same `client_uuid`. Drafts are cleared
 * only after server confirmation (or explicit Delete). Page reload discards the
 * draft — recovery after reload is not supported.
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

function pickRecorderMime() {
    if (typeof MediaRecorder === 'undefined' || !MediaRecorder.isTypeSupported) {
        return '';
    }
    const candidates = [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/mp4',
        'audio/ogg;codecs=opus',
        'audio/ogg',
    ];
    return candidates.find((t) => MediaRecorder.isTypeSupported(t)) || '';
}

function extForMime(mime) {
    const base = (mime || '').split(';')[0];
    if (base.includes('mp4') || base.includes('m4a')) return 'm4a';
    if (base.includes('ogg')) return 'ogg';
    return 'webm';
}

function isHeic(file) {
    const type = (file?.type || '').toLowerCase();
    const name = (file?.name || '').toLowerCase();
    return type.includes('heic') || type.includes('heif') || /\.heic$|\.heif$/.test(name);
}

function appendDmRow(log, payload, currentUserId, opts = {}) {
    if (!payload) return null;
    const clientUuid = payload.client_uuid || opts.clientUuid || '';
    if (payload.id && log.querySelector(`[data-message-id="${payload.id}"]`)) {
        return log.querySelector(`[data-message-id="${payload.id}"]`);
    }
    if (clientUuid) {
        const pending = log.querySelector(`[data-client-uuid="${clientUuid}"]`);
        if (pending && payload.id) {
            pending.dataset.messageId = String(payload.id);
            pending.classList.remove('is-pending', 'is-failed');
            pending.querySelector('[data-retry]')?.remove();
            pending.querySelector('[data-upload-progress]')?.remove();
            pending.remove();
        } else if (pending && !payload.id) {
            return pending;
        }
    }

    document.getElementById('dm-empty')?.remove();

    const mine = Number(payload.user?.id) === currentUserId || opts.mine === true;
    const row = document.createElement('div');
    row.className = `chat-row ${mine ? 'chat-row--mine' : 'chat-row--theirs'}${opts.pending ? ' is-pending' : ''}${opts.failed ? ' is-failed' : ''}`;
    if (payload.id) row.dataset.messageId = String(payload.id);
    if (clientUuid) row.dataset.clientUuid = clientUuid;
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

    if (payload.message_type === 'voice' && (payload.media_url || opts.previewUrl)) {
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.preload = 'metadata';
        audio.className = 'max-w-full';
        audio.src = payload.media_url || opts.previewUrl;
        audio.addEventListener('error', () => {
            audio.insertAdjacentHTML(
                'afterend',
                '<p class="mt-1 text-[10px] font-bold text-rose-200">Audio unavailable</p>'
            );
        });
        bubble.appendChild(audio);
        if (payload.media_duration_ms) {
            const dur = document.createElement('p');
            dur.className = 'mt-0.5 text-[10px] opacity-70';
            dur.textContent = `${Math.max(1, Math.round(Number(payload.media_duration_ms) / 1000))}s`;
            bubble.appendChild(dur);
        }
    } else if (payload.message_type === 'image' && (payload.media_url || opts.previewUrl)) {
        const a = document.createElement('a');
        a.href = payload.media_url || opts.previewUrl;
        a.target = '_blank';
        a.rel = 'noopener';
        const img = document.createElement('img');
        img.src = payload.media_url || opts.previewUrl;
        img.alt = '';
        img.className = 'max-h-56 w-full max-w-xs rounded-lg object-cover';
        a.appendChild(img);
        bubble.appendChild(a);
    } else {
        const body = document.createElement('p');
        body.className = 'chat-row__body whitespace-pre-wrap break-words';
        body.dir = 'auto';
        body.textContent = payload.body || (opts.pending ? '…' : '');
        bubble.appendChild(body);
    }

    const foot = document.createElement('div');
    foot.className = 'mt-1 flex flex-wrap items-center gap-2';
    const meta = document.createElement('p');
    meta.className = 'chat-row__meta text-[10px] opacity-70';
    meta.dataset.meta = '1';
    if (opts.pending) {
        meta.textContent = opts.pendingLabel || '…';
    } else if (opts.failed) {
        meta.textContent = opts.failedLabel || 'Failed';
    } else {
        meta.textContent = payload.created_at
            ? new Date(payload.created_at).toLocaleTimeString([], {
                  hour: 'numeric',
                  minute: '2-digit',
                  hour12: true,
              })
            : '';
    }
    foot.appendChild(meta);

    if (opts.pending) {
        const prog = document.createElement('span');
        prog.className = 'text-[10px] font-bold opacity-80';
        prog.dataset.uploadProgress = '1';
        prog.textContent = opts.progressText || '';
        foot.appendChild(prog);
    }

    if (opts.failed && typeof opts.onRetry === 'function') {
        const retry = document.createElement('button');
        retry.type = 'button';
        retry.className = 'chat-row__retry';
        retry.dataset.retry = '1';
        retry.textContent = opts.retryLabel || 'Retry';
        retry.addEventListener('click', (ev) => {
            ev.preventDefault();
            opts.onRetry();
        });
        foot.appendChild(retry);
    } else if (!opts.pending && !opts.failed) {
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
    }
    bubble.appendChild(foot);

    row.appendChild(bubble);
    log.appendChild(row);
    log.scrollTop = log.scrollHeight;
    return row;
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
    const sendBtn = document.getElementById('dm-send');
    const attachPreview = form?.querySelector('[data-chat-attach-preview]');
    const attachLabel = form?.querySelector('[data-chat-attach-label]');
    const reactTemplate = root.dataset.reactUrlTemplate || '';
    const emojis = (root.dataset.emojis || '❤️,👍,😂,😮,😢,👏').split(',');

    const i18n = {
        sendFailed: form?.dataset.i18nSendFailed || 'Message could not be sent. Tap to retry.',
        voiceReady: form?.dataset.i18nVoiceReady || 'Preview ready — Send or delete',
        voiceRecording: form?.dataset.i18nVoiceRecording || 'Recording…',
        voicePaused: form?.dataset.i18nVoicePaused || 'Paused',
        micDenied: form?.dataset.i18nMicDenied || 'Microphone permission denied.',
        micUnsupported: form?.dataset.i18nMicUnsupported || 'Voice notes are not supported in this browser.',
        heic: form?.dataset.i18nHeic || 'HEIC/HEIF photos are not supported. Please send a JPEG or PNG.',
        voiceEmpty: form?.dataset.i18nVoiceEmpty || 'Recording failed or was empty. Please try again.',
        voiceUploading: form?.dataset.i18nVoiceUploading || 'Uploading voice…',
        voicePending: form?.dataset.i18nVoicePending || 'Pending',
        voiceFailed: form?.dataset.i18nVoiceFailed || 'Failed',
        voiceProgress: form?.dataset.i18nVoiceProgress || 'Uploading :pct%',
        retry: form?.dataset.i18nRetry || 'Retry',
    };

    let mediaRecorder = null;
    let mediaStream = null;
    let recordedChunks = [];
    let recordAccumMs = 0;
    let segmentStartedAt = 0;
    let previewObjectUrl = null;
    let voiceObjectUrl = null;
    let voiceDraftFile = null;
    let timerId = null;
    let recordingLock = false;
    let sending = false;
    let voicePhase = 'idle'; // idle | recording | paused | preview
    let activeClientUuid = null;
    let lastFailedUuid = null;

    const voicePanel = document.getElementById('dm-voice-panel');
    const voicePreview = document.getElementById('dm-voice-preview');
    const voiceTimer = document.getElementById('dm-voice-timer');
    const voiceStateEl = document.getElementById('dm-voice-state');
    const voiceRecordBtn = document.getElementById('dm-voice-record');
    const voicePauseBtn = document.getElementById('dm-voice-pause');
    const voiceResumeBtn = document.getElementById('dm-voice-resume');
    const voiceStopBtn = document.getElementById('dm-voice-stop');
    const voiceAgainBtn = document.getElementById('dm-voice-again');
    const voiceDiscardBtn = document.getElementById('dm-voice-discard');
    const voiceSendBtn = document.getElementById('dm-voice-send');
    const progressWrap = document.getElementById('dm-voice-progress-wrap');
    const progressBar = document.getElementById('dm-voice-progress-bar');
    const progressLabel = document.getElementById('dm-voice-progress-label');

    const setStatus = (msg, show = true) => {
        if (!voiceStatus) return;
        voiceStatus.textContent = msg || '';
        voiceStatus.hidden = !show || !msg;
        voiceStatus.classList.toggle('hidden', !show || !msg);
    };

    const formatTimer = (ms) => {
        const s = Math.max(0, Math.floor(ms / 1000));
        return `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;
    };

    const elapsedMs = () => {
        let total = recordAccumMs;
        if (voicePhase === 'recording' && segmentStartedAt) {
            total += Date.now() - segmentStartedAt;
        }
        return total;
    };

    const stopTracks = () => {
        mediaStream?.getTracks()?.forEach((t) => t.stop());
        mediaStream = null;
    };

    const stopTimer = () => {
        if (timerId) window.clearInterval(timerId);
        timerId = null;
    };

    const startTimer = () => {
        stopTimer();
        timerId = window.setInterval(() => {
            if (voiceTimer) voiceTimer.textContent = formatTimer(elapsedMs());
        }, 200);
    };

    const setProgress = (pct) => {
        const n = Math.max(0, Math.min(100, Math.round(pct)));
        if (progressWrap) progressWrap.hidden = false;
        if (progressBar) progressBar.style.width = `${n}%`;
        if (progressLabel) {
            progressLabel.textContent = i18n.voiceProgress.replace(':pct', String(n));
        }
        const pending = activeClientUuid
            ? log?.querySelector(`[data-client-uuid="${activeClientUuid}"]`)
            : null;
        const el = pending?.querySelector('[data-upload-progress]');
        if (el) el.textContent = i18n.voiceProgress.replace(':pct', String(n));
    };

    const hideProgress = () => {
        if (progressWrap) progressWrap.hidden = true;
        if (progressBar) progressBar.style.width = '0%';
        if (progressLabel) progressLabel.textContent = '';
    };

    const syncVoiceUi = () => {
        if (voicePanel) {
            voicePanel.hidden = voicePhase === 'idle';
            voicePanel.dataset.voiceState = voicePhase;
        }
        const show = (el, on) => {
            if (el) el.hidden = !on;
        };
        show(voiceRecordBtn, voicePhase === 'idle' || voicePhase === 'preview');
        if (voiceRecordBtn && voicePhase === 'preview') {
            voiceRecordBtn.hidden = true;
        }
        show(voicePauseBtn, voicePhase === 'recording');
        show(voiceResumeBtn, voicePhase === 'paused');
        show(voiceStopBtn, voicePhase === 'recording' || voicePhase === 'paused');
        show(voiceAgainBtn, voicePhase === 'preview');
        show(voiceDiscardBtn, voicePhase === 'preview' || voicePhase === 'paused' || voicePhase === 'recording');
        show(voiceSendBtn, voicePhase === 'preview');
        if (voicePreview) {
            voicePreview.classList.toggle('hidden', voicePhase !== 'preview');
        }
        if (voiceStateEl) {
            voiceStateEl.textContent =
                voicePhase === 'recording'
                    ? i18n.voiceRecording
                    : voicePhase === 'paused'
                      ? i18n.voicePaused
                      : voicePhase === 'preview'
                        ? i18n.voiceReady
                        : '';
        }
        if (voiceBtn) {
            voiceBtn.setAttribute('aria-pressed', voicePhase === 'recording' || voicePhase === 'paused' ? 'true' : 'false');
            voiceBtn.textContent = voicePhase === 'recording' || voicePhase === 'paused' ? '⏹' : '🎤';
        }
        if (voiceTimer) voiceTimer.textContent = formatTimer(elapsedMs());
    };

    const clearVoiceDraft = ({ keepPanel = false } = {}) => {
        stopTimer();
        if (voiceObjectUrl) {
            URL.revokeObjectURL(voiceObjectUrl);
            voiceObjectUrl = null;
        }
        voiceDraftFile = null;
        if (voicePreview) {
            voicePreview.removeAttribute('src');
            voicePreview.load();
        }
        if (mediaKind?.value === 'voice') {
            if (mediaInput) mediaInput.value = '';
            mediaKind.value = '';
            if (durationInput) durationInput.value = '';
        }
        recordAccumMs = 0;
        segmentStartedAt = 0;
        recordedChunks = [];
        voicePhase = keepPanel ? 'idle' : 'idle';
        hideProgress();
        if (!keepPanel && voicePanel) voicePanel.hidden = true;
        syncVoiceUi();
    };

    const clearAttach = () => {
        if (mediaInput) mediaInput.value = '';
        if (mediaKind) mediaKind.value = '';
        if (durationInput) durationInput.value = '';
        if (previewObjectUrl) {
            URL.revokeObjectURL(previewObjectUrl);
            previewObjectUrl = null;
        }
        if (attachPreview) attachPreview.hidden = true;
        if (attachLabel) attachLabel.textContent = '';
        clearVoiceDraft();
    };

    const applyVoiceDraft = (file, durationMs) => {
        voiceDraftFile = file;
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
            voicePreview.classList.remove('hidden');
        }
        recordAccumMs = durationMs;
        voicePhase = 'preview';
        if (voicePanel) voicePanel.hidden = false;
        syncVoiceUi();
        setStatus(i18n.voiceReady, true);
    };

    const showAttachPreview = (file) => {
        if (!attachPreview || !attachLabel || !file) return;
        attachLabel.textContent = file.name || file.type || 'Attachment';
        attachPreview.hidden = false;
        if (previewObjectUrl) URL.revokeObjectURL(previewObjectUrl);
        if (file.type?.startsWith('image/')) {
            previewObjectUrl = URL.createObjectURL(file);
            attachLabel.textContent = `🖼 ${file.name || 'Image'}`;
        }
    };

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
    form?.querySelector('[data-chat-attach-clear]')?.addEventListener('click', () => {
        clearAttach();
        setStatus('', false);
    });

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

    const finishRecordingToDraft = () => {
        stopTracks();
        stopTimer();
        const type = (mediaRecorder?.mimeType || pickRecorderMime() || 'audio/webm').split(';')[0];
        const blob = new Blob(recordedChunks, { type });
        mediaRecorder = null;
        if (blob.size < 256) {
            voicePhase = 'idle';
            syncVoiceUi();
            setStatus(i18n.voiceEmpty, true);
            return;
        }
        const durationMs = Math.max(1, elapsedMs());
        const file = new File([blob], `voice-${Date.now()}.${extForMime(type)}`, {
            type,
            lastModified: Date.now(),
        });
        applyVoiceDraft(file, durationMs);
    };

    const startRecording = async () => {
        if (recordingLock || sending) return;
        if (typeof MediaRecorder === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
            setStatus(i18n.micUnsupported, true);
            return;
        }
        recordingLock = true;
        try {
            clearVoiceDraft({ keepPanel: true });
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
                if (ev.data && ev.data.size > 0) recordedChunks.push(ev.data);
            };
            mediaRecorder.onerror = () => {
                stopTracks();
                stopTimer();
                mediaRecorder = null;
                voicePhase = 'idle';
                syncVoiceUi();
                setStatus(i18n.voiceEmpty, true);
            };
            mediaRecorder.onstop = () => {
                finishRecordingToDraft();
            };
            mediaRecorder.start(250);
            voicePhase = 'recording';
            if (voicePanel) voicePanel.hidden = false;
            syncVoiceUi();
            startTimer();
            setStatus(i18n.voiceRecording, true);
        } catch {
            stopTracks();
            voicePhase = 'idle';
            syncVoiceUi();
            setStatus(i18n.micDenied, true);
        } finally {
            recordingLock = false;
        }
    };

    const pauseRecording = () => {
        if (!mediaRecorder || mediaRecorder.state !== 'recording') return;
        try {
            mediaRecorder.pause();
            mediaRecorder.requestData?.();
            recordAccumMs += Date.now() - segmentStartedAt;
            segmentStartedAt = 0;
            voicePhase = 'paused';
            syncVoiceUi();
            setStatus(i18n.voicePaused, true);
        } catch {
            setStatus(i18n.voiceEmpty, true);
        }
    };

    const resumeRecording = () => {
        if (!mediaRecorder || mediaRecorder.state !== 'paused') return;
        try {
            mediaRecorder.resume();
            segmentStartedAt = Date.now();
            voicePhase = 'recording';
            syncVoiceUi();
            startTimer();
            setStatus(i18n.voiceRecording, true);
        } catch {
            setStatus(i18n.voiceEmpty, true);
        }
    };

    const stopRecording = () => {
        if (!mediaRecorder) return;
        if (recordingLock) return;
        recordingLock = true;
        try {
            if (mediaRecorder.state === 'paused') {
                // Some engines deliver a more reliable final blob if resumed briefly before stop.
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
            if (mediaRecorder.state === 'recording' || mediaRecorder.state === 'paused') {
                mediaRecorder.requestData?.();
                mediaRecorder.stop();
            }
        } finally {
            recordingLock = false;
        }
    };

    const cancelLiveRecording = () => {
        if (mediaRecorder && (mediaRecorder.state === 'recording' || mediaRecorder.state === 'paused')) {
            mediaRecorder.onstop = () => {
                stopTracks();
                stopTimer();
                mediaRecorder = null;
                clearVoiceDraft();
                setStatus('', false);
            };
            try {
                mediaRecorder.stop();
            } catch {
                stopTracks();
                mediaRecorder = null;
                clearVoiceDraft();
            }
            return;
        }
        clearVoiceDraft();
        setStatus('', false);
    };

    voiceRecordBtn?.addEventListener('click', () => startRecording());
    voicePauseBtn?.addEventListener('click', () => pauseRecording());
    voiceResumeBtn?.addEventListener('click', () => resumeRecording());
    voiceStopBtn?.addEventListener('click', () => stopRecording());
    voiceAgainBtn?.addEventListener('click', () => startRecording());
    voiceDiscardBtn?.addEventListener('click', () => cancelLiveRecording());
    voiceSendBtn?.addEventListener('click', () => {
        if (voicePhase !== 'preview' || !voiceDraftFile) return;
        sendMessage();
    });

    voiceBtn?.addEventListener('click', async () => {
        if (voicePhase === 'recording' || voicePhase === 'paused') {
            stopRecording();
            return;
        }
        // Idle or preview: start / record again.
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
        if (!mediaInput.files?.length) return;
        const file = mediaInput.files[0];
        if (isHeic(file)) {
            clearAttach();
            setStatus(i18n.heic, true);
            return;
        }
        mediaKind.value = file.type.startsWith('audio/')
            ? 'voice'
            : file.type.startsWith('image/')
              ? 'image'
              : 'file';
        showAttachPreview(file);
        setStatus('', false);
    });

    const xhrSend = (fd, onProgress) =>
        new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', postUrl);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.withCredentials = true;
            xhr.upload.onprogress = (ev) => {
                if (!ev.lengthComputable || typeof onProgress !== 'function') return;
                onProgress((ev.loaded / ev.total) * 100);
            };
            xhr.onload = () => {
                let json = {};
                try {
                    json = JSON.parse(xhr.responseText || '{}');
                } catch {
                    json = {};
                }
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve({ ok: true, status: xhr.status, json });
                } else {
                    reject(new Error(json.message || json.errors?.body?.[0] || i18n.sendFailed));
                }
            };
            xhr.onerror = () => reject(new Error(i18n.sendFailed));
            xhr.send(fd);
        });

    const sendMessage = async ({ reuseUuid = null } = {}) => {
        if (sending) return;
        if (voicePhase === 'recording' || voicePhase === 'paused') {
            setStatus(i18n.voiceRecording, true);
            return;
        }
        if (voiceDraftFile && (!mediaInput?.files?.length || mediaKind.value !== 'voice')) {
            applyVoiceDraft(voiceDraftFile, Number(durationInput.value) || 1);
        }
        const body = bodyInput?.value?.trim() || '';
        const hasMedia = mediaInput?.files?.length > 0;
        if (!body && !hasMedia) return;
        if (hasMedia && mediaKind.value === 'voice' && mediaInput.files[0].size < 256) {
            setStatus(i18n.voiceEmpty, true);
            return;
        }

        const clientUuid = reuseUuid || uuid();
        activeClientUuid = clientUuid;
        clientUuidInput.value = clientUuid;
        const fd = new FormData(form);
        if (!hasMedia) fd.delete('media');
        fd.set('client_uuid', clientUuid);

        const isVoice = mediaKind.value === 'voice';
        const previewUrl =
            hasMedia && mediaInput.files[0].type.startsWith('image/')
                ? URL.createObjectURL(mediaInput.files[0])
                : isVoice && voiceObjectUrl
                  ? voiceObjectUrl
                  : null;

        // Reuse existing failed/pending row for same uuid (no duplicate bubble).
        let pendingRow = log?.querySelector(`[data-client-uuid="${clientUuid}"]`);
        if (pendingRow) {
            pendingRow.classList.add('is-pending');
            pendingRow.classList.remove('is-failed');
            pendingRow.querySelector('[data-retry]')?.remove();
            const meta = pendingRow.querySelector('[data-meta]');
            if (meta) meta.textContent = i18n.voicePending;
            let prog = pendingRow.querySelector('[data-upload-progress]');
            if (!prog) {
                prog = document.createElement('span');
                prog.className = 'text-[10px] font-bold opacity-80';
                prog.dataset.uploadProgress = '1';
                pendingRow.querySelector('.chat-row__bubble > div:last-child')?.appendChild(prog);
            }
            prog.textContent = '';
        } else {
            const pendingPayload = {
                client_uuid: clientUuid,
                body,
                message_type: mediaKind.value || 'text',
                media_duration_ms: isVoice ? Number(durationInput.value) || null : null,
                user: { id: userId },
            };
            pendingRow = appendDmRow(log, pendingPayload, userId, {
                pending: true,
                mine: true,
                clientUuid,
                previewUrl: isVoice ? voiceObjectUrl : previewUrl,
                pendingLabel: isVoice ? i18n.voicePending : '…',
            });
        }

        sending = true;
        if (sendBtn) sendBtn.disabled = true;
        if (voiceSendBtn) voiceSendBtn.disabled = true;
        if (isVoice) {
            setStatus(i18n.voiceUploading, true);
            setProgress(0);
        }

        const t0 = performance.now();
        try {
            const { json } = await xhrSend(fd, (pct) => {
                if (isVoice) setProgress(pct);
            });
            const payload = json.message || json.data || json;
            appendDmRow(log, payload, userId, { clientUuid });
            const confirmedMs = Math.round(performance.now() - t0);
            pendingRow = log?.querySelector(`[data-message-id="${payload.id}"]`);
            if (pendingRow) pendingRow.dataset.confirmMs = String(confirmedMs);
            form.reset();
            setReply('', '', '');
            clearAttach();
            hideProgress();
            setStatus('', false);
            clientUuidInput.value = '';
            activeClientUuid = null;
            lastFailedUuid = null;
            window.__funnityLastVoiceTiming = { clientUuid, confirmedMs, messageId: payload.id };
        } catch (err) {
            lastFailedUuid = clientUuid;
            const pending = log?.querySelector(`[data-client-uuid="${clientUuid}"]`);
            if (pending) {
                pending.classList.add('is-failed');
                pending.classList.remove('is-pending');
                const meta = pending.querySelector('[data-meta]');
                if (meta) meta.textContent = i18n.voiceFailed;
                pending.querySelector('[data-upload-progress]')?.remove();
                const foot =
                    pending.querySelector('.chat-row__bubble > div:last-child') ||
                    pending.querySelector('.chat-row__bubble');
                if (foot && !pending.querySelector('[data-retry]')) {
                    const retry = document.createElement('button');
                    retry.type = 'button';
                    retry.className = 'chat-row__retry';
                    retry.dataset.retry = '1';
                    retry.textContent = i18n.retry;
                    retry.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        if (body) bodyInput.value = body;
                        // Re-seat draft bytes into the file input before retrying
                        // the same client_uuid (server idempotency).
                        if (voiceDraftFile) {
                            applyVoiceDraft(
                                voiceDraftFile,
                                Number(durationInput?.value) ||
                                    Number(payload?.media_duration_ms) ||
                                    Math.max(1, recordAccumMs) ||
                                    1
                            );
                        }
                        window.setTimeout(() => sendMessage({ reuseUuid: clientUuid }), 0);
                    });
                    foot.appendChild(retry);
                }
            }
            setStatus(err?.message || i18n.sendFailed, true);
        } finally {
            sending = false;
            if (sendBtn) sendBtn.disabled = false;
            if (voiceSendBtn) voiceSendBtn.disabled = false;
            if (previewUrl && previewUrl !== voiceObjectUrl) URL.revokeObjectURL(previewUrl);
            bodyInput?.focus();
        }
    };

    whenEchoReady((Echo) => {
        if (!channelName || !log) return;
        Echo.private(channelName).listen('.message.posted', (e) => {
            const payload = e.message || e.data || e;
            appendDmRow(log, payload, userId);
            if (payload?.id && payload?.client_uuid) {
                window.__funnityLastEchoVoice = {
                    id: payload.id,
                    client_uuid: payload.client_uuid,
                    at: Date.now(),
                    media_url: payload.media_url,
                };
            }
        });
    });

    form?.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage();
    });

    syncVoiceUi();
});
