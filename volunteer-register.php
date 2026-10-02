<?php require 'includes/header.php'; ?>

<div class="bg-gray-50 min-h-screen py-12 px-4"
    x-data="{ 
        isModalOpen: false, 
        loading: false, 
        formMessage: '', 
        formMessageType: 'error',
        selectedState: '',
        selectedDistrict: '',
        districts: [],
        name: '',
        email: '',
        phone: '',
        qualification: '',
        dob: '',
        nameError: '',
        emailError: '',
        phoneError: '',
        qualificationError: '',
        dobError: '',
        stateError: '',
        districtError: '',
        photoError: '',

        validateName() {
            if (!this.name.trim()) {
                this.nameError = 'Full Name is required.';
            } else if (!/^[a-zA-Z\s\'.\-]+$/.test(this.name.trim())) {
                this.nameError = 'Full Name should only contain letters, spaces, hyphens, apostrophes, and dots.';
            } else {
                this.nameError = '';
            }
        },
        validateEmail() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!this.email.trim()) {
                this.emailError = 'Email address is required.';
            } else if (!emailRegex.test(this.email.trim())) {
                this.emailError = 'Please enter a valid email address.';
            } else {
                this.emailError = '';
            }
        },
        validatePhone() {
            this.phone = this.phone.replace(/[^0-9]/g, '');
            if (this.phone.length > 10) {
                this.phone = this.phone.substring(0, 10);
            }
            if (!this.phone.trim()) {
                this.phoneError = 'Phone number is required.';
            } else if (this.phone.length !== 10) {
                this.phoneError = 'Phone number must be exactly 10 digits.';
            } else if (!/^[6-9]/.test(this.phone)) {
                this.phoneError = 'Phone number must start with 6, 7, 8, or 9.';
            } else {
                this.phoneError = '';
            }
        },
        validateQualification() {
            if (!this.qualification.trim()) {
                this.qualificationError = 'Qualification is required.';
            } else {
                this.qualificationError = '';
            }
        },
        validateDob() {
            if (!this.dob) {
                this.dobError = 'Date of Birth is required.';
            } else {
                this.dobError = '';
            }
        },
        validateState() {
            if (!this.selectedState) {
                this.stateError = 'State is required.';
            } else {
                this.stateError = '';
            }
        },
        validateDistrict() {
            if (!this.selectedDistrict) {
                this.districtError = 'District is required.';
            } else {
                this.districtError = '';
            }
        },
        stateDistrictData: {
    'Andhra Pradesh': ['Anantapur','Chittoor','East Godavari','Guntur','Kadapa','Krishna','Kurnool','Nellore','Prakasam','Srikakulam','Visakhapatnam','Vizianagaram','West Godavari'],
    'Arunachal Pradesh': ['Anjaw','Changlang','Dibang Valley','East Kameng','East Siang','Kamle','Kra Daadi','Kurung Kumey','Lepa Rada','Lohit','Longding','Lower Dibang Valley','Lower Siang','Lower Subansiri','Namsai','Pakke Kessang','Papum Pare','Shi Yomi','Siang','Tawang','Tirap','Upper Siang','Upper Subansiri','West Kameng','West Siang'],
    'Assam': ['Baksa','Barpeta','Biswanath','Bongaigaon','Cachar','Charaideo','Chirang','Darrang','Dhemaji','Dhubri','Dibrugarh','Dima Hasao','Goalpara','Golaghat','Hailakandi','Hojai','Jorhat','Kamrup','Kamrup Metropolitan','Karbi Anglong','Karimganj','Kokrajhar','Lakhimpur','Majuli','Morigaon','Nagaon','Nalbari','Sivasagar','South Salmara-Mankachar','Sonitpur','Tinsukia','Udalguri','West Karbi Anglong'],
    'Bihar': ['Araria','Arwal','Aurangabad','Banka','Begusarai','Bhagalpur','Bhojpur','Buxar','Darbhanga','East Champaran','Gaya','Gopalganj','Jamui','Jehanabad','Kaimur','Katihar','Khagaria','Kishanganj','Lakhisarai','Madhepura','Madhubani','Munger','Muzaffarpur','Nalanda','Nawada','Patna','Purnia','Rohtas','Saharsa','Samastipur','Saran','Sheikhpura','Sheohar','Sitamarhi','Siwan','Supaul','Vaishali','West Champaran'],
    'Chhattisgarh': ['Balod','Baloda Bazar','Balrampur','Bastar','Bemetara','Bijapur','Bilaspur','Dantewada','Dhamtari','Durg','Gariaband','Gaurela-Pendra-Marwahi','Janjgir-Champa','Jashpur','Kabirdham','Kanker','Kondagaon','Korba','Koriya','Mahasamund','Mungeli','Narayanpur','Raigarh','Raipur','Rajnandgaon','Sukma','Surajpur','Surguja'],
    'Goa': ['North Goa','South Goa'],
    'Gujarat': ['Ahmedabad','Amreli','Anand','Aravalli','Banaskantha','Bharuch','Bhavnagar','Botad','Chhota Udepur','Dahod','Dang','Devbhoomi Dwarka','Gandhinagar','Gir Somnath','Jamnagar','Junagadh','Kheda','Kutch','Mahisagar','Mehsana','Morbi','Narmada','Navsari','Panchmahal','Patan','Porbandar','Rajkot','Sabarkantha','Surat','Surendranagar','Tapi','Vadodara','Valsad'],
    'Haryana': ['Ambala','Bhiwani','Charkhi Dadri','Faridabad','Fatehabad','Gurugram','Hisar','Jhajjar','Jind','Kaithal','Karnal','Kurukshetra','Mahendragarh','Nuh','Palwal','Panchkula','Panipat','Rewari','Rohtak','Sirsa','Sonipat','Yamunanagar'],
    'Himachal Pradesh': ['Bilaspur','Chamba','Hamirpur','Kangra','Kinnaur','Kullu','Lahaul and Spiti','Mandi','Shimla','Sirmaur','Solan','Una'],
    'Jharkhand': ['Bokaro','Chatra','Deoghar','Dhanbad','Dumka','East Singhbhum','Garhwa','Giridih','Godda','Gumla','Hazaribagh','Jamtara','Khunti','Koderma','Latehar','Lohardaga','Pakur','Palamu','Ramgarh','Ranchi','Sahebganj','Seraikela Kharsawan','Simdega','West Singhbhum'],
    'Karnataka': ['Bagalkot','Ballari','Belagavi','Bengaluru Rural','Bengaluru Urban','Bidar','Chamarajanagar','Chikkaballapur','Chikkamagaluru','Chitradurga','Dakshina Kannada','Davanagere','Dharwad','Gadag','Hassan','Haveri','Kalaburagi','Kodagu','Kolar','Koppal','Mandya','Mysuru','Raichur','Ramanagara','Shivamogga','Tumakuru','Udupi','Uttara Kannada','Vijayapura','Yadgir'],
    'Kerala': ['Alappuzha','Ernakulam','Idukki','Kannur','Kasaragod','Kollam','Kottayam','Kozhikode','Malappuram','Palakkad','Pathanamthitta','Thiruvananthapuram','Thrissur','Wayanad'],
    'Madhya Pradesh': ['Agar Malwa','Alirajpur','Anuppur','Ashoknagar','Balaghat','Barwani','Betul','Bhind','Bhopal','Burhanpur','Chhatarpur','Chhindwara','Damoh','Datia','Dewas','Dhar','Dindori','Guna','Gwalior','Harda','Hoshangabad','Indore','Jabalpur','Jhabua','Katni','Khandwa','Khargone','Mandla','Mandsaur','Morena','Narsinghpur','Neemuch','Panna','Raisen','Rajgarh','Ratlam','Rewa','Sagar','Satna','Sehore','Seoni','Shahdol','Shajapur','Sheopur','Shivpuri','Sidhi','Singrauli','Tikamgarh','Ujjain','Umaria','Vidisha'],
    'Maharashtra': ['Ahmednagar','Akola','Amravati','Aurangabad','Beed','Bhandara','Buldhana','Chandrapur','Dhule','Gadchiroli','Gondia','Hingoli','Jalgaon','Jalna','Kolhapur','Latur','Mumbai City','Mumbai Suburban','Nagpur','Nanded','Nandurbar','Nashik','Osmanabad','Palghar','Parbhani','Pune','Raigad','Ratnagiri','Sangli','Satara','Sindhudurg','Solapur','Thane','Wardha','Washim','Yavatmal'],
    'Manipur': ['Bishnupur','Chandel','Churachandpur','Imphal East','Imphal West','Jiribam','Kakching','Kamjong','Kangpokpi','Noney','Pherzawl','Senapati','Tamenglong','Tengnoupal','Thoubal','Ukhrul'],
    'Meghalaya': ['East Garo Hills','East Jaintia Hills','East Khasi Hills','North Garo Hills','Ri Bhoi','South Garo Hills','South West Garo Hills','South West Khasi Hills','West Garo Hills','West Jaintia Hills','West Khasi Hills'],
    'Mizoram': ['Aizawl','Champhai','Hnahthial','Khawzawl','Kolasib','Lawngtlai','Lunglei','Mamit','Saiha','Saitual','Serchhip'],
    'Nagaland': ['Dimapur','Kiphire','Kohima','Longleng','Mokokchung','Mon','Noklak','Peren','Phek','Tuensang','Wokha','Zunheboto'],
    'Odisha': ['Angul','Balangir','Balasore','Bargarh','Bhadrak','Boudh','Cuttack','Deogarh','Dhenkanal','Gajapati','Ganjam','Jagatsinghpur','Jajpur','Jharsuguda','Kalahandi','Kandhamal','Kendrapara','Kendujhar','Khordha','Koraput','Malkangiri','Mayurbhanj','Nabarangpur','Nayagarh','Nuapada','Puri','Rayagada','Sambalpur','Sonepur','Sundargarh'],
    'Punjab': ['Amritsar','Barnala','Bathinda','Faridkot','Fatehgarh Sahib','Fazilka','Ferozepur','Gurdaspur','Hoshiarpur','Jalandhar','Kapurthala','Ludhiana','Mansa','Moga','Muktsar','Pathankot','Patiala','Rupnagar','Sahibzada Ajit Singh Nagar','Sangrur','Shahid Bhagat Singh Nagar','Sri Muktsar Sahib','Tarn Taran'],
    'Rajasthan': ['Ajmer','Alwar','Banswara','Baran','Barmer','Bharatpur','Bhilwara','Bikaner','Bundi','Chittorgarh','Churu','Dausa','Dholpur','Dungarpur','Hanumangarh','Jaipur','Jaisalmer','Jalore','Jhalawar','Jhunjhunu','Jodhpur','Karauli','Kota','Nagaur','Pali','Pratapgarh','Rajsamand','Sawai Madhopur','Sikar','Sirohi','Sri Ganganagar','Tonk','Udaipur'],
    'Sikkim': ['East Sikkim','North Sikkim','South Sikkim','West Sikkim'],
    'Tamil Nadu': ['Ariyalur','Chengalpattu','Chennai','Coimbatore','Cuddalore','Dharmapuri','Dindigul','Erode','Kallakurichi','Kanchipuram','Kanyakumari','Karur','Krishnagiri','Madurai','Mayiladuthurai','Nagapattinam','Namakkal','Nilgiris','Perambalur','Pudukkottai','Ramanathapuram','Ranipet','Salem','Sivaganga','Tenkasi','Thanjavur','Theni','Thoothukudi','Tiruchirappalli','Tirunelveli','Tirupathur','Tiruppur','Tiruvallur','Tiruvannamalai','Tiruvarur','Vellore','Viluppuram','Virudhunagar'],
    'Telangana': ['Adilabad','Bhadradri Kothagudem','Hyderabad','Jagtial','Jangaon','Jayashankar Bhupalpally','Jogulamba Gadwal','Kamareddy','Karimnagar','Khammam','Kumuram Bheem','Mahabubabad','Mahabubnagar','Mancherial','Medak','Medchal-Malkajgiri','Mulugu','Nagarkurnool','Nalgonda','Narayanpet','Nirmal','Nizamabad','Peddapalli','Rajanna Sircilla','Rangareddy','Sangareddy','Siddipet','Suryapet','Vikarabad','Wanaparthy','Warangal Rural','Warangal Urban','Yadadri Bhuvanagiri'],
    'Tripura': ['Dhalai','Gomati','Khowai','North Tripura','Sepahijala','South Tripura','Unakoti','West Tripura'],
    'Uttar Pradesh': ['Agra','Aligarh','Ambedkar Nagar','Amethi','Amroha','Auraiya','Ayodhya','Azamgarh','Baghpat','Bahraich','Ballia','Balrampur','Banda','Barabanki','Bareilly','Basti','Bhadohi','Bijnor','Budaun','Bulandshahr','Chandauli','Chitrakoot','Deoria','Etah','Etawah','Farrukhabad','Fatehpur','Firozabad','Gautam Buddha Nagar','Ghaziabad','Ghazipur','Gonda','Gorakhpur','Hamirpur','Hapur','Hardoi','Hathras','Jalaun','Jaunpur','Jhansi','Kannauj','Kanpur Dehat','Kanpur Nagar','Kasganj','Kaushambi','Kheri','Kushinagar','Lalitpur','Lucknow','Maharajganj','Mahoba','Mainpuri','Mathura','Mau','Meerut','Mirzapur','Moradabad','Muzaffarnagar','Pilibhit','Pratapgarh','Prayagraj','Raebareli','Rampur','Saharanpur','Sambhal','Sant Kabir Nagar','Shahjahanpur','Shamli','Shravasti','Siddharthnagar','Sitapur','Sonbhadra','Sultanpur','Unnao','Varanasi'],
    'Uttarakhand': ['Almora','Bageshwar','Chamoli','Champawat','Dehradun','Haridwar','Nainital','Pauri Garhwal','Pithoragarh','Rudraprayag','Tehri Garhwal','Udham Singh Nagar','Uttarkashi'],
    'West Bengal': ['Alipurduar','Bankura','Birbhum','Cooch Behar','Dakshin Dinajpur','Darjeeling','Hooghly','Howrah','Jalpaiguri','Jhargram','Kalimpong','Kolkata','Malda','Murshidabad','Nadia','North 24 Parganas','Paschim Bardhaman','Paschim Medinipur','Purba Bardhaman','Purba Medinipur','Purulia','South 24 Parganas','Uttar Dinajpur'],
    'Andaman and Nicobar Islands': ['Nicobar','North and Middle Andaman','South Andaman'],
    'Chandigarh': ['Chandigarh'],
    'Dadra and Nagar Haveli and Daman and Diu': ['Dadra and Nagar Haveli','Daman','Diu'],
    'Delhi': ['Central Delhi','East Delhi','New Delhi','North Delhi','North East Delhi','North West Delhi','Shahdara','South Delhi','South East Delhi','South West Delhi','West Delhi'],
    'Jammu and Kashmir': ['Anantnag','Bandipora','Baramulla','Budgam','Doda','Ganderbal','Jammu','Kathua','Kishtwar','Kulgam','Kupwara','Poonch','Pulwama','Rajouri','Ramban','Reasi','Samba','Shopian','Srinagar','Udhampur'],
    'Ladakh': ['Kargil','Leh'],
    'Lakshadweep': ['Lakshadweep'],
    'Puducherry': ['Karaikal','Mahe','Puducherry','Yanam']
},
        updateEventMeta() {
            const s = this.$refs.eventSelect;
            const dateInput = this.$refs.eventDateInput;
            const locationInput = this.$refs.eventLocationInput;
            if (!s || !dateInput || !locationInput) return;
            const selected = s.options[s.selectedIndex];
            dateInput.value = selected ? (selected.dataset.date || '') : '';
            locationInput.value = selected ? (selected.dataset.location || '') : '';
        },
        updateFee() {
            const s = this.$refs.designationSelect;
            const f = this.$refs.feeInput;
            if (!s || !f) return;
            const selected = s.options[s.selectedIndex];
            f.value = selected ? (selected.dataset.fee || '0') : '0';
        },
        updateDistricts() {
            this.districts = this.stateDistrictData[this.selectedState] || [];
            this.selectedDistrict = '';
        },
        
        submitForm(event) {
            this.validateName();
            this.validateEmail();
            this.validatePhone();
            this.validateQualification();
            this.validateDob();
            this.validateState();
            this.validateDistrict();

            // Photo validation
            this.photoError = '';
            const photoInput = event.target.querySelector('#photo');
            if (photoInput.files.length === 0) {
                this.photoError = 'Photo is required.';
            } else {
                const file = photoInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/png'];
                if (!allowedTypes.includes(file.type)) {
                    this.photoError = 'Invalid file type. Please upload a JPG or PNG image.';
                } else if (file.size > 2 * 1024 * 1024) {
                    this.photoError = 'Photo size must be under 2MB.';
                }
            }

            if (this.nameError || this.emailError || this.phoneError || this.qualificationError || this.dobError || this.stateError || this.districtError || this.photoError) {
                this.formMessage = 'Please fill in all mandatory fields with correct details.';
                this.formMessageType = 'error';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            this.loading = true;
            this.formMessage = '';
            
            const formData = new FormData(event.target);

            fetch('process/submit_volunteer.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.isModalOpen = true;
                    event.target.reset();
                    this.name = '';
                    this.email = '';
                    this.phone = '';
                    this.qualification = '';
                    this.dob = '';
                    this.selectedState = '';
                    this.selectedDistrict = '';
                } else {
                    this.formMessage = data.message;
                    this.formMessageType = 'error';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            })
            .catch(() => {
                this.formMessage = 'An unexpected error occurred.';
                this.formMessageType = 'error';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .finally(() => { this.loading = false; });
        }
     }">

    <div class="container mx-auto max-w-2xl">
        <div class="bg-white p-8 md:p-12 rounded-2xl shadow-xl border border-gray-100">

            <div class="text-center mb-10">
                <div class="w-16 h-16 bg-green-50 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-hands-helping text-3xl"></i>
                </div>
                <h1 class="text-4xl font-extrabold text-gray-800">Become a Volunteer</h1>
                <p class="text-gray-600 mt-3">Join our team of passionate individuals dedicated to making a change.</p>
            </div>

            <form @submit.prevent="submitForm($event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium mb-1.5 text-gray-700">Full Name *</label>
                        <input type="text" id="name" name="name" x-model="name" @input="validateName()" @blur="validateName()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none transition" placeholder="Enter your full name">
                        <p x-show="nameError" x-text="nameError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium mb-1.5 text-gray-700">Email Address *</label>
                        <input type="email" id="email" name="email" x-model="email" @input="validateEmail()" @blur="validateEmail()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none" placeholder="you@example.com">
                        <p x-show="emailError" x-text="emailError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium mb-1.5 text-gray-700">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" x-model="phone" @input="validatePhone()" @blur="validatePhone()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none" placeholder="10-digit mobile">
                        <p x-show="phoneError" x-text="phoneError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
                    </div>

                    <div>
                       <label for="qualification" class="block text-sm font-medium mb-1.5 text-gray-700">Qualification *</label>
                         <input type="text" id="qualification" name="qualification" x-model="qualification" @input="validateQualification()" @blur="validateQualification()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none" placeholder="Enter your qualification">
                         <p x-show="qualificationError" x-text="qualificationError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
                    </div>

                    <div>
                        <label for="profession" class="block text-sm font-medium mb-1.5 text-gray-700">Profession</label>
                        <input type="text" id="profession" name="profession" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>

                    <div>
                        <label for="marital_status" class="block text-sm font-medium mb-1.5 text-gray-700">Marital Status</label>
                        <select id="marital_status" name="marital_status" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>

                    <div>
                        <label for="blood_group" class="block text-sm font-medium mb-1.5 text-gray-700">Blood Group (Optional)</label>
                        <select id="blood_group" name="blood_group" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    
                    
                    <div>
    <label for="dob" class="block text-sm font-medium mb-1.5 text-gray-700">
        Date of Birth *
    </label>

    <input
        type="date"
        id="dob"
        name="dob"
        x-model="dob"
        @input="validateDob()"
        @blur="validateDob()"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
    <p x-show="dobError" x-text="dobError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
</div>

                    <div>
                        <label for="photo" class="block text-sm font-medium mb-1.5 text-gray-700">Your Photo *</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg, image/png" @change="photoError = ''" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        <p class="text-xs text-gray-500 mt-1">Max 2MB (JPG or PNG only)</p>
                        <p x-show="photoError" x-text="photoError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium mb-1.5 text-gray-700">Address</label>
                        <textarea id="address" name="address" rows="3" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none"></textarea>
                    </div>

<div>
    <label class="block text-sm font-medium mb-1.5 text-gray-700">State *</label>
    <select name="state" x-model="selectedState" @change="updateDistricts(); validateState()" @blur="validateState()"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none"
        x-init="$nextTick(() => {
            const stateNames = Object.keys(stateDistrictData);
            stateNames.forEach(state => {
                const opt = document.createElement('option');
                opt.value = state;
                opt.textContent = state;
                $el.appendChild(opt);
            });
        })">
        <option value="">Select State</option>
    </select>
    <p x-show="stateError" x-text="stateError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
