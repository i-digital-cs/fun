/**
 * Folder attachment UI — click folder opens native picker; selected media is visible.
 */
function formatBytes(bytes) {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '';
    }
    if (bytes < 1024) {
        return `${bytes} B`;
    }
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function fileExt(name) {
    const parts = String(name || '').split('.');
    return parts.length > 1 ? parts.pop().toUpperCase() : 'FILE';
}

function isImageFile(file) {
    return Boolean(file?.type?.startsWith('image/'));
}

function revokePreviews(root) {
    (root._attachmentObjectUrls || []).forEach((url) => URL.revokeObjectURL(url));
    root._attachmentObjectUrls = [];
}

function updateStatus(root, fileInfos) {
    const status = root.querySelector('[data-attachment-status]');
    if (!status) {
        return;
    }
    if (fileInfos.length === 0) {
        status.hidden = true;
        status.textContent = '';
        root.classList.remove('has-files');
        return;
    }
    root.classList.add('has-files');
    status.hidden = false;
    if (fileInfos.length === 1) {
        const info = fileInfos[0];
        const meta = [fileExt(info.name), formatBytes(info.size)].filter(Boolean).join(' · ');
        status.textContent = meta ? `${info.name} (${meta})` : info.name;
    } else {
        status.textContent = (root.dataset.selectedLabel || ':count selected').replace(
            ':count',
            String(fileInfos.length)
        );
    }
}

function renderFileCards(root, fileInfos) {
    const host = root.querySelector('[data-attachment-files]');
    const countEl = root.querySelector('[data-attachment-count]');
    const preview = root.querySelector('[data-attachment-preview]');
    if (!host) {
        return;
    }

    revokePreviews(root);
    const palette = ['file-1', 'file-2', 'file-3', 'file-4', 'file-5'];
    const max = 5;
    const slice = fileInfos.slice(0, max);
    const addLabel = root.dataset.addLabel || 'Add file';
    const dropLabel = root.dataset.dropLabel || 'Choose';

    if (slice.length === 0) {
        host.innerHTML = `
            <div class="file file-1 attachment-folder__placeholder" data-attachment-placeholder>
                <div class="shine"></div>
                <svg class="file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                <div class="file-text">${escapeHtml(addLabel)}</div>
                <div class="file-tag">${escapeHtml(dropLabel)}</div>
            </div>`;
        if (preview) {
            preview.hidden = true;
            preview.removeAttribute('src');
        }
    } else {
        host.innerHTML = slice
            .map((info, index) => {
                const tag = [fileExt(info.name), formatBytes(info.size)].filter(Boolean).join(' · ');
                const thumb = info.previewUrl
                    ? `<img class="file-thumb" src="${escapeAttr(info.previewUrl)}" alt="">`
                    : '';
                return `
                <div class="file ${palette[index % 5]}${info.previewUrl ? ' file--image' : ''}" data-attachment-file data-name="${escapeAttr(info.name)}">
                    ${thumb}
                    <div class="shine"></div>
                    <svg class="file-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <div class="file-text">${escapeHtml(truncate(info.name, 18))}</div>
                    ${tag ? `<div class="file-tag">${escapeHtml(tag)}</div>` : ''}
                </div>`;
            })
            .join('');

        const firstImage = slice.find((f) => f.previewUrl);
        if (preview) {
            if (firstImage) {
                preview.src = firstImage.previewUrl;
                preview.hidden = false;
            } else {
                preview.hidden = true;
                preview.removeAttribute('src');
            }
        }
    }

    if (countEl) {
        countEl.textContent = String(slice.length).padStart(2, '0');
    }
    updateStatus(root, fileInfos);
}

function truncate(value, max) {
    const s = String(value ?? '');
    return s.length > max ? `${s.slice(0, max - 1)}…` : s;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
}

function bindFolder(root) {
    if (root.dataset.mode !== 'upload') {
        bindFilter(root);
        return;
    }

    const input = root.querySelector('[data-attachment-input]');
    const browse = root.querySelector('[data-attachment-browse]');
    const toggle = root.querySelector('.folder-toggle');
    const card = root.querySelector('.folder-card');
    if (!input) {
        return;
    }

    // Label is wired to the file input (native picker). Keep folder visually open on interact.
    const openFolder = () => {
        if (toggle) {
            toggle.checked = true;
        }
    };

    card?.addEventListener('pointerdown', openFolder);
    card?.addEventListener('focus', openFolder);

    browse?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        openFolder();
        input.click();
    });

    input.addEventListener('change', () => {
        const files = Array.from(input.files || []);
        revokePreviews(root);
        const infos = files.map((file) => {
            const info = { name: file.name, size: file.size, previewUrl: null };
            if (isImageFile(file)) {
                info.previewUrl = URL.createObjectURL(file);
                root._attachmentObjectUrls = root._attachmentObjectUrls || [];
                root._attachmentObjectUrls.push(info.previewUrl);
            }
            return info;
        });
        renderFileCards(root, infos);
        openFolder();
    });

    bindFilter(root);
}

function bindFilter(root) {
    const filter = root.querySelector('[data-attachment-filter]');
    const search = root.querySelector('.folder-search');
    if (!filter) {
        return;
    }
    const blockPicker = (e) => {
        e.preventDefault();
        e.stopPropagation();
    };
    search?.addEventListener('click', blockPicker);
    search?.addEventListener('pointerdown', blockPicker);
    filter.addEventListener('input', () => {
        const q = filter.value.trim().toLowerCase();
        root.querySelectorAll('[data-attachment-file]').forEach((el) => {
            const name = (el.getAttribute('data-name') || '').toLowerCase();
            el.style.display = !q || name.includes(q) ? '' : 'none';
        });
    });
    filter.addEventListener('keydown', (e) => e.stopPropagation());
}

export function initAttachmentFolders(scope = document) {
    scope.querySelectorAll('[data-attachment-folder]').forEach(bindFolder);
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.documentElement.dataset.attachmentFolderReady) {
        initAttachmentFolders();
        document.documentElement.dataset.attachmentFolderReady = '1';
    }
});
