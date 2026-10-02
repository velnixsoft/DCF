<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/member_module.php';

$designations = $pdo->query("SELECT id, title, fee_amount FROM member_designations WHERE is_active = 1 ORDER BY fee_amount ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$banks = $pdo->query("SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$qrs = $pdo->query("SELECT * FROM payment_qrs WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$events = [];
try {
    $events = $pdo->query("SELECT id, title, event_date, location, status FROM events WHERE status <> 'Cancelled' ORDER BY event_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $events = [];
}
$refCode = isset($_GET['ref']) ? trim($_GET['ref']) : '';
$donationRefCode = isset($_GET['mref']) ? trim($_GET['mref']) : '';
?>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="bg-gray-50 min-h-screen py-12 px-4"
    x-data="{
        loading: false,
        message: '',
        messageType: 'error',
        showSuccess: false,
        paymentMode: 'manual',
        razorpay: { order_id: '', payment_id: '', signature: '' },
        selectedState: '',
        selectedDistrict: '',
        districts: [],
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
        async submitMemberForm(event) {
            const form = event.target;
            this.loading = true;
            this.message = '';
            if (this.paymentMode === 'razorpay' && !this.razorpay.payment_id) {
                await this.startRazorpayPayment(form);
                this.loading = false;
                return;
            }
            const formData = new FormData(form);
            formData.set('payment_mode', this.paymentMode);
            formData.set('razorpay_order_id', this.razorpay.order_id || '');
            formData.set('razorpay_payment_id', this.razorpay.payment_id || '');
            formData.set('razorpay_signature', this.razorpay.signature || '');
            fetch('process/submit_member.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.showSuccess = true;
                    form.reset();
                    this.paymentMode = 'manual';
                    this.razorpay = { order_id: '', payment_id: '', signature: '' };
                    this.updateFee();
                } else {
                    this.message = data.message || 'Failed to submit membership request.';
                    this.messageType = 'error';
                }
            })
            .catch((e) => {
                this.message = 'Unexpected error. Please try again.';
                this.messageType = 'error';
            })
            .finally(() => { this.loading = false; });
        },
        async startRazorpayPayment(form) {
            try {
                const preData = new FormData();
                preData.set('designation_id', form.querySelector('[name=designation_id]').value);
                preData.set('membership_fee', form.querySelector('[name=membership_fee]').value);
                const orderRes = await fetch('process/create_member_order.php', { method: 'POST', body: preData });
                const orderData = await orderRes.json();
                if (!orderData.success) {
                    this.message = orderData.message || 'Unable to initialize Razorpay payment.';
                    return;
                }
                const options = {
                    key: orderData.key_id,
                    amount: orderData.amount,
                    currency: orderData.currency,
                    name: orderData.name,
                    description: 'Membership Fee Payment',
                    order_id: orderData.order_id,
                    prefill: {
                        name: form.querySelector('[name=full_name]').value,
                        email: form.querySelector('[name=email]').value,
                        contact: form.querySelector('[name=phone]').value
                    },
                    theme: { color: '#2563eb' },
                    handler: (response) => {
                        this.razorpay.order_id = response.razorpay_order_id;
                        this.razorpay.payment_id = response.razorpay_payment_id;
                        this.razorpay.signature = response.razorpay_signature;
                        this.submitMemberForm({ target: form });
                    }
                };
                const rzp = new Razorpay(options);
                rzp.open();
            } catch (e) {
                this.message = 'Could not start Razorpay checkout.';
            }
        }
    }"
    x-init="updateFee(); updateEventMeta()">

    <div class="container mx-auto max-w-3xl">
        <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl border border-gray-100">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-id-badge text-3xl"></i>
                </div>
                <h1 class="text-4xl font-extrabold text-gray-800">Membership Registration</h1>
                <p class="text-gray-600 mt-2">Register as a member and pay fee via Manual or Razorpay.</p>
            </div>

            <form @submit.prevent="submitMemberForm($event)" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Full Name *</label>
                        <input type="text" name="full_name" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Email *</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Phone *</label>
                        <input type="text" name="phone" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Blood Group</label>
                        <select name="blood_group" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
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
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Date of Birth *</label>
                        <input type="date" name="dob" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Gender</label>
                        <select name="gender" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Qualification</label>
                        <input type="text" name="qualification" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Profession</label>
                        <input type="text" name="profession" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Marital Status</label>
                        <select name="marital_status" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Divorced">Divorced</option>
                            <option value="Separated">Separated</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                    </div>

                   <!-- REPLACE your current State + District fields with this code -->

