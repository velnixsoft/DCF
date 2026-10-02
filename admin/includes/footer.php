<div class="h-20 md:h-8"></div>

<footer class="fixed bottom-0 left-0 md:left-64 right-0 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700 px-6 py-3 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 dark:text-gray-400 z-10 transition-all duration-300">

    <div class="mb-1 md:mb-0 text-center md:text-left">
        &copy; <?php echo date('Y'); ?>
        <span class="font-bold text-gray-700 dark:text-gray-300">
            <?php echo $settings['site_name'] ?? 'NGO System'; ?>
        </span>.
        All rights reserved.
    </div>

    <div class="flex items-center gap-1">
        <span>Designed & Developed by</span>
        <a href="#" target="_blank" class="text-[#1070B0] hover:text-[#F0A010] font-medium flex items-center gap-1 transition">
            Your Name
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
        </a>
    </div>
</footer>

<div x-data="{ show: false }"
    @open-logout-modal.window="show = true"
    x-show="show"
    class="fixed inset-0 z-[80] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm"
    x-cloak>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6 transform transition-all border border-gray-100 dark:border-gray-700"
        @click.away="show = false"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">

        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-50 dark:bg-red-900/20 mb-4 ring-8 ring-red-50/50 dark:ring-red-900/10">
                <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Confirm Logout</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Are you sure you want to Lgout?
            </p>
        </div>

        <div class="mt-6 flex gap-3">
            <button @click="show = false" class="flex-1 justify-center rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2.5 bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none transition">
                Cancel
            </button>
            <a href="logout.php" class="flex-1 inline-flex justify-center items-center rounded-lg border border-transparent px-4 py-2.5 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none shadow-lg shadow-red-500/30 transition">
                Logout Now
            </a>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['flash'])): ?>
    <script>
        document.addEventListener('alpine:initialized', () => {
            const flashData = <?php echo json_encode($_SESSION['flash']); ?>;
            Alpine.store('toast').show(flashData.message, flashData.type);
        });
    </script>
<?php unset($_SESSION['flash']);
endif; ?>

</body>

</html>