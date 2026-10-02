<div class="border-t border-gray-200 dark:border-gray-800"></div>

<div class="border-t border-gray-200 dark:border-gray-800"></div>

<footer class="bg-[#1F2937] text-white pt-16 pb-8">
    <div class="container mx-auto px-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12 mb-12">

            <div class="space-y-4">
                <a href="/" class="flex flex-col sm:flex-row items-center sm:items-center gap-4 group text-center sm:text-left">
    
    <?php if (!empty($settings['ngo_logo'])): ?>
        <img 
            src="<?php echo $settings['ngo_logo']; ?>" 
            class="h-16 w-16 sm:h-20 sm:w-20 object-contain opacity-100 transition shrink-0"
            alt="Logo"
        >
    <?php endif; ?>

    <span class="text-xl sm:text-2xl font-bold text-white leading-tight break-words max-w-full" style="font-family: 'Book Antiqua', serif;">
        <?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>
    </span>

</a>
                <p class="text-gray-300 text-sm leading-relaxed">
                    <?php echo nl2br(htmlspecialchars($settings['footer_about'] ?? '')); ?>
                </p>

                <div class="flex gap-4 pt-2">
                    <?php
                    $socials = [
                        'facebook' => ['icon' => 'fa-brands fa-facebook-f'],
                        'instagram' => ['icon' => 'fa-brands fa-instagram'],
                        'youtube' => ['icon' => 'fa-brands fa-youtube']
                    ];
                    foreach ($socials as $key => $data):
                        if (!empty($settings['social_' . $key])):
                    ?>
                            <a href="<?php echo $settings['social_' . $key]; ?>" target="_blank" class="w-12 h-12 flex items-center justify-center bg-[#1070B0] text-white rounded-full shadow-lg hover:bg-[#F0A010] transition duration-300 transform hover:scale-110">
                                <i class="<?php echo $data['icon']; ?> fa-fw text-lg"></i>
                            </a>
                    <?php endif;
                    endforeach; ?>
                </div>
            </div>

            <div>
                <h4 class="text-lg font-bold text-white mb-4 border-b-2 border-[#F0A010] pb-2 inline-block">Get Involved</h4>
                <ul class="space-y-2 text-sm text-gray-300">
                    <li><a href="/donate" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Donate Now</a></li>
                    <li><a href="/donor-history" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Donation History</a></li>
                    <li><a href="/volunteer-register" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Become a Volunteer</a></li>
                    <li><a href="/careers" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Careers & Opportunities</a></li>
                    <li><a href="/member-register" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Become a Member</a></li>
                    <li><a href="/member-verify" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Verify Member</a></li>
                    <li><a href="/volunteer-verify" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Verify Volunteer ID</a></li>
                    <li><a href="/feedback" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">Staff & Member Feedback</a></li>
                    <li><a href="/projects" class="hover:text-[#F0A010] hover:pl-1 transition-all duration-300">View All Projects</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-lg font-bold text-white mb-4 border-b-2 border-[#F0A010] pb-2 inline-block">About & Legal</h4>
                <ul class="space-y-2 text-sm text-gray-300">
                    <li><a href="/about" class="hover:text-[#F0A010] transition">About Us</a></li>
                    <li><a href="/organization-structure" class="hover:text-[#F0A010] transition">Organization Structure</a></li>
                    <li><a href="/hr-policies" class="hover:text-[#F0A010] transition">HR Policies & Ethics</a></li>
                    <li><a href="/privacy-policy" class="hover:text-[#F0A010] transition">Privacy & Policy</a></li>
                    <li><a href="/terms-conditions" class="hover:text-[#F0A010] transition">Terms & Conditions</a></li>
                    <li><a href="/refund-policy" class="hover:text-[#F0A010] transition">Refund Policy</a></li>
                    <li class="pt-2"> <span class="text-xs bg-[#1070B0] text-white px-3 py-2 rounded-lg shadow-md inline-block font-semibold"> Reg No: <?php echo htmlspecialchars($settings['reg_no'] ?? 'N/A'); ?> </span> </li>
                </ul>
            </div>

            <div>
                <h4 class="text-lg font-bold text-white mb-4 border-b-2 border-[#F0A010] pb-2 inline-block">Contact Us</h4>
                <div class="space-y-4 text-sm text-gray-300">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-[#1070B0] mt-1 fa-fw"></i>
                        <span><?php echo nl2br(htmlspecialchars($settings['ngo_address'] ?? 'Address not set.')); ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-phone text-[#1070B0] fa-fw"></i>
                        <span><?php echo htmlspecialchars($settings['ngo_phone'] ?? 'Phone not set.'); ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-[#1070B0] fa-fw"></i>
                        <span><?php echo htmlspecialchars($settings['ngo_email'] ?? 'Email not set.'); ?></span>
                    </div>
                </div>
            </div>

        </div>

        <div class="border-t border-gray-700 pt-6 flex flex-col md:flex-row justify-between items-center text-xs text-gray-400">
            <p>&copy; <?php echo date('Y'); ?> <span class="text-[#F0A010] font-bold" style="font-family: 'Book Antiqua', serif;"><?php echo strtoupper(htmlspecialchars($settings['site_name'] ?? 'NGO')); ?></span>. All rights reserved.</p>
            <p class="mt-2 md:mt-0 flex items-center gap-1">
                Designed & Developed by
                <a href="https://velnixsoft.com/" target="_blank" class="text-[#F0A010] hover:text-[#d48b0a] font-bold transition">Velnix Soft</a>
            </p>
        </div>

    </div>
</footer>

<?php if (!empty($settings['whatsapp_number'])):
    $preText = "Hello, I would like to know more about your NGO.";
    $encodedText = urlencode($preText);
?>
    <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>?text=<?php echo $encodedText; ?>" target="_blank"
        class="fixed bottom-6 right-6 z-50 bg-[#25D366] text-white w-14 h-14 rounded-full flex items-center justify-center shadow-2xl hover:bg-[#20b85c] transition transform hover:scale-110 group"
        title="Chat with us on WhatsApp">

        <span class="absolute inline-flex h-full w-full rounded-full bg-[#25D366] opacity-75 animate-ping group-hover:hidden"></span>

        <i class="fab fa-whatsapp text-3xl relative z-10"></i>

        <span class="absolute right-16 bg-gray-900 text-white text-xs font-bold px-3 py-2 rounded opacity-0 group-hover:opacity-100 transition duration-300 whitespace-nowrap pointer-events-none shadow-lg">
            Chat With Us
        </span>
    </a>
<?php endif; ?>

<?php if (($settings['enable_user_ai'] ?? '1') !== '0'): ?>
    <!-- NGO AI Informer & Guider Interactive Widget -->
    <script src="/assets/js/ai-guide.js" defer></script>
<?php endif; ?>

<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js').catch(() => {});
    });
}
</script>
</body>

</html>

