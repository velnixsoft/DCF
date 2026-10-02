<?php
require 'includes/header.php';
?>

<div class="bg-white"
    x-data="{ 
        loading: false, 
        formMessage: '', 
        formSuccess: false,
        submitContactForm(event) {
            this.loading = true; this.formMessage = '';
            const formData = new FormData(event.target);
            fetch('process/submit_contact.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                this.formSuccess = data.success;
                this.formMessage = data.message;
                if (data.success) event.target.reset();
                setTimeout(() => {
        this.formMessage = '';
        
    }, 3000);
                 
            }).catch(() => { this.formSuccess = false; this.formMessage = 'An unexpected error occurred.'; })
            .finally(() => { this.loading = false; });
        }
     }">

    <div class="bg-[#FFF8F1] py-16 text-center border-b border-[#FEECDC]">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-[#0F8B8D]">Get in Touch</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-[#4B5563]">We are here to help and answer any question you might have.</p>
            <p class="mt-3 max-w-3xl mx-auto text-sm text-[#0F8B8D]">
                Contact JAYSMRUTTI FOUNDATION for sustainable development programs, economy and employment initiatives, education support, skill training, volunteer registration, documents, and public partnerships.
            </p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">

            <div class="lg:col-span-1 space-y-8">
                <div class="flex items-start gap-4 p-6 bg-white rounded-[18px] shadow-md border border-gray-100">
                    <div class="bg-[#F0FDFD] text-[#0F8B8D] p-3 rounded-full flex-shrink-0">
                        <i class="fas fa-map-marker-alt fa-lg fa-fw"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#1F2937]">Our Office</h3>
                        <p class="text-[#4B5563] mt-1 text-sm leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($settings['ngo_address'] ?? 'Address not available.')); ?>
                            <?php if (!empty($settings['ngo_city']) || !empty($settings['ngo_district'])): ?>
                                <br>
                                <?php 
                                $city_dist = [];
                                if (!empty($settings['ngo_city'])) $city_dist[] = htmlspecialchars($settings['ngo_city']);
                                if (!empty($settings['ngo_district'])) $city_dist[] = htmlspecialchars($settings['ngo_district']);
                                echo implode(', ', $city_dist);
                                ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-6 bg-white rounded-[18px] shadow-md border border-gray-100">
                    <div class="bg-[#F0FDFD] text-[#0F8B8D] p-3 rounded-full flex-shrink-0">
                        <i class="fas fa-envelope fa-lg fa-fw"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#1F2937]">Email Us</h3>
                        <a href="mailto:<?php echo htmlspecialchars($settings['ngo_email'] ?? ''); ?>" class="text-[#0F8B8D] hover:text-[#F4A640] hover:underline mt-1 text-sm transition">
                            <?php echo htmlspecialchars($settings['ngo_email'] ?? 'Email not available.'); ?>
                        </a>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-6 bg-white rounded-[18px] shadow-md border border-gray-100">
                    <div class="bg-[#F0FDFD] text-[#0F8B8D] p-3 rounded-full flex-shrink-0">
                        <i class="fas fa-phone fa-lg fa-fw"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-[#1F2937]">Call Us</h3>
                        <a href="tel:<?php echo htmlspecialchars($settings['ngo_phone'] ?? ''); ?>" class="text-[#4B5563] hover:text-[#F4A640] hover:underline mt-1 text-sm transition">
                            <?php echo htmlspecialchars($settings['ngo_phone'] ?? 'Phone not available.'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white p-8 rounded-[18px] shadow-xl border border-gray-100">
                <h2 class="text-2xl font-bold text-[#1F2937] mb-6">Send Us a Message</h2>
                <form @submit.prevent="submitContactForm($event)" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium mb-1.5 text-[#1F2937]">Your Name *</label>
                            <input type="text" id="name" name="name" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none transition">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium mb-1.5 text-[#1F2937]">Your Email *</label>
                            <input type="email" id="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none transition">
                        </div>
                    </div>
                    <div>
                        <label for="subject" class="block text-sm font-medium mb-1.5 text-[#1F2937]">Subject</label>
                        <input type="text" id="subject" name="subject" class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none transition">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-medium mb-1.5 text-[#1F2937]">Your Message *</label>
                        <textarea id="message" name="message" rows="4" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:ring-2 focus:ring-[#0F8B8D] focus:border-[#0F8B8D] outline-none transition"></textarea>
                    </div>

                    <div x-show="formMessage" x-cloak class="p-4 rounded-xl text-sm font-medium" :class="formSuccess ? 'bg-[#F0FDFD] text-[#0F8B8D]' : 'bg-red-50 text-red-700'">
                        <span x-text="formMessage"></span>
                    </div>

                    <button type="submit" :disabled="loading" class="bg-[#F4A640] hover:bg-[#D98E2B] text-white font-bold py-3 px-8 rounded-full shadow-lg transition duration-300 disabled:opacity-50">
                        <span x-show="!loading">Send Message</span>
                        <span x-show="loading">Sending...</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-20">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <div class="w-full h-80 md:h-96">
                    <?php if (!empty($settings['contact_map_iframe'])): ?>
                        <div class="w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0">
                            <?php echo $settings['contact_map_iframe']; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center justify-center h-full text-gray-400 bg-gray-100">Map will be shown here.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-green-800">
        <div class="container mx-auto px-4 py-16 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
                Ready to Make a Difference?
            </h2>
            <p class="mt-4 max-w-2xl mx-auto text-lg text-green-100">
                Your support can turn our vision into reality. Join us in our mission to create a better world.
            </p>

            <div class="mt-8 flex flex-row items-center justify-center gap-3">

                <a href="donate.php"
                    class="inline-flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base w-auto">
                    <i class="fas fa-heart mr-2"></i>
                    <span>Donate Now</span>
                </a>

                <a href="volunteer-register.php"
                    class="inline-flex items-center justify-center bg-white hover:bg-gray-100 text-green-800 font-bold py-3 px-5 md:px-8 rounded-full shadow-lg transition transform hover:scale-105 active:scale-95 text-sm md:text-base w-auto whitespace-nowrap">
                    <span>Join as Volunteer</span>
                </a>

            </div>
        </div>
    </div>

</div>

<?php require 'includes/footer.php'; ?>
