<?php require 'includes/header.php'; ?>

<div class="bg-white min-h-screen py-16 px-4"
    x-data="{
        state: 'enter_email',
        loading: false,
        email: '',
        otp: '',
        volunteerData: null,
        message: '',
        messageType: 'error', // success or error
        
        sendOtp() {
            if(!this.email) return;
            this.loading = true; this.message = '';
            fetch('process/send_volunteer_otp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ email: this.email })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.otp_sent) {
                    this.state = 'enter_otp';
                    this.message = data.message;
                    this.messageType = 'success';
                } else if (data.success && !data.otp_sent) {
                    this.message = data.message;
                    this.messageType = 'success';
                    this.state = 'enter_email';
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            }).finally(() => this.loading = false);
        },

        verifyOtp() {
            if(this.otp.length !== 6) return;
            this.loading = true; this.message = '';
            fetch('process/verify_volunteer_otp.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ email: this.email, otp: this.otp })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    this.volunteerData = data.volunteer;
                    this.state = 'show_details';
                } else {
                    this.message = data.message;
                    this.messageType = 'error';
                }
            }).finally(() => this.loading = false);
        }
     }">

    <div class="container mx-auto max-w-lg">

        <div x-show="state === 'enter_email'" x-transition.opacity>
            <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl border border-gray-100 text-center transform transition hover:scale-[1.01]">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 ring-8 ring-green-50">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-800">Volunteer Verification</h1>
                <p class="text-gray-600 mt-2">Enter your registered email to receive a secure OTP.</p>

                <form @submit.prevent="sendOtp" class="mt-8">
                    <input type="email" x-model="email" required placeholder="your.email@example.com" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none transition text-center text-lg">
                    <button type="submit" :disabled="loading" class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-green-500/30 transition flex items-center justify-center disabled:opacity-60">
                        <span x-show="!loading">Send OTP</span>
                        <span x-show="loading" class="flex items-center gap-2"><svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg> Sending...</span>
                    </button>
                    <p x-show="message" x-text="message" class="text-sm mt-3 font-medium" :class="messageType === 'error' ? 'text-red-500' : 'text-green-600'"></p>
                </form>
            </div>
        </div>

        <div x-show="state === 'enter_otp'" x-transition.opacity x-cloak>
            <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl border border-gray-100 text-center">
                <h1 class="text-3xl font-bold text-gray-800">Enter OTP</h1>
                <p class="text-sm text-gray-500 mt-2">We sent a 6-digit code to <b x-text="email" class="text-gray-800"></b></p>

                <form @submit.prevent="verifyOtp" class="mt-8">
                    <input type="text" x-model="otp" required pattern="\d{6}" maxlength="6" placeholder="_ _ _ _ _ _" class="w-full text-center tracking-[15px] text-3xl font-bold px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none transition">
                    <button type="submit" :disabled="loading" class="w-full mt-6 bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-amber-500/30 transition flex items-center justify-center disabled:opacity-60">
                        <span x-show="!loading">Verify Identity</span>
                        <span x-show="loading">Verifying...</span>
                    </button>
                    <p x-show="message" x-text="message" class="text-sm mt-3 text-red-500 font-medium"></p>
                    <button type="button" @click="state='enter_email'; message='';" class="text-xs text-gray-500 mt-6 hover:text-green-600 hover:underline">Change Email or Resend</button>
                </form>
            </div>
        </div>

        <div x-show="state === 'show_details'" x-transition.opacity x-cloak>
            <template x-if="volunteerData">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden transform transition hover:shadow-2xl">

                    <div class="p-6 bg-gradient-to-r from-green-600 to-teal-600 text-white text-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-full bg-white/10 transform -skew-y-6 origin-top-left"></div>
                        <div class="relative z-10">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-3 backdrop-blur-sm border-2 border-white/30">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold">Identity Verified</h2>
                            <p class="text-green-100 text-sm py-2">Official Volunteer Record</p>
                        </div>
                    </div>

                    <div class="p-8">
                        <div class="flex flex-col items-center gap-4 mb-8">
                            <img :src="volunteerData.photo ? volunteerData.photo : 'https://ui-avatars.com/api/?name=' + volunteerData.name + '&background=random'"
                                class="w-28 h-28 rounded-full object-cover border-4 border-white shadow-lg -mt-16 relative z-20 bg-gray-100">
                            <div class="text-center">
                                <h3 class="text-2xl font-bold text-gray-800" x-text="volunteerData.name"></h3>
                                <p class="text-green-600 font-bold font-mono tracking-wide bg-green-50 px-3 py-1 rounded-full text-sm inline-block mt-1" x-text="volunteerData.id_card_no"></p>
                            </div>
                        </div>

                        <div class="space-y-4 text-sm border-t border-gray-100 pt-6">
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-gray-500">Current Status</span>
                                <span class="font-bold px-3 py-1 rounded-full text-xs uppercase tracking-wide"
                                    :class="{
                                        'bg-green-100 text-green-700': volunteerData.status === 'Active',
                                        'bg-red-100 text-red-700': volunteerData.status !== 'Active'
                                      }"
                                    x-text="volunteerData.status">
                                </span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-gray-500">Valid Until</span>
                                <span class="font-medium text-gray-800" x-text="new Date(volunteerData.valid_until).toLocaleDateString('en-GB')"></span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-50">
                                <span class="text-gray-500">Email</span>
                                <span class="font-medium text-gray-800" x-text="volunteerData.email"></span>
                            </div>
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-500">Phone</span>
                                <span class="font-medium text-gray-800" x-text="volunteerData.phone"></span>
                            </div>
                        </div>

                        <div class="mt-8 text-center bg-amber-50 border border-amber-100 p-4 rounded-xl">
                            <p class="text-sm text-amber-800 leading-relaxed">
                                <i class="fas fa-info-circle mr-1"></i>
                                This is a digital verification record. To collect your physical ID card, please contact
                                <strong class="text-amber-900"><?php echo htmlspecialchars($settings['site_name'] ?? 'Administration'); ?></strong>.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 border-t text-center">
                        <a href="volunteer-verify.php" class="text-sm text-green-600 hover:text-green-800 font-medium hover:underline transition">Verify Another ID</a>
                    </div>
                </div>
            </template>
        </div>

    </div>
</div>

<?php require 'includes/footer.php'; ?>
