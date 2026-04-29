@php
    $user = auth()->user();
    $name = $user ? $user->name : 'Administrator';
    
    // Attempt to get a role name or default to Administrator
    $role = 'Administrator';
    if ($user && method_exists($user, 'getRoleNames') && $user->getRoleNames()->count() > 0) {
        $role = $user->getRoleNames()->first();
    } elseif ($user && isset($user->role)) {
        $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : (string) $user->role;
    }
    
    // Create initials (e.g. Admin Geprekin -> AG)
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $w) {
        if (!empty($w)) $initials .= mb_substr($w, 0, 1);
        if (strlen($initials) >= 2) break;
    }
    $initials = strtoupper($initials);
@endphp

<div class="px-5 py-4 custom-sidebar-footer border-t border-gray-100 bg-white">
    <!-- User Info Card -->
    <div class="flex items-center p-3.5 mb-3 bg-[#f3f6fc] dark:bg-[#1e293b] rounded-[18px]">
        <div class="flex-shrink-0 flex items-center justify-center w-[48px] h-[48px] rounded-full font-bold text-[#1e3a8a] dark:text-[#bfdbfe] bg-[#dee8ff] dark:bg-[#1e3a8a] text-[18px]">
            {{ $initials }}
        </div>
        <div class="ml-3 overflow-hidden">
            <p class="text-[16px] font-bold text-[#0f172a] dark:text-white truncate" style="font-family: ui-serif, Georgia, serif;">{{ $name }}</p>
            @if(!str_contains(strtolower($role), 'admin'))
            <p class="text-[13px] text-gray-400 dark:text-[#94a3b8] truncate capitalize">{{ str_replace(['_', '.'], ' ', $role) }}</p>
            @endif
        </div>
    </div>

    <!-- Logout Button -->
    <form method="POST" action="{{ filter_var(route('filament.admin.auth.logout'), FILTER_SANITIZE_URL) }}">
        @csrf
        <button type="submit" class="w-full flex items-center justify-center gap-3 p-3.5 text-[16px] font-bold rounded-[18px] text-[#ef4444] bg-[#fff5f5] dark:bg-[#451010] border border-transparent transition-all duration-300 hover:bg-[#ffebeb] dark:hover:bg-[#7f1d1d]">
            <span>Keluar</span>
            <svg class="w-[20px] h-[20px] text-[#ef4444] dark:text-[#f87171]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l3 3m0 0l-3 3m3-3H2.25" />
            </svg>
        </button>
    </form>
</div>
