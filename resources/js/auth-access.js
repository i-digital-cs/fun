/**
 * Shared cinematic auth sequence for login / register / verify / reset.
 * Root: [data-auth-access]
 */
function initAuthAccess(root) {
    const i18n = JSON.parse(root.querySelector('[data-auth-i18n]')?.textContent || '{}');
    const mode = root.dataset.mode || 'form';
    const digitSource = root.dataset.digitSource || 'password';
    const form = root.querySelector('[data-stage="form"]');
    const theater = root.querySelector('[data-stage="theater"]');
    const titleEl = root.querySelector('[data-auth-title]');
    const subEl = root.querySelector('[data-auth-sub]');
    const errorEl = root.querySelector('[data-auth-error]');
    const digitsWrap = root.querySelector('[data-digits]');
    const digitEls = [...root.querySelectorAll('[data-digit]')];
    const verifyEl = root.querySelector('[data-verify]');
    const ringEl = root.querySelector('.login-access__ring');
    const countEl = root.querySelector('[data-count]');
    const ringProgress = root.querySelector('[data-ring-progress]');
    const successEl = root.querySelector('[data-success]');
    const confettiEl = root.querySelector('[data-confetti]');
    const footerForm = root.querySelector('[data-footer-form]');
    const footerTheater = root.querySelector('[data-footer-theater]');
    const resendBtn = root.querySelector('[data-auth-resend]');
    const resendCooldownEl = root.querySelector('[data-resend-cooldown]');
    const otpInputs = [...root.querySelectorAll('[data-otp-input]')];
    const otpHidden = root.querySelector('[data-otp-value]');

    const RING_LEN = 2 * Math.PI * 52;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let busy = false;
    let abortVerify = false;
    let resendSecondsLeft = Math.max(0, parseInt(root.dataset.retryAfter || '0', 10) || 0);
    let resendTimerId = null;
    let resendBusy = false;

    if (titleEl) titleEl.dataset.default = titleEl.textContent.trim();
    if (subEl) subEl.dataset.default = subEl.textContent.trim();

    if (ringProgress) {
        ringProgress.style.strokeDasharray = String(RING_LEN);
        ringProgress.style.strokeDashoffset = String(RING_LEN);
    }

    const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

    function setCopy(title, sub) {
        if (titleEl) titleEl.textContent = title;
        if (subEl) subEl.textContent = sub || '';
    }

    function showError(message) {
        if (!errorEl) return;
        errorEl.textContent = message || '';
        errorEl.classList.toggle('hidden', !message);
    }

    function readOtp() {
        return otpInputs.map((el) => el.value.replace(/\D/g, '')).join('').slice(0, 4);
    }

    function syncOtpHidden() {
        if (otpHidden) otpHidden.value = readOtp();
    }

    function digitChars() {
        if (digitSource === 'code') {
            const code = readOtp() || otpHidden?.value || '';
            return code.padEnd(4, ' ').slice(0, 4).split('').map((c) => (c === ' ' ? '' : c));
        }

        if (digitSource === 'email') {
            const email = form?.querySelector('[name="email"]')?.value || '';
            const local = email.split('@')[0] || '';
            return local.slice(0, 4).padEnd(4, '•').slice(0, 4).split('');
        }

        if (digitSource === 'none') {
            return ['•', '•', '•', '•'];
        }

        const password = form?.querySelector('[name="password"]')?.value || '';
        const chars = password.slice(0, 4).split('');
        while (chars.length < 4) chars.push('');
        return chars;
    }

    function fillDigits() {
        const chars = digitChars();
        digitEls.forEach((el, i) => {
            const ch = chars[i] ?? '';
            el.textContent = ch;
            el.classList.toggle('is-empty', ch === '');
        });
    }

    function setStage(stage) {
        root.dataset.stage = stage;
        digitsWrap?.classList.toggle('is-orbiting', stage === 'orbit');
        digitsWrap?.classList.toggle('is-hidden', stage === 'verify' || stage === 'success');
        verifyEl?.classList.toggle('is-visible', stage === 'verify');
        ringEl?.classList.toggle('is-spinning', stage === 'verify');
        successEl?.classList.toggle('is-visible', stage === 'success');
    }

    function formatMmSs(totalSeconds) {
        const s = Math.max(0, totalSeconds | 0);
        const mm = String(Math.floor(s / 60)).padStart(2, '0');
        const ss = String(s % 60).padStart(2, '0');
        return `${mm}:${ss}`;
    }

    function paintResendUi() {
        if (!resendBtn) return;
        const cooling = resendSecondsLeft > 0 || resendBusy;
        resendBtn.disabled = cooling;
        resendBtn.classList.toggle('opacity-60', cooling);
        if (resendSecondsLeft > 0) {
            const label = (i18n.resend_in || 'Resend code in :time').replace(
                ':time',
                formatMmSs(resendSecondsLeft),
            );
            resendBtn.textContent = label;
            if (resendCooldownEl) {
                resendCooldownEl.textContent = label;
                resendCooldownEl.classList.remove('hidden');
            }
        } else {
            resendBtn.textContent = i18n.resend || 'Resend code';
            resendCooldownEl?.classList.add('hidden');
        }
    }

    function startResendCooldown(seconds) {
        resendSecondsLeft = Math.max(0, Math.ceil(Number(seconds) || 0));
        if (resendTimerId) {
            clearInterval(resendTimerId);
            resendTimerId = null;
        }
        paintResendUi();
        if (resendSecondsLeft <= 0) return;
        resendTimerId = window.setInterval(() => {
            resendSecondsLeft = Math.max(0, resendSecondsLeft - 1);
            paintResendUi();
            if (resendSecondsLeft <= 0 && resendTimerId) {
                clearInterval(resendTimerId);
                resendTimerId = null;
            }
        }, 1000);
    }

    function showTheater(show) {
        form?.classList.toggle('hidden', show);
        theater?.classList.toggle('hidden', !show);
        footerForm?.classList.toggle('hidden', show);
        // Identity cancel lives in data-footer-theater; OTP resend uses
        // data-footer-resend and stays visible on the form stage.
        footerTheater?.classList.toggle('hidden', !show);
    }

    function restoreForm() {
        setStage('enter');
        if (countEl) countEl.textContent = '0';
        if (ringProgress) ringProgress.style.strokeDashoffset = String(RING_LEN);
        confettiEl?.replaceChildren();
        showTheater(false);
        setCopy(titleEl?.dataset.default || '', subEl?.dataset.default || '');
        busy = false;
        abortVerify = false;
    }

    function spawnConfetti() {
        if (!confettiEl) return;
        confettiEl.replaceChildren();
        const colors = ['#fbbf24', '#38bdf8', '#f472b6', '#34d399', '#a78bfa', '#fb923c'];
        for (let i = 0; i < 28; i += 1) {
            const piece = document.createElement('span');
            piece.className = 'login-access__piece';
            piece.style.setProperty('--x', `${(Math.random() * 160 - 80).toFixed(1)}px`);
            piece.style.setProperty('--y', `${(Math.random() * 140 - 90).toFixed(1)}px`);
            piece.style.setProperty('--r', `${(Math.random() * 360).toFixed(0)}deg`);
            piece.style.setProperty('--d', `${(0.35 + Math.random() * 0.55).toFixed(2)}s`);
            piece.style.background = colors[i % colors.length];
            piece.style.borderRadius = Math.random() > 0.5 ? '999px' : '3px';
            confettiEl.appendChild(piece);
        }
    }

    async function runCounter(durationMs) {
        const start = performance.now();
        let value = 0;
        while (value < 100 && !abortVerify) {
            const t = Math.min(1, (performance.now() - start) / durationMs);
            value = Math.round(t * 100);
            if (countEl) countEl.textContent = String(value);
            if (ringProgress) ringProgress.style.strokeDashoffset = String(RING_LEN * (1 - value / 100));
            if (value >= 100) break;
            await sleep(16);
        }
        if (!abortVerify) {
            if (countEl) countEl.textContent = '100';
            if (ringProgress) ringProgress.style.strokeDashoffset = '0';
        }
    }

    async function postForm() {
        syncOtpHidden();
        const body = new FormData(form);
        const response = await fetch(root.dataset.actionUrl, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': root.dataset.csrf,
            },
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message =
                payload.message ||
                Object.values(payload.errors || {}).flat()[0] ||
                'Request failed.';
            throw new Error(message);
        }

        return payload;
    }

    async function playSequence() {
        showError('');
        fillDigits();
        showTheater(true);
        setCopy(i18n.enter_title, i18n.enter_sub);
        setStage('enter');

        const runAuth = async () => {
            const payload = await postForm();
            setCopy(i18n.granted_title, i18n.granted_sub);
            setStage('success');
            spawnConfetti();
            await sleep(reduceMotion ? 500 : 1400);
            window.location.href = payload.redirect || '/';
        };

        if (reduceMotion) {
            setCopy(i18n.verifying_title, i18n.verifying_sub);
            setStage('verify');
            try {
                await runAuth();
            } catch (err) {
                restoreForm();
                showError(err.message);
            }
            return;
        }

        await sleep(450);
        if (abortVerify) return;
        setStage('orbit');
        await sleep(1600);
        if (abortVerify) return;

        setCopy(i18n.verifying_title, i18n.verifying_sub);
        setStage('verify');

        let payload = null;
        let authError = null;
        const authPromise = postForm()
            .then((p) => {
                payload = p;
            })
            .catch((err) => {
                authError = err;
            });

        await Promise.all([runCounter(2200), authPromise]);
        if (abortVerify) {
            busy = false;
            return;
        }

        if (authError || !payload) {
            root.classList.add('is-shake');
            await sleep(420);
            root.classList.remove('is-shake');
            restoreForm();
            showError(authError?.message || 'Request failed.');
            return;
        }

        setCopy(i18n.granted_title, i18n.granted_sub);
        setStage('success');
        spawnConfetti();
        await sleep(1400);
        window.location.href = payload.redirect || window.location.href;
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        if (busy) return;

        if (mode === 'otp') {
            syncOtpHidden();
            if (readOtp().length !== 4) {
                showError(i18n.enter_sub || 'Enter the 4-digit code.');
                return;
            }
        } else if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        busy = true;
        abortVerify = false;
        playSequence();
    });

    otpInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            input.classList.toggle('is-empty', input.value === '');
            syncOtpHidden();
            if (input.value && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            if (readOtp().length === 4 && !busy) {
                form?.requestSubmit();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Backspace' && !input.value && index > 0) {
                otpInputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', (event) => {
            const text = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 4);
            if (!text) return;
            event.preventDefault();
            text.split('').forEach((ch, i) => {
                if (otpInputs[i]) {
                    otpInputs[i].value = ch;
                    otpInputs[i].classList.remove('is-empty');
                }
            });
            syncOtpHidden();
            const focusAt = Math.min(text.length, otpInputs.length - 1);
            otpInputs[focusAt]?.focus();
            if (text.length === 4 && !busy) form?.requestSubmit();
        });
    });

    resendBtn?.addEventListener('click', async () => {
        if (root.dataset.stage === 'success') return;
        if (resendBusy || resendSecondsLeft > 0) return;

        const resendUrl = root.dataset.resendUrl;
        if (!resendUrl) {
            abortVerify = true;
            restoreForm();
            showError('');
            return;
        }

        resendBusy = true;
        paintResendUi();

        try {
            const response = await fetch(resendUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': root.dataset.csrf,
                    'Content-Type': 'application/json',
                },
                body: '{}',
            });
            const payload = await response.json().catch(() => ({}));
            const retryAfter = Number(
                payload.retry_after_seconds ??
                    response.headers.get('Retry-After') ??
                    0,
            );

            if (response.status === 429) {
                startResendCooldown(retryAfter || root.dataset.retryAfter || 60);
                throw new Error(payload.message || i18n.resend || 'Please wait');
            }

            if (!response.ok) {
                // Delivery failure (e.g. 503): keep Resend actionable.
                if (retryAfter > 0) startResendCooldown(retryAfter);
                throw new Error(payload.message || 'Resend failed');
            }

            abortVerify = true;
            restoreForm();
            showError('');
            if (titleEl) {
                setCopy(titleEl.dataset.default || '', payload.message || i18n.resend_ok);
            }
            startResendCooldown(retryAfter > 0 ? retryAfter : (Number(root.dataset.retryAfter) || 60));
        } catch (err) {
            showError(err.message);
        } finally {
            resendBusy = false;
            paintResendUi();
        }
    });

    startResendCooldown(resendSecondsLeft);
}

document.querySelectorAll('[data-auth-access]').forEach(initAuthAccess);
