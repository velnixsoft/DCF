<header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-dark-card border-b dark:border-gray-700 sticky top-0 z-20">

    <div class="flex items-center">

        <button @click="$store.sidebar.toggle()" class="text-gray-500 focus:outline-none md:hidden">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>

    </div>

    <div class="flex items-center gap-3">

        <!-- AI Copilot Quick Button -->
        <a href="/admin/ai-assistant" 
            class="hidden sm:inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-gradient-to-r from-amber-500/10 via-sky-500/10 to-amber-500/10 hover:from-amber-500/20 hover:to-sky-500/20 text-[#1070B0] dark:text-sky-400 font-semibold text-xs border border-amber-300/40 dark:border-sky-500/30 shadow-xs hover:shadow transition transform active:scale-95 group"
            title="Open Admin AI Copilot (Instant operational insights)">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
            </span>
            <i class="fa-solid fa-wand-magic-sparkles text-amber-500 group-hover:rotate-12 transition-transform"></i>
            <span>AI Copilot</span>
            <span class="text-[10px] bg-white dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-500 dark:text-gray-300 font-mono hidden md:inline">Ctrl+K</span>
        </a>

        <button @click="$store.theme.toggle()" class="p-2 rounded-full text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition">

            <svg x-show="$store.theme.isDark" class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>

            <svg x-show="!$store.theme.isDark" class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>

        <div x-data="{ dropdownOpen: false }" class="relative">
            <button @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-2 focus:outline-none group">
                <div class="w-9 h-9 overflow-hidden border-2 border-gray-200 dark:border-gray-600 rounded-full transition group-hover:border-[#1070B0]">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name'] ?? 'Admin'); ?>&background=1070b0&color=fff" class="object-cover w-full h-full" alt="avatar">
                </div>
                <div class="hidden md:block text-left">
                    <p class="text-sm font-semibold text-[#1F2937] dark:text-gray-200 leading-tight"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></p>
                    <p class="text-xs text-[#4B5563] dark:text-gray-400 leading-tight"><?php echo $_SESSION['user_role'] ?? 'Administrator'; ?></p>
                </div>
                <svg class="w-4 h-4 text-gray-400 hidden md:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="dropdownOpen" @click.away="dropdownOpen = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-dark-card rounded-xl shadow-xl z-50 py-2 border border-gray-100 dark:border-gray-700"
                x-cloak>

                <div class="px-4 py-2 border-b dark:border-gray-700 md:hidden">
                    <span class="block text-sm font-bold text-[#1F2937] dark:text-white"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></span>
                    <span class="block text-xs text-[#4B5563]"><?php echo $_SESSION['user_role'] ?? 'Administrator'; ?></span>
                </div>

                <a href="settings.php" class="block px-4 py-2 text-sm text-[#1F2937] dark:text-gray-300 hover:bg-[#fffcf5] dark:hover:bg-gray-700 hover:text-[#1070B0] transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </a>
                <hr class="border-gray-100 dark:border-gray-700 my-1">

                <button @click="dropdownOpen = false; $dispatch('open-logout-modal')" class="w-full text-left block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Logout
                </button>
            </div>
        </div>
    </div>
</header>