import './bootstrap';
import { initLiveSearch } from './live-search';
import { initAttachmentFolders } from './attachment-folder';
import { initConfirmDialog } from './confirm-dialog';
import { initChatToasts } from './chat-toast';
import { initPageLoader } from './page-loader';
import { initDiscoveryLocationUi } from './discovery-location';

window.Echo = null;

initLiveSearch();
document.documentElement.dataset.liveSearchReady = '1';

initAttachmentFolders();
document.documentElement.dataset.attachmentFolderReady = '1';

initConfirmDialog();
document.documentElement.dataset.confirmDialogReady = '1';

initPageLoader();
document.documentElement.dataset.pageLoaderReady = '1';

initDiscoveryLocationUi();
document.documentElement.dataset.discoveryLocationReady = '1';

if (document.querySelector('[data-discover-map]')) {
    import('./discover-map')
        .then(({ initDiscoverMap }) => {
            initDiscoverMap();
            document.documentElement.dataset.discoverMapReady = '1';
        })
        .catch(() => {
            // Map is progressive enhancement.
        });
}

const needsRealtime =
    document.body?.dataset?.realtime === '1' ||
    Boolean(document.getElementById('chat-toast-host'));

if (needsRealtime) {
    import('./realtime')
        .then(({ initRealtime }) => {
            initRealtime();
            initChatToasts();
            document.documentElement.dataset.chatToastReady = '1';
        })
        .catch(() => {
            // Realtime is optional; keep page usable offline.
        });
}

const needsPlaceMaps = Boolean(
    document.querySelector('[data-event-place-picker], [data-event-place-viewer]')
);

if (needsPlaceMaps) {
    import('./event-place-map')
        .then(({ initEventPlaceMaps }) => {
            initEventPlaceMaps();
            document.documentElement.dataset.eventPlaceMapReady = '1';
        })
        .catch(() => {
            // Maps are progressive enhancement.
        });
}