<div>
    <label class="block text-sm font-medium mb-1.5 text-gray-700">State *</label>
    <select name="state" x-model="selectedState" @change="updateDistricts()" required
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
        <option value="">Select State</option>
        <template x-for="state in Object.keys(stateDistrictData)" :key="state">
            <option :value="state" x-text="state"></option>
        </template>
    </select>
</div>

<div>
    <label class="block text-sm font-medium mb-1.5 text-gray-700">District *</label>
    <select name="district" x-model="selectedDistrict" required
        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
        <option value="">Select District</option>

        <template x-for="district in districts" :key="district">
            <option :value="district" x-text="district"></option>
        </template>
    </select>
</div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Local Body Type</label>
                        <select name="local_body_type" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select</option>
                            <option value="Panchayat">Panchayat</option>
                            <option value="Municipality">Municipality</option>
                            <option value="Corporation">Corporation</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Panchayat / Municipality / Corporation Name</label>
                        <input type="text" name="local_body_name" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Ward No</label>
                        <input type="text" name="ward_no" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Ward Name</label>
                        <input type="text" name="ward_name" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <!-- <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Kudumbha Samithi</label>
                        <input type="text" name="kudumbha_samithi" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div> -->

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Event</label>
                        <select name="event_id" x-ref="eventSelect" @change="updateEventMeta()" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="">Select event</option>
                            <?php foreach ($events as $event): ?>
                                <option
                                    value="<?php echo (int)$event['id']; ?>"
                                    data-date="<?php echo htmlspecialchars((string)($event['event_date'] ?? '')); ?>"
                                    data-location="<?php echo htmlspecialchars((string)($event['location'] ?? '')); ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        (string)$event['title']
                                        . (!empty($event['event_date']) ? ' (' . date('d M Y', strtotime((string)$event['event_date'])) . ')' : '')
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Event Date</label>
                        <input type="text" x-ref="eventDateInput" readonly class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Event Location</label>
                        <input type="text" x-ref="eventLocationInput" readonly class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Occasion Name</label>
                        <input type="text" name="occasion_name" placeholder="e.g. Annual Youth Festival 2026" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Member Photo</label>
                        <input type="file" name="member_photo" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-gray-500">
                        <p class="mt-1 text-xs text-gray-500">Upload a clear passport-style photo. JPG, PNG, or WEBP only.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Designation *</label>
                        <select name="designation_id" x-ref="designationSelect" @change="updateFee()" required class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                            <?php foreach ($designations as $d): ?>
                                <option value="<?php echo (int)$d['id']; ?>" data-fee="<?php echo htmlspecialchars($d['fee_amount']); ?>">
                                    <?php echo htmlspecialchars($d['title']); ?> (INR <?php echo number_format($d['fee_amount'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Membership Fee (INR)</label>
                        <input type="text" x-ref="feeInput" name="membership_fee" readonly class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-2 text-gray-700">Payment Method *</label>
                        <div class="flex gap-4 text-sm">
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" value="manual" x-model="paymentMode" name="payment_mode_ui">
                                <span>Manual Payment</span>
                            </label>
                            <label class="inline-flex items-center gap-2">
                                <input type="radio" value="razorpay" x-model="paymentMode" name="payment_mode_ui">
                                <span>Razorpay</span>
                            </label>
                        </div>
                    </div>

                    <div x-show="paymentMode === 'manual'">
                        <label class="block text-sm font-medium mb-1.5 text-gray-700">Payment Transaction ID *</label>
                        <input type="text" name="payment_txn_id" :required="paymentMode === 'manual'" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

<div x-show="paymentMode === 'manual'">
    <label class="block text-sm font-medium mb-1.5 text-gray-700">
        Payment Screenshot *
    </label>

    <input
        type="file"
        name="payment_proof"
        accept="image/jpeg,image/png,image/webp"
        :required="paymentMode === 'manual'"
        class="w-full text-sm text-gray-500">
</div>

                    <div class="md:col-span-2" x-show="paymentMode === 'manual'" x-transition.opacity>
                        <div class="mt-2 bg-amber-50 border border-amber-100 rounded-xl p-5">
                            <h4 class="font-bold text-gray-900">Pay Membership Fee (Manual)</h4>
                            <p class="text-sm text-gray-600 mt-1">Scan UPI QR or transfer to our bank account. After payment, enter the Transaction ID above and submit.</p>

                            <div class="mt-4" x-data="{ tab: 'qr' }">
                                <div class="flex gap-2 text-sm font-semibold">
                                    <button type="button" @click="tab = 'qr'" class="px-4 py-2 rounded-lg border"
                                        :class="tab === 'qr' ? 'bg-white border-amber-300 text-amber-800' : 'bg-transparent border-amber-200 text-gray-600'">
                                        UPI / QR
                                    </button>
                                    <button type="button" @click="tab = 'bank'" class="px-4 py-2 rounded-lg border"
                                        :class="tab === 'bank' ? 'bg-white border-amber-300 text-amber-800' : 'bg-transparent border-amber-200 text-gray-600'">
                                        Bank Transfer
                                    </button>
                                </div>

                                <div class="mt-4" x-show="tab === 'qr'" x-transition.opacity>
                                    <div class="flex flex-wrap gap-6">
                                        <?php if (!empty($qrs)): foreach ($qrs as $q): ?>
                                            <div class="bg-white rounded-xl border border-amber-100 p-4 text-center w-56">
                                                <img src="<?php echo htmlspecialchars($q['qr_image_path']); ?>" class="w-44 h-44 mx-auto object-contain rounded-lg" alt="UPI QR">
                                                <p class="mt-2 text-sm font-bold text-gray-800"><?php echo htmlspecialchars($q['title']); ?></p>
                                            </div>
                                        <?php endforeach; else: ?>
                                            <p class="text-sm text-gray-500">QR codes will be available soon.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mt-4" x-show="tab === 'bank'" x-transition.opacity>
                                    <div class="space-y-3">
                                        <?php if (!empty($banks)): foreach ($banks as $b): ?>
                                            <div class="bg-white rounded-xl border border-amber-100 p-4">
                                                <p class="font-bold text-gray-800"><?php echo htmlspecialchars($b['bank_name']); ?></p>
                                                <div class="mt-2 text-sm text-gray-700 space-y-1">
                                                    <p><span class="font-semibold">A/C Holder:</span> <?php echo htmlspecialchars($b['account_holder']); ?></p>
                                                    <p><span class="font-semibold">A/C Number:</span> <?php echo htmlspecialchars($b['account_number']); ?></p>
                                                    <p><span class="font-semibold">IFSC:</span> <?php echo htmlspecialchars($b['ifsc_code']); ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; else: ?>
                                            <p class="text-sm text-gray-500">Bank details will be updated soon.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="ref" value="<?php echo htmlspecialchars($refCode); ?>">
                <input type="hidden" name="mref" value="<?php echo htmlspecialchars($donationRefCode); ?>">
                <input type="hidden" name="payment_mode" :value="paymentMode">
                <input type="hidden" name="razorpay_order_id" :value="razorpay.order_id">
                <input type="hidden" name="razorpay_payment_id" :value="razorpay.payment_id">
                <input type="hidden" name="razorpay_signature" :value="razorpay.signature">

                <div x-show="message" x-text="message" class="mt-5 p-3 rounded border bg-red-50 text-red-700 border-red-200 text-sm" x-transition></div>

                <div class="mt-8">
                    <button type="submit" :disabled="loading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-lg shadow transition disabled:opacity-60">
                        <span x-show="!loading && paymentMode === 'manual'">Submit Membership Application</span>
                        <span x-show="!loading && paymentMode === 'razorpay'">Pay with Razorpay & Submit</span>
                        <span x-show="loading">Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showSuccess" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4" x-cloak>
        <div class="bg-white max-w-md w-full rounded-2xl shadow-xl p-8 text-center">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-5">
                <i class="fas fa-check text-green-600 text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Registration Submitted</h2>
            <p class="text-gray-600 mt-3">Your membership request has been submitted successfully.</p>
            <div class="mt-6">
                <a href="index.php" class="inline-block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
