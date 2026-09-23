import './bootstrap';
import { initLiveSearch } from './live-search';
import { initAttachmentFolders } from './attachment-folder';
import { initConfirmDialog } from './confirm-dialog';
import { initChatToasts } from './chat-toast';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;
const enabled = Boolean(key);

window.Echo = enabled
    ? new Echo({
          broadcaster: 'reverb',
          key,
          wsHost: import.meta.env.VITE_REVERB_HOST,
          wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
          wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
          forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
          enabledTransports: ['ws', 'wss'],
      })
    : null;

initLiveSearch();
document.documentElement.dataset.liveSearchReady = '1';

initAttachmentFolders();
document.documentElement.dataset.attachmentFolderReady = '1';

initConfirmDialog();
document.documentElement.dataset.confirmDialogReady = '1';

initChatToasts();
document.documentElement.dataset.chatToastReady = '1';