</div>

<div>
    <label class="block text-sm font-medium mb-1.5 text-gray-700">District *</label>
    <select name="district" x-model="selectedDistrict" @change="validateDistrict()" @blur="validateDistrict()"
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none"
        x-effect="
            $el.options.length = 1;
            const dists = stateDistrictData[selectedState] || [];
            dists.forEach(dist => {
                const opt = document.createElement('option');
                opt.value = dist;
                opt.textContent = dist;
                $el.appendChild(opt);
            });
            if (dists.includes(selectedDistrict)) {
                $el.value = selectedDistrict;
            } else {
                $el.value = '';
                selectedDistrict = '';
            }
        ">
        <option value="">Select District</option>
    </select>
    <p x-show="districtError" x-text="districtError" class="text-xs text-red-500 mt-1" style="display: none;"></p>
</div>

                    <!-- <div>
                        <label for="local_body_type" class="block text-sm font-medium mb-1.5 text-gray-700">Local Body Type</label>
                        <select id="local_body_type" name="local_body_type" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                            <option value="">Select</option>
                            <option value="Panchayat">Panchayat</option>
                            <option value="Municipality">Municipality</option>
                            <option value="Corporation">Corporation</option>
                        </select>
                    </div> -->
<!-- 
                    <div>
                        <label for="local_body_name" class="block text-sm font-medium mb-1.5 text-gray-700">Panchayat / Municipality / Corporation Name</label>
                        <input type="text" id="local_body_name" name="local_body_name" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>

                    <div>
                        <label for="ward_no" class="block text-sm font-medium mb-1.5 text-gray-700">Ward No</label>
                        <input type="text" id="ward_no" name="ward_no" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>

                    <div>
                        <label for="ward_name" class="block text-sm font-medium mb-1.5 text-gray-700">Ward Name</label>
                        <input type="text" id="ward_name" name="ward_name" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>

                    <div class="md:col-span-2">
                        <label for="kudumbha_samithi" class="block text-sm font-medium mb-1.5 text-gray-700">Kudumbha Samithi</label>
                        <input type="text" id="kudumbha_samithi" name="kudumbha_samithi" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 outline-none">
                    </div>
                -->
                </div>

                <div x-show="formMessage" x-text="formMessage"
                    :class="formMessageType === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'"
                    class="mt-6 p-4 rounded-lg border text-sm"
                    x-transition>
                </div>

                <div class="mt-8">
                    <button type="submit" :disabled="loading" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-lg shadow-lg shadow-green-500/30 transition flex items-center justify-center disabled:opacity-60">
                        <span x-show="!loading">Submit Application</span>
                        <span x-show="loading">Submitting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" x-cloak>
        <div class="bg-white max-w-md w-full rounded-2xl shadow-xl p-8 text-center" @click.away="isModalOpen = false">
            <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 ring-8 ring-green-50">
                <i class="fas fa-check text-green-500 text-4xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Application Submitted!</h2>
            <p class="text-gray-600 mt-4 leading-relaxed">Thank you! Our team will review your application and notify you via email upon approval.</p>
            <div class="mt-8">
                <a href="index.php" class="inline-block w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg transition">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>
