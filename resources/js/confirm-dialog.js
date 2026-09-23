/**
 * App confirm dialog — replaces window.confirm for leave/cancel/delete/etc.
 * Usage: data-confirm="Are you sure…?" on a <form> or on a submit/button.
 */
function getDialog() {
    return document.getElementById('app-confirm-dialog');
}

function openConfirm(message) {
    const dialog = getDialog();
    if (!dialog || typeof dialog.showModal !== 'function') {
        return Promise.resolve(window.confirm(message));
    }

    const messageEl = dialog.querySelector('[data-confirm-message]');
    if (messageEl) {
        messageEl.textContent = message;
    }

    return new Promise((resolve) => {
        const ok = dialog.querySelector('[data-confirm-ok]');
        const cancel = dialog.querySelector('[data-confirm-cancel]');

        const cleanup = () => {
            ok?.removeEventListener('click', onOk);
            cancel?.removeEventListener('click', onCancel);
            dialog.removeEventListener('cancel', onCancel);
            dialog.removeEventListener('click', onBackdrop);
        };

        const finish = (value) => {
            cleanup();
            if (dialog.open) {
                dialog.close();
            }
            resolve(value);
        };

        const onOk = (e) => {
            e.preventDefault();
            finish(true);
        };
        const onCancel = (e) => {
            e.preventDefault();
            finish(false);
        };
        const onBackdrop = (e) => {
            if (e.target === dialog) {
                finish(false);
            }
        };

        ok?.addEventListener('click', onOk);
        cancel?.addEventListener('click', onCancel);
        dialog.addEventListener('cancel', onCancel);
        dialog.addEventListener('click', onBackdrop);

        dialog.showModal();
    });
}

function bindConfirmTriggers(scope = document) {
    scope.querySelectorAll('[data-confirm]').forEach((el) => {
        if (el.dataset.confirmBound === '1') {
            return;
        }
        el.dataset.confirmBound = '1';

        const message = el.getAttribute('data-confirm') || '';
        if (!message) {
            return;
        }

        if (el.tagName === 'FORM') {
            el.addEventListener('submit', async (event) => {
                if (el.dataset.confirmAccepted === '1') {
                    delete el.dataset.confirmAccepted;
                    return;
                }
                event.preventDefault();
                event.stopImmediatePropagation();
                const ok = await openConfirm(message);
                if (ok) {
                    el.dataset.confirmAccepted = '1';
                    el.requestSubmit();
                }
            });
            return;
        }

        el.addEventListener('click', async (event) => {
            if (el.dataset.confirmAccepted === '1') {
                delete el.dataset.confirmAccepted;
                return;
            }
            event.preventDefault();
            event.stopImmediatePropagation();
            const ok = await openConfirm(message);
            if (!ok) {
                return;
            }
            el.dataset.confirmAccepted = '1';
            if (el.tagName === 'A' && el.href) {
                window.location.href = el.href;
                return;
            }
            el.click();
        });
    });
}

export function initConfirmDialog(scope = document) {
    bindConfirmTriggers(scope);
}

export { openConfirm };

document.addEventListener('DOMContentLoaded', () => {
    if (!document.documentElement.dataset.confirmDialogReady) {
        initConfirmDialog();
        document.documentElement.dataset.confirmDialogReady = '1';
    }
});
