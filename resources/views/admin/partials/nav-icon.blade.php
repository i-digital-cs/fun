@php
    $icons = [
        'home' => '<path stroke-linecap="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/>',
        'users' => '<path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
        'id' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="M7 10h4M7 14h10"/><circle cx="16" cy="10" r="1.5"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>',
        'flag' => '<path stroke-linecap="round" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path stroke-linecap="round" d="M4 22v-7"/>',
        'tag' => '<path stroke-linecap="round" d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><circle cx="7" cy="7" r="1.5"/>',
        'pin' => '<path stroke-linecap="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path stroke-linecap="round" d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>',
        'audit' => '<path stroke-linecap="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>',
        'megaphone' => '<path stroke-linecap="round" d="M3 11v2a1 1 0 001 1h2l6 4V6L6 10H4a1 1 0 00-1 1z"/><path stroke-linecap="round" d="M15 9.5a3.5 3.5 0 010 5"/><path stroke-linecap="round" d="M7 15v2a2 2 0 002 2"/>',
        'plug' => '<path stroke-linecap="round" d="M9 7v4M15 7v4M8 11h8v2a4 4 0 01-4 4h0a4 4 0 01-4-4v-2z"/><path stroke-linecap="round" d="M12 17v4"/>',
        'wallet' => '<rect x="2" y="6" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M2 10h20"/><circle cx="16" cy="14" r="1.5"/>',
    ];
    $path = $icons[$icon] ?? $icons['home'];
@endphp
<svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">{!! $path !!}</svg>
