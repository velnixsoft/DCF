<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'OPTIONS') {
    if (!headers_sent()) http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

global $pdo;
if (!isset($pdo)) {
    $pdo = $GLOBALS['pdo'] ?? null;
}

// Helper response functions
function ai_respond(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Safe database query helpers
function safeQueryScalar(PDO $pdo, string $sql, $default = 0) {
    try {
        $val = $pdo->query($sql)->fetchColumn();
        return $val !== false ? $val : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function safeQueryAll(PDO $pdo, string $sql): array {
    try {
        $stmt = $pdo->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }
}

// Read JSON / POST input
$rawInput = file_get_contents('php://input');
$inputData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
    }
}
$postData = array_merge(is_array($_POST) ? $_POST : [], is_array($inputData) ? $inputData : []);

$message = trim((string)($postData['message'] ?? $postData['prompt'] ?? ''));
$panel = strtolower(trim((string)($postData['panel'] ?? 'user'))); // 'user' or 'admin'
$history = is_array($postData['history'] ?? null) ? $postData['history'] : [];
$action = trim((string)($postData['action'] ?? 'chat'));

// Fetch Settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
    $settings = [];
}

$siteName = $settings['site_name'] ?? 'Jaysmrutti Foundation';
$botName = $settings['ai_bot_name'] ?? ($panel === 'admin' ? 'Admin Copilot' : 'NGO Smart Guide');
$ngoPhone = $settings['ngo_phone'] ?? '+91 7651910331';
$ngoEmail = $settings['ngo_email'] ?? 'info@velnixsoft.com';
$ngoAddress = $settings['ngo_address'] ?? 'Jaunpur, Uttar Pradesh, India';
$regNo = $settings['reg_no'] ?? '123455';
$whatsappNo = $settings['whatsapp_number'] ?? '917651910331';
$disclaimer = $settings['receipt_disclaimer'] ?? 'Donations are tax exempted u/s 80G.';

// ==========================================
// 1. ACTION: GET INITIAL SUGGESTIONS & INFO
// ==========================================
if ($action === 'init') {
    if ($panel === 'admin') {
        $isAdmin = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        if (!$isAdmin) {
            ai_respond([
                'success' => false,
                'message' => 'Admin authentication required.',
                'authenticated' => false
            ], 401);
        }

        $adminName = $_SESSION['user_name'] ?? 'Admin';
        $adminRole = $_SESSION['user_role'] ?? 'Administrator';

        // Quick live metrics for prompt initial banner
        $totalDonation = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'", 0);
        $pendingVolunteers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Pending'", 0);
        $activeMembers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM members WHERE status = 'Active'", 0);
        $unreadMessages = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM contact_messages WHERE status = 'New'", 0);
        $totalBeneficiaries = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiaries", 0);
        $totalProviders = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers", 0);
        $pendingComplaints = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM complaints WHERE status = 'pending'", 0);
        $jobApplications = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM job_applications", 0);

        ai_respond([
            'success' => true,
            'bot_name' => $botName,
            'greeting' => "Namaste **{$adminName}** ({$adminRole})! ⚡ Main aapka **Admin AI Sahayak (Copilot)** hoon. Mere paas NGO ke sabhi **25+ Modules** ka complete access hai.\n\nAap mujhse kisi bhi module, live daan (donations), labharthi (beneficiaries), Swasthya health cards, doctor agreements, job vacancies, kharcha (expenses), ya operational shortcuts ke baare me pooch sakte hain!",
            'stats' => [
                'total_donations' => '₹' . number_format($totalDonation, 2),
                'pending_volunteers' => $pendingVolunteers,
                'active_members' => $activeMembers,
                'unread_inquiries' => $unreadMessages,
                'beneficiaries' => $totalBeneficiaries,
                'healthcare_providers' => $totalProviders,
                'pending_complaints' => $pendingComplaints,
                'job_applications' => $jobApplications
            ],
            'suggestions' => [
                ['label' => '🗺️ Sabhi Modules Ki List', 'prompt' => 'NGO system ke sabhi modules ki list aur guide dikhao'],
                ['label' => '📊 NGO Operations Summary', 'prompt' => 'NGO operations ki overall summary aur live stats dikhao'],
                ['label' => '🏥 Doctor & Health Partnerships', 'prompt' => 'Healthcare directory, doctors aur hospital MoUs ke baare me batao'],
                ['label' => '🤝 Labharthi & Sahayata Vitaran', 'prompt' => 'Beneficiaries ki sankhya aur sahayata vitaran ka hisab dikhao'],
                ['label' => '💸 Kharcha Tracker (Expenses)', 'prompt' => 'Kharche ka hisab aur monthly income vs expense balance dikhao'],
                ['label' => '💼 Job Vacancies & HR Policies', 'prompt' => 'Naye job vacancy kaise post karein aur aavedan kaise dekhein?'],
                ['label' => '🎓 Student Ambassadors Wing', 'prompt' => 'Student ambassador aur campaign tasks kaise kaam karte hain?'],
                ['label' => '📜 Document Studio & Letterheads', 'prompt' => 'Official letterhead, appointment letter aur certificates kaise banayein?'],
                ['label' => '⚙️ Payment Gateway & Settings', 'prompt' => 'Razorpay, UPI QR aur email settings kahan se badle?']
            ]
        ]);
    } else {
        // User Panel Initial Data
        $customGreeting = $settings['ai_welcome_message'] ?? "Namaste! 🙏 Welcome to **{$siteName}**. I am your AI Guide. How can I help you today?";
        ai_respond([
            'success' => true,
            'bot_name' => $botName,
            'greeting' => $customGreeting,
            'suggestions' => [
                ['label' => '💖 How to Donate?', 'prompt' => 'How can I donate to this NGO and get 80G tax benefit?'],
                ['label' => '🤝 Become a Member', 'prompt' => 'How to become a member and get an ID card?'],
                ['label' => '🙋 Join as Volunteer', 'prompt' => 'How can I become a volunteer in your NGO?'],
                ['label' => '🌱 Ongoing Projects', 'prompt' => 'What projects and social drives are currently active?'],
                ['label' => '📜 Verify Certificate / ID', 'prompt' => 'How do I verify a member ID, volunteer ID, or certificate?'],
                ['label' => '📞 Contact & Office Info', 'prompt' => 'What is your office address, phone number, and WhatsApp?']
            ]
        ]);
    }
}

// ==========================================
// 2. CHAT HANDLER
// ==========================================
if (empty($message)) {
    ai_respond([
        'success' => false,
        'message' => 'Please provide a prompt or question.'
    ], 400);
}

// ------------------------------------------
// A. ADMIN PANEL AI COPILOT PROCESSING
// ------------------------------------------
if ($panel === 'admin') {
    $isAdmin = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    if (!$isAdmin) {
        ai_respond([
            'success' => false,
            'reply' => "🔒 **Session Expired or Unauthorized**: Please log in to the [Admin Panel](/admin) to query administrative information.",
            'actions' => [
                ['label' => 'Login to Admin', 'url' => '/admin', 'icon' => 'fa-lock']
            ]
        ], 401);
    }

    $reply = processAdminQuery($pdo, $message, $settings);
    ai_respond(array_merge(['success' => true], $reply));
}

// ------------------------------------------
// B. USER PANEL AI GUIDE PROCESSING
// ------------------------------------------
$reply = processUserQuery($pdo, $message, $settings);
ai_respond(array_merge(['success' => true], $reply));


// ==========================================
// HINGLISH LANGUAGE DETECTOR HELPER
// ==========================================
function isHinglishQuery(string $q): bool {
    // Check Devanagari Unicode block (Hindi characters)
    if (preg_match('/[\x{0900}-\x{097F}]/u', $q)) {
        return true;
    }

    // Specific Hinglish phrases
    if (preg_match('/\b(baare me|bare me|ke baare|ke bare|kya hai|kaise kare|kaise karein|kitna hai|kitna hua|kaise banaye|kaise dekhe)\b/iu', $q)) {
        return true;
    }

    $hinglishTokens = [
        'kya', 'kaise', 'dikhao', 'batao', 'kitna', 'kitne', 'kitni', 'kahan', 'kaha', 
        'kharcha', 'kharche', 'madad', 'labharthi', 'daan', 'hai', 'hain', 'karo', 'karein', 'karna', 
        'chahiye', 'kisko', 'kiska', 'kiski', 'sabhi', 'wala', 'wali', 'vale', 'mujhe', 
        'bana', 'banayein', 'banaye', 'aaya', 'gaya', 'sadasya', 'naukri', 'shikayat', 
        'patra', 'sanstha', 'kaun', 'konsa', 'konse', 'hum', 'humein', 'chhodkar', 
        'chodkar', 'pura', 'hoga', 'hogi', 'dijie', 'dena', 'lekin', 'aur',
        'mein', 'kare', 'bolo', 'sunao', 'chal', 'raha', 'swasthya', 'kaccha',
        'shuru', 'kholna', 'parivar', 'yojana', 'paise', 'jankari',
        'bataye', 'pratikriya', 'nivedan', 'karega', 'karenge', 'jana', 'jane'
    ];
    foreach ($hinglishTokens as $token) {
        if (preg_match('/\b' . preg_quote($token, '/') . '\b/iu', $q)) {
            return true;
        }
    }
    return false;
}

// ==========================================
// ADMIN QUERY PROCESSOR FUNCTION (COMPREHENSIVE)
// ==========================================
function processAdminQuery(PDO $pdo, string $query, array $settings): array {
    $q = mb_strtolower(trim($query));
    $isHinglish = isHinglishQuery($q);

    // -------------------------------------------------------------
    // 1. ALL MODULES DIRECTORY / SITEMAP / GUIDE / HELP
    // -------------------------------------------------------------
    if (preg_match('/(all module|all modules|list module|list modules|sitemap|modules|menu|features|kya kya hai|guide me|help me|directory|navigation|sabhi module)/i', $q) && !preg_match('/(donation|expense|beneficiar|health|doctor|volunteer|student|job|daan|kharcha|labharthi)/i', $q)) {
        if ($isHinglish) {
            $text = "### 🗺️ **NGO System Ke Sabhi Modules Ki Directory & Guide**\n\n"
                  . "Mere paas platform ke sabhi **25+ administrative modules** ka live access hai. Yahan categorized operational guide di gayi hai:\n\n"
                  . "#### 🏥 **1. Swasthya & Labharthi Kalyan (Healthcare & Beneficiaries)**\n"
                  . "• **[Labharthi Directory (/admin/beneficiaries)](/admin/beneficiaries)**: Parivar, BPL, vidhwa, anath aur divyang labharthiyon ko register karein.\n"
                  . "• **[Sahayata Vitaran Log (/admin/beneficiary-assistance)](/admin/beneficiary-assistance)**: Ration kit, medical sahayata, chhatravritti aur direct aid log karein.\n"
                  . "• **[Labharthi Reports (/admin/beneficiary-reports)](/admin/beneficiary-reports)**: Category-wise aur demographic distribution reports.\n"
                  . "• **[Swasthya Directory (/admin/healthcare_directory.php)](/admin/healthcare_directory.php)**: Hospitals, clinics, pathology labs aur pharmacies ki list.\n"
                  . "• **[Doctor MoUs & Agreements (/admin/doctor_agreements.php)](/admin/doctor_agreements.php)**: Doctors ke sath discounted OPD agreements banayein.\n"
                  . "• **[Swasthya Health Cards (/admin/health_card_applications.php)](/admin/health_card_applications.php)**: QR code wale digital/printable health card issue karein.\n\n"
                  . "#### 💰 **2. Vitt, Receipts & Daan (Finance & Donations)**\n"
                  . "• **[Daan Management (/admin/donations)](/admin/donations)**: Razorpay, UPI QR aur online gateway collections.\n"
                  . "• **[Custom 80G Receipts (/admin/generate_custom_receipt.php)](/admin/generate_custom_receipt.php)**: Offline cash/cheque daan ke liye 80G receipt generator.\n"
                  . "• **[Auto Pay / Recurring Daan (/admin/recurring-donations)](/admin/recurring-donations)**: Monthly recurring daan mandates track karein.\n"
                  . "• **[Samaan Ka Daan (/admin/item-donation-categories)](/admin/item-donation-categories)**: Kapde, ration, pustakein, wheelchairs, kambal ka hisab.\n"
                  . "• **[Kharcha Tracker (/admin/expenses)](/admin/expenses)**: Vouchers, bills aur category-wise kharchon ka hisab.\n"
                  . "• **[Income vs Expense Balance (/admin/income-vs-expense)](/admin/income-vs-expense)**: Real-time surplus/deficit audit sheet.\n"
                  . "• **[Donation Analytics (/admin/donation-analytics)](/admin/donation-analytics)**: Financial trends aur source breakdown.\n\n"
                  . "#### 🪪 **3. Sadasyata, Patra & Swayamsevak (Community & Members)**\n"
                  . "• **[Membership Management (/admin/memberships)](/admin/memberships)**: Sadasya tiers, fees, approval aur digital ID cards.\n"
                  . "• **[Document Studio & Letterhead (/admin/document-studio)](/admin/document-studio)**: Official letterhead, appointment letters, appreciation certificates.\n"
                  . "• **[Staff & Volunteer Patra (/admin/staff_letters.php)](/admin/staff_letters.php)**: Joining, experience aur authorization letters.\n"
                  . "• **[Visitor Certificates (/admin/visitor-certificates)](/admin/visitor-certificates)**: Guest of honor aur seminar certificates.\n"
                  . "• **[Sanstha Authorization (/admin/sanstha-certificates)](/admin/sanstha-certificates)**: Branch aur affiliated centers ka affiliation certificate.\n"
                  . "• **[Volunteers & Field Activities (/admin/volunteers)](/admin/volunteers)**: Swayamsevak roster aur approval pipeline.\n\n"
                  . "#### 🎓 **4. Student Ambassadors & Yuva Wing**\n"
                  . "• **[Student Directory (/admin/student-directory)](/admin/student-directory)**: College ambassadors aur enrollment records.\n"
                  . "• **[Campaign Tasks & Submissions (/admin/student-tasks)](/admin/student-tasks)**: Social tasks assign karein, proof check karein aur points dein.\n"
                  . "• **[Referral Verification (/admin/student-referrals)](/admin/student-referrals)**: Students ke referrals aur vendor leads verify karein.\n"
                  . "• **[Student Certificates (/admin/student-certificates)](/admin/student-certificates)**: Performance certificates aur leaderboard.\n\n"
                  . "#### 💼 **5. Naukri, HR Niyam & Governance (Careers & HR)**\n"
                  . "• **[Job Vacancies (/admin/jobs.php)](/admin/jobs.php)**: Coordinator, social worker aur accountant vacancies post karein.\n"
                  . "• **[Job Applications Screening (/admin/job_applications.php)](/admin/job_applications.php)**: Candidate resumes aur interview status.\n"
                  . "• **[HR Policies (/admin/hr_policies.php)](/admin/hr_policies.php)**: Code of Conduct, POSH Act, Child Protection aur Leave rules.\n"
                  . "• **[Career Guidance CMS (/admin/career_guidance_manager.php)](/admin/career_guidance_manager.php)**: Skill development aur vocational courses.\n"
                  . "• **[Agreements & MoUs (/admin/agreements.php)](/admin/agreements.php)**: Institutional partnerships aur bilateral contracts.\n"
                  . "• **[Org Hierarchy Tree (/admin/org_structure.php)](/admin/org_structure.php)**: Executive Board, Patron aur leadership tree.\n\n"
                  . "#### ⚙️ **6. Field Staff & Operations Settings**\n"
                  . "• **[Field Agent GPS Attendance & Payroll (/admin/field-agents.php)](/admin/field-agents.php)**: Daily GPS punch-in, cash deposit aur salary slips.\n"
                  . "• **[Projects & Crowdfunding (/admin/projects)](/admin/projects)**: Social campaigns aur donation progress.\n"
                  . "• **[Events & Camps (/admin/events)](/admin/events)**: Free medical camps, plantation drives aur galleries.\n"
                  . "• **[Shikayatein (Complaints) (/admin/complaints)](/admin/complaints)**: Public grievance redressal ticket system.\n"
                  . "• **[Staff Feedback (/admin/feedbacks.php)](/admin/feedbacks.php)**: Star ratings aur suggestions.\n"
                  . "• **[System Settings (/admin/settings)](/admin/settings)**: Razorpay keys, UPI QR, SMTP, AI settings aur branding.";
        } else {
            $text = "### 🗺️ **Comprehensive NGO System Modules Catalog**\n\n"
                  . "I have direct access to all **25+ administrative wings and modules** across this platform. Here is the categorized operational directory:\n\n"
                  . "#### 🏥 **1. Healthcare & Beneficiary Welfare**\n"
                  . "• **[Beneficiaries Directory](/admin/beneficiaries)**: Register families, BPL, senior citizens, orphans, and Divyang beneficiaries.\n"
                  . "• **[Assistance Distribution Log](/admin/beneficiary-assistance)**: Log ration, health kits, scholarships, and monetary aid.\n"
                  . "• **[Beneficiary Reports](/admin/beneficiary-reports)**: Statistical demographic reports and distribution analytics.\n"
                  . "• **[Healthcare Directory](/admin/healthcare_directory.php)**: Empaneled hospitals, clinics, pathology labs, pharmacies, GPS map pins.\n"
                  . "• **[Doctor Agreements & MoUs](/admin/doctor_agreements.php)**: Healthcare bilateral contracts, discounted OPD packages.\n"
                  . "• **[Health Cards (Swasthya Cards)](/admin/health_card_applications.php)**: Issue digitized health cards with verifiable QR codes.\n\n"
                  . "#### 💰 **2. Finance, Receipts & Donations**\n"
                  . "• **[Donations Management](/admin/donations)**: Razorpay, UPI QR, and online gateway collections.\n"
                  . "• **[Custom 80G Receipts](/admin/generate_custom_receipt.php)**: Offline manual receipt book generator & instant PDF downloads.\n"
                  . "• **[Auto Pay / Recurring Subscriptions](/admin/recurring-donations)**: Monthly recurring donor mandates.\n"
                  . "• **[Item & In-Kind Donations](/admin/item-donation-categories)**: Clothes, ration, books, wheelchairs, blankets.\n"
                  . "• **[Expense Tracker](/admin/expenses)**: Vouchers, bills, vendor payments, and category-wise spending.\n"
                  . "• **[Income vs Expense Balance](/admin/income-vs-expense)**: Real-time surplus/deficit financial audit.\n"
                  . "• **[Donation Analytics](/admin/donation-analytics)**: Financial trends, donor retention, and source breakdowns.\n\n"
                  . "#### 🪪 **3. Community, Members & Volunteers**\n"
                  . "• **[Membership Management](/admin/memberships)**: Member tiers, fees, membership approval, and digital ID cards.\n"
                  . "• **[Document Studio & Letterhead](/admin/document-studio)**: Official NGO letterheads, appointment letters, appreciation certificates.\n"
                  . "• **[Staff & Volunteer Letters](/admin/staff_letters.php)**: Role authorizations, service recognition, sanction letters.\n"
                  . "• **[Visitor Certificates](/admin/visitor-certificates)**: Guest passes, seminar attendance, and participant certificates.\n"
                  . "• **[Sanstha Authorization](/admin/sanstha-certificates)**: Branch, affiliated center, and trust authorizations.\n"
                  . "• **[Volunteers & Field Activities](/admin/volunteers)**: Volunteer roster, hours logged, and approval pipeline.\n\n"
                  . "#### 🎓 **4. Student Ambassadors & Youth Wing**\n"
                  . "• **[Student Directory](/admin/student-directory)**: Campus ambassadors, colleges, enrollment records.\n"
                  . "• **[Campaign Tasks & Submissions](/admin/student-tasks)**: Assign social tasks, verify proof submissions, reward points.\n"
                  . "• **[Referral Verification & Vendor Leads](/admin/student-referrals)**: Track student-driven memberships & merchant leads.\n"
                  . "• **[Student Certificates & Leaderboard](/admin/student-certificates)**: Performance certificates and gamified rankings.\n\n"
                  . "#### 💼 **5. Careers, HR Policies & Governance**\n"
                  . "• **[Job Openings & Careers](/admin/jobs.php)**: State/District/Block/Panchayat coordinator vacancies.\n"
                  . "• **[Job Applications Screening](/admin/job_applications.php)**: Applicant resumes, interview notes, and candidate onboarding.\n"
                  . "• **[HR Workplace Policies](/admin/hr_policies.php)**: Code of Conduct, POSH Act, Child Safeguarding, Leave rules.\n"
                  . "• **[Career Guidance CMS](/admin/career_guidance_manager.php)**: Vocational skill courses and student enrollment counseling.\n"
                  . "• **[Agreements & MoUs](/admin/agreements.php)**: Institutional partnerships, service agreements, and digital signing.\n"
                  . "• **[Organization Hierarchy Tree](/admin/org_structure.php)**: Executive Board, Patron, Trustees, and leadership tree.\n\n"
                  . "#### ⚙️ **6. Operations, Communications & Settings**\n"
                  . "• **[Field Agent Operations & Payroll](/admin/field-agents.php)**: Daily GPS punch-in, cash collection, and payroll.\n"
                  . "• **[Projects & Crowdfunding](/admin/projects)**: Ongoing causes, target milestones, and public campaigns.\n"
                  . "• **[Events & RSVPs](/admin/events)**: Awareness camps, plantation drives, and photo albums.\n"
                  . "• **[Complaints & Suggestions](/admin/complaints)**: Public grievance redressal and ticket resolution.\n"
                  . "• **[Staff & Member Feedback](/admin/feedbacks.php)**: Internal star ratings and stakeholder suggestions.\n"
                  . "• **[System Settings & Payment Gateways](/admin/settings)**: Razorpay keys, UPI QR, SMTP, AI configuration, branding.\n"
                  . "• **[Access Control & Roles](/admin/access-control)**: Admin, Manager, Coordinator, and Field Agent permissions.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Healthcare Directory', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user'],
                ['label' => 'Beneficiaries', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child'],
                ['label' => 'Donations Panel', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Expenses Tracker', 'url' => '/admin/expenses', 'icon' => 'fa-receipt'],
                ['label' => 'Memberships', 'url' => '/admin/memberships', 'icon' => 'fa-id-card'],
                ['label' => 'Student Directory', 'url' => '/admin/student-directory', 'icon' => 'fa-graduation-cap'],
                ['label' => 'Job Postings', 'url' => '/admin/jobs.php', 'icon' => 'fa-briefcase'],
                ['label' => 'Settings', 'url' => '/admin/settings', 'icon' => 'fa-gear']
            ],
            'suggestions' => [
                'Show healthcare directory and doctor MOUs',
                'Show expense summary and financial balance',
                'Show beneficiaries and assistance log',
                'Show student ambassador tasks and submissions'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 2. SETTINGS, PAYMENTS, RAZORPAY, UPI, SMTP & SYSTEM CONFIG
    // -------------------------------------------------------------
    if (preg_match('/(setting|settings|config|razorpay|upi|smtp|email setting|logo|seal|theme|access control|audit log|audit logs|server info|system info)/i', $q)) {
        $text = "### ⚙️ **Admin Configuration & System Shortcuts**\n\n"
              . "Configure payment gateways, email credentials, document branding, AI parameters, and user permissions:\n\n"
              . "• 💳 **Payment & Razorpay Settings**: [Configure Gateways, Key ID, Secret & UPI QR](/admin/settings?tab=payments)\n"
              . "• 🏢 **General NGO Profile & Registration**: [Update Address, Reg No, Trust Details](/admin/settings?tab=general)\n"
              . "• 📧 **SMTP Email Server**: [Configure Mail Host, Port, Username & Password](/admin/settings?tab=smtp)\n"
              . "• 🎨 **Document Branding & Letterhead**: [Upload Official Logo, Signature & Stamp](/admin/settings?tab=brands)\n"
              . "• 🤖 **AI Assistant Configuration**: [Set AI Bot Name, Welcome Prompt & Gemini Key](/admin/settings?tab=ai)\n"
              . "• 👥 **Access Control & Staff Roles**: [Set Coordinator / Manager / Agent Permissions](/admin/access-control)\n"
              . "• 📋 **Audit Logs**: [Inspect Administrative Login & Action History](/admin/audit-logs)\n"
              . "• 💻 **System Health & Server Info**: [Inspect PHP, MySQL & Extension Diagnostics](/admin/system-info)\n";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Payment Settings', 'url' => '/admin/settings?tab=payments', 'icon' => 'fa-credit-card'],
                ['label' => 'General Settings', 'url' => '/admin/settings?tab=general', 'icon' => 'fa-gear'],
                ['label' => 'Access Control', 'url' => '/admin/access-control', 'icon' => 'fa-user-shield'],
                ['label' => 'Audit Logs', 'url' => '/admin/audit-logs', 'icon' => 'fa-clipboard-list']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 3. PROJECTS, CAUSES & CROWDFUNDING
    // -------------------------------------------------------------
    if (preg_match('/(project|projects|campaign|campaigns|crowdfund|crowdfunding|fundrais|cause|initiatives|yojana)/i', $q)) {
        $projects = safeQueryAll($pdo, "SELECT title, target_amount, raised_amount, status FROM projects ORDER BY id DESC LIMIT 6");
        $text = "### 🌱 **Projects & Crowdfunding Campaigns**\n\n"
              . "The **Projects Module** tracks capital funding goals, social welfare causes, and real-time donation progress bars for public campaigns.\n\n";

        if (!empty($projects)) {
            $text .= "| Project Title | Goal | Raised | Status |\n| :--- | :--- | :--- | :--- |\n";
            foreach ($projects as $p) {
                $pct = $p['target_amount'] > 0 ? round(($p['raised_amount'] / $p['target_amount']) * 100) : 0;
                $text .= "| **" . htmlspecialchars($p['title']) . "** | ₹" . number_format((float)$p['target_amount']) . " | ₹" . number_format((float)$p['raised_amount']) . " ({$pct}%) | " . htmlspecialchars($p['status'] ?? 'Active') . " |\n";
            }
        } else {
            $text .= "_No projects currently created._\n";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Manage Projects', 'url' => '/admin/projects', 'icon' => 'fa-seedling'],
                ['label' => 'Crowdfunding Manager', 'url' => '/admin/crowdfunding', 'icon' => 'fa-bullhorn'],
                ['label' => 'Donations Panel', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 4. EVENTS, MEDICAL CAMPS & RSVPS
    // -------------------------------------------------------------
    if (preg_match('/(event|events|camp|camps|activity|activities|karyakram|plantation|workshop)/i', $q) && !preg_match('/(volunteer activity)/i', $q)) {
        $totalEvents = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM events", 0);
        $upcomingEvents = safeQueryAll($pdo, "SELECT title, event_date, location FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 4");

        $text = "### 📅 **Events, Awareness Camps & Drives**\n\n"
              . "The **Events Module** manages health camps, tree plantation drives, relief distributions, attendee registrations, and event photo galleries.\n\n"
              . "• **Total Events Logged**: `{$totalEvents}`\n\n";

        if (!empty($upcomingEvents)) {
            $text .= "#### 🚀 **Upcoming Scheduled Events:**\n";
            foreach ($upcomingEvents as $ev) {
                $text .= "• **" . htmlspecialchars($ev['title']) . "** on **" . date('d M, Y', strtotime($ev['event_date'])) . "** at `" . htmlspecialchars($ev['location'] ?: 'Jaunpur') . "`\n";
            }
        } else {
            $text .= "• No future events currently scheduled.\n";
        }

        $text .= "\n👉 Create new events and view RSVP reports at **[Events Management](/admin/events)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Events Management', 'url' => '/admin/events', 'icon' => 'fa-calendar-days'],
                ['label' => 'Create New Event', 'url' => '/admin/create-event.php', 'icon' => 'fa-calendar-plus'],
                ['label' => 'Gallery Manager', 'url' => '/admin/gallery-manager', 'icon' => 'fa-images']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 5. AGREEMENTS & BILATERAL MOUS
    // -------------------------------------------------------------
    if (preg_match('/(agreement|agreements|mou|mous|contract|memorandum|partnership agreement)/i', $q) && !preg_match('/(doctor)/i', $q)) {
        $totalAgreements = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM agreements", 0);
        $signedAgreements = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM agreements WHERE signed_status = 'signed'", 0);
        $pendingAgreements = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM agreements WHERE signed_status = 'pending_signature' OR signed_status = 'draft'", 0);
        $recentAgreements = safeQueryAll($pdo, "SELECT agreement_no, partner_name, type, signed_status, valid_until FROM agreements ORDER BY id DESC LIMIT 5");

        $text = "### 📜 **Agreements, MoUs & Institutional Partnerships**\n\n"
              . "The **Agreements & MoUs Module** enables the organization to compose, manage, digitally acknowledge, and generate PDF contracts on official letterhead.\n\n"
              . "#### 📊 **Agreements Overview:**\n"
              . "• **Total Agreements Drafted**: `{$totalAgreements}`\n"
              . "• **Signed & Executed MoUs**: `{$signedAgreements}`\n"
              . "• **Draft / Pending Signatures**: `{$pendingAgreements}`\n\n";

        if (!empty($recentAgreements)) {
            $text .= "#### 📋 **Recent Agreements:**\n"
                   . "| Agreement No | Partner Organization | Type | Status | Valid Until |\n| :--- | :--- | :--- | :--- | :--- |\n";
            foreach ($recentAgreements as $ag) {
                $statusBadge = $ag['signed_status'] === 'signed' ? '✅ Signed' : '⏳ ' . ucfirst($ag['signed_status']);
                $text .= "| `" . htmlspecialchars($ag['agreement_no']) . "` | **" . htmlspecialchars($ag['partner_name']) . "** | " . strtoupper(htmlspecialchars($ag['type'])) . " | {$statusBadge} | " . ($ag['valid_until'] ? date('d M Y', strtotime($ag['valid_until'])) : 'Ongoing') . " |\n";
            }
        }

        $text .= "\n👉 Create contracts and bilateral memorandums at **[Agreements & MoUs](/admin/agreements.php)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Agreements & MoUs', 'url' => '/admin/agreements.php', 'icon' => 'fa-file-contract'],
                ['label' => 'Doctor Agreements', 'url' => '/admin/doctor_agreements.php', 'icon' => 'fa-hospital-user'],
                ['label' => 'Document Studio', 'url' => '/admin/document-studio', 'icon' => 'fa-file-signature']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 6. OFFICIAL LETTERHEAD, DOCUMENT STUDIO & STAFF LETTERS
    // -------------------------------------------------------------
    if (preg_match('/(letter|letters|letterhead|document studio|staff letter|appointment letter|appreciation letter|sanction letter|template builder|visitor certificate|sanstha certificate)/i', $q)) {
        $totalLetters = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM letters", 0);
        $totalStaffLetters = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM staff_letters", 0);
        $totalVisitorCerts = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM visitor_certificates", 0);
        $totalSansthaCerts = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sanstha_certificates", 0);

        $text = "### 📄 **Document Studio, Official Letterhead & Certificates**\n\n"
              . "The **Document Studio & Letterhead System** automates the generation of branded, serial-numbered PDF documents, appointment letters, and authorization certificates.\n\n"
              . "#### 📊 **Document Generation Stats:**\n"
              . "• **Official Letterhead Letters Issued**: `{$totalLetters}` letters\n"
              . "• **Staff & Volunteer Service Letters**: `{$totalStaffLetters}` documents\n"
              . "• **Visitor & Guest Certificates**: `{$totalVisitorCerts}` issued\n"
              . "• **Sanstha / Branch Authorizations**: `{$totalSansthaCerts}` verified certificates\n\n"
              . "#### 🧭 **Document Hub Modules:**\n"
              . "• **[Document Studio](/admin/document-studio)**: Generate member welcome kits, ID documents, and custom member letters.\n"
              . "• **[Generated Letters](/admin/letters.php)**: Compose appointment, request, sanction, and appreciation letters on NGO letterhead.\n"
              . "• **[Staff Letters](/admin/staff_letters.php)**: Issue staff experience letters, joining letters, and coordinator authorizations.\n"
              . "• **[Visitor Certificates](/admin/visitor-certificates)**: Issue printable certificates for event guests, donors, and seminar attendees.\n"
              . "• **[Sanstha Authorization](/admin/sanstha-certificates)**: Issue legal affiliation certificates for local centers and partner institutions.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Document Studio', 'url' => '/admin/document-studio', 'icon' => 'fa-file-signature'],
                ['label' => 'Letterhead Letters', 'url' => '/admin/letters.php', 'icon' => 'fa-envelope-open-text'],
                ['label' => 'Staff Letters', 'url' => '/admin/staff_letters.php', 'icon' => 'fa-user-pen'],
                ['label' => 'Visitor Certificates', 'url' => '/admin/visitor-certificates', 'icon' => 'fa-certificate'],
                ['label' => 'Sanstha Authorization', 'url' => '/admin/sanstha-certificates', 'icon' => 'fa-stamp']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 7. HEALTHCARE DIRECTORY, HOSPITALS, CLINICS, DOCTORS & HEALTH CARDS
    // -------------------------------------------------------------
    if (preg_match('/(healthcare|health care|hospital|clinic|pathology|pharmacy|doctor|swasthya|health card|doctor agreement|doctor mou|medical facility|health directory)/i', $q)) {
        $totalProviders = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers", 0);
        $hospitals = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers WHERE type = 'hospital'", 0);
        $clinics = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers WHERE type = 'clinic'", 0);
        $labs = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers WHERE type = 'pathology_lab'", 0);
        $pharmacies = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers WHERE type = 'pharmacy'", 0);
        $doctors = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers WHERE type = 'doctor'", 0);
        
        $totalHealthCards = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM health_cards", 0);
        $activeHealthCards = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM health_cards WHERE status = 'active'", 0);
        
        $totalDoctorAgreements = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM agreements WHERE type = 'mou' OR type = 'service_agreement' OR partner_type = 'hospital' OR partner_type = 'doctor'", 0);

        $recentProviders = safeQueryAll($pdo, "SELECT name, type, speciality, district, contact FROM healthcare_providers ORDER BY id DESC LIMIT 4");

        if ($isHinglish) {
            $text = "### 🏥 **Swasthya Directory, Doctors & Health Cards Ki Jankari**\n\n"
                  . "**Healthcare Panel** ke jariye aap empaneled hospitals, clinics, pathology labs aur citizen Swasthya Cards ko manage kar sakte hain:\n\n"
                  . "#### 📊 **Live Healthcare Network Snapshot:**\n"
                  . "• **Kul Empaneled Healthcare Providers**: `{$totalProviders}`\n"
                  . "  - 🏥 Hospitals: `{$hospitals}` | 🩺 Clinics: `{$clinics}` | 🧪 Pathology Labs: `{$labs}`\n"
                  . "  - 💊 Pharmacies: `{$pharmacies}` | 👨‍⚕️ Doctors: `{$doctors}`\n"
                  . "• **🪪 Swasthya Health Cards**: `{$activeHealthCards}` Active Cards (Total `{$totalHealthCards}` applications)\n"
                  . "• **📜 Doctor MoUs & Agreements**: `{$totalDoctorAgreements}` bilateral contracts\n\n"
                  . "#### 📍 **Haal Hi Me Jude Healthcare Providers:**\n";

            if (!empty($recentProviders)) {
                $text .= "| Provider Ka Naam | Type | Speciality | District | Contact |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentProviders as $hp) {
                    $text .= "| **" . htmlspecialchars($hp['name']) . "** | " . ucfirst(htmlspecialchars($hp['type'])) . " | " . htmlspecialchars($hp['speciality'] ?: 'General') . " | " . htmlspecialchars($hp['district'] ?: 'N/A') . " | " . htmlspecialchars($hp['contact']) . " |\n";
                }
            } else {
                $text .= "_Abhi tak koi healthcare provider register nahi hai._\n";
            }

            $text .= "\n#### 🧭 **Module Ko Use Karne Ka Tarika:**\n"
                   . "1. **Naya Doctor/Hospital Jodein**: **[Healthcare Directory](/admin/healthcare_directory.php)** par jayein aur '+ Add Provider' par click karein.\n"
                   . "2. **Health Card Issue Karein**: **[Health Card Applications](/admin/health_card_applications.php)** me citizen request approve karke QR health card print karein.\n"
                   . "3. **Doctor MoU Banayein**: **[Doctor Agreements & MoUs](/admin/doctor_agreements.php)** par official discount agreement tayar karein.";
        } else {
            $text = "### 🏥 **Healthcare Directory, Empanelment & Health Cards**\n\n"
                  . "The **Healthcare Panel** manages empaneled medical providers, doctor partnership MOUs, and citizen Swasthya Health Cards with discounted rates.\n\n"
                  . "#### 📊 **Current Healthcare Network Metrics:**\n"
                  . "• **Total Empaneled Providers**: `{$totalProviders}`\n"
                  . "  - 🏥 Hospitals: `{$hospitals}` | 🩺 Clinics: `{$clinics}` | 🧪 Pathology Labs: `{$labs}`\n"
                  . "  - 💊 Pharmacies: `{$pharmacies}` | 👨‍⚕️ Individual Doctors: `{$doctors}`\n"
                  . "• **🪪 Swasthya Health Cards Issued**: `{$activeHealthCards}` Active (Total `{$totalHealthCards}` applications)\n"
                  . "• **📜 Healthcare Partnerships & MOUs**: `{$totalDoctorAgreements}` bilateral contracts\n\n"
                  . "#### 📍 **Recent Empaneled Healthcare Providers:**\n";

            if (!empty($recentProviders)) {
                $text .= "| Provider Name | Facility Type | Speciality | District | Contact |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentProviders as $hp) {
                    $text .= "| **" . htmlspecialchars($hp['name']) . "** | " . ucfirst(htmlspecialchars($hp['type'])) . " | " . htmlspecialchars($hp['speciality'] ?: 'General') . " | " . htmlspecialchars($hp['district'] ?: 'N/A') . " | " . htmlspecialchars($hp['contact']) . " |\n";
                }
            } else {
                $text .= "_No healthcare providers added yet._\n";
            }

            $text .= "\n#### 🧭 **How to Manage this Module:**\n"
                   . "1. **Add Healthcare Facility**: Go to **[Healthcare Directory](/admin/healthcare_directory.php)**, click '+ Add Provider', enter doctor/hospital details, GPS coordinates, and discount %.\n"
                   . "2. **Issue Health Cards**: Visit **[Health Card Applications](/admin/health_card_applications.php)** to approve citizen applications and generate printable QR health cards.\n"
                   . "3. **Sign Doctor MOUs**: Open **[Doctor Agreements & MOUs](/admin/doctor_agreements.php)** to compose official bilateral partnership memorandums on letterhead.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Healthcare Directory', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user'],
                ['label' => 'Doctor Agreements & MOUs', 'url' => '/admin/doctor_agreements.php', 'icon' => 'fa-file-contract'],
                ['label' => 'Health Card Applications', 'url' => '/admin/health_card_applications.php', 'icon' => 'fa-id-card-clip'],
                ['label' => 'Beneficiaries Log', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child']
            ],
            'suggestions' => [
                'How to issue a new Health Card?',
                'Show beneficiaries and welfare assistance',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 8. BENEFICIARY & WELFARE MANAGEMENT
    // -------------------------------------------------------------
    if (preg_match('/(beneficiar|beneficiaries|labharthi|assistance|welfare|ration distribution|bpl|aid distribution|beneficiary report)/i', $q)) {
        $totalBeneficiaries = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiaries", 0);
        $activeBeneficiaries = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiaries WHERE status = 'Active'", 0);
        $assistedCount = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiaries WHERE status = 'Assisted'", 0);
        
        $totalAssistanceLogs = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiary_assistance", 0);
        $totalAidValue = (float)safeQueryScalar($pdo, "SELECT SUM(monetary_value) FROM beneficiary_assistance", 0);
        
        $categories = safeQueryAll($pdo, "SELECT c.category_name, COUNT(b.id) as total FROM beneficiary_categories c LEFT JOIN beneficiaries b ON c.id = b.category_id GROUP BY c.id ORDER BY total DESC LIMIT 5");
        $recentAssistance = safeQueryAll($pdo, "SELECT b.name, a.assistance_type, a.monetary_value, a.distribution_date FROM beneficiary_assistance a JOIN beneficiaries b ON a.beneficiary_id = b.id ORDER BY a.id DESC LIMIT 4");

        if ($isHinglish) {
            $text = "### 🤝 **Labharthi Prabandhan & Sahayata Vitaran Log**\n\n"
                  . "**Beneficiary Module** ke jariye aap zarooratmand parivaron, BPL, vidhwa, anath aur divyang labharthiyon ka record rakh sakte hain aur sahayata vitaran track kar sakte hain:\n\n"
                  . "#### 📊 **Labharthi Snapshot:**\n"
                  . "• **Kul Registered Labharthi**: `{$totalBeneficiaries}` (`{$activeBeneficiaries}` Active, `{$assistedCount}` Sahayata Prapt)\n"
                  . "• **Sahayata Vitaran Entries**: `{$totalAssistanceLogs}` records\n"
                  . "• **Kul Vitarit Sahayata Ka Mulya**: `₹" . number_format($totalAidValue, 2) . "`\n\n"
                  . "#### 🏷️ **Mukhya Labharthi Categories:**\n";

            if (!empty($categories)) {
                foreach ($categories as $cat) {
                    $text .= "• **" . htmlspecialchars($cat['category_name']) . "**: `{$cat['total']} labharthi`\n";
                }
            }

            if (!empty($recentAssistance)) {
                $text .= "\n#### 📦 **Haal Hi Me Di Gayi Sahayata:**\n"
                       . "| Labharthi Ka Naam | Sahayata Type | Rashi (₹) | Vitaran Date |\n| :--- | :--- | :--- | :--- |\n";
                foreach ($recentAssistance as $ast) {
                    $text .= "| **" . htmlspecialchars($ast['name']) . "** | " . htmlspecialchars($ast['assistance_type']) . " | ₹" . number_format((float)$ast['monetary_value'], 2) . " | " . date('d M Y', strtotime($ast['distribution_date'])) . " |\n";
                }
            }

            $text .= "\n#### 🧭 **Istemal Karne Ka Tarika:**\n"
                   . "1. **Naya Labharthi Jodein**: **[Beneficiaries](/admin/beneficiaries)** me jayein aur '+ Register Beneficiary' par click karein.\n"
                   . "2. **Sahayata Log Karein**: **[Assistance Log](/admin/beneficiary-assistance)** me jakar ration, medical kit ya chhatravritti ka distribution darj karein.\n"
                   . "3. **Report Nikalein**: **[Beneficiary Reports](/admin/beneficiary-reports)** se audit ke liye report download karein.";
        } else {
            $text = "### 🤝 **Beneficiary Management & Welfare Assistance Log**\n\n"
                  . "The **Beneficiary Management System** empowers your NGO to record underprivileged families, track assistance given (ration, medical aids, educational kits, wheelchairs), and generate audit-ready reports.\n\n"
                  . "#### 📊 **Beneficiary Data Overview:**\n"
                  . "• **Total Registered Beneficiaries**: `{$totalBeneficiaries}` (`{$activeBeneficiaries}` Active, `{$assistedCount}` Assisted)\n"
                  . "• **Total Aid Distribution Events**: `{$totalAssistanceLogs}` logged records\n"
                  . "• **Cumulative Estimated Aid Value**: `₹" . number_format($totalAidValue, 2) . "`\n\n"
                  . "#### 🏷️ **Top Beneficiary Categories:**\n";

            if (!empty($categories)) {
                foreach ($categories as $cat) {
                    $text .= "• **" . htmlspecialchars($cat['category_name']) . "**: `{$cat['total']} beneficiaries`\n";
                }
            }

            if (!empty($recentAssistance)) {
                $text .= "\n#### 📦 **Recent Assistance Distribution Entries:**\n"
                       . "| Beneficiary Name | Assistance Type | Value (₹) | Distribution Date |\n| :--- | :--- | :--- | :--- |\n";
                foreach ($recentAssistance as $ast) {
                    $text .= "| **" . htmlspecialchars($ast['name']) . "** | " . htmlspecialchars($ast['assistance_type']) . " | ₹" . number_format((float)$ast['monetary_value'], 2) . " | " . date('d M Y', strtotime($ast['distribution_date'])) . " |\n";
                }
            }

            $text .= "\n#### 🧭 **Operational Steps:**\n"
                   . "1. **Register Beneficiary**: Visit **[Beneficiaries](/admin/beneficiaries)** and click '+ Register Beneficiary' to record Aadhaar, photo, family details, and BPL category.\n"
                   . "2. **Log Aid Distribution**: Go to **[Assistance Log](/admin/beneficiary-assistance)** to record kits/ration delivered, field officer in charge, and distribution receipts.\n"
                   . "3. **Export Reports**: Open **[Beneficiary Reports](/admin/beneficiary-reports)** to download demographic and CSR compliance summaries.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Beneficiaries Directory', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child'],
                ['label' => 'Assistance Log', 'url' => '/admin/beneficiary-assistance', 'icon' => 'fa-hand-holding-heart'],
                ['label' => 'Beneficiary Reports', 'url' => '/admin/beneficiary-reports', 'icon' => 'fa-file-invoice'],
                ['label' => 'Healthcare Directory', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user']
            ],
            'suggestions' => [
                'Show healthcare directory and doctor MOUs',
                'Show item and in-kind donation categories',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 9. EXPENSES & FINANCIAL AUDIT / INCOME VS EXPENSE
    // -------------------------------------------------------------
    if (preg_match('/(expense|expenses|kharcha|spending|voucher|income vs expense|balance sheet|audit expense)/i', $q)) {
        $totalExpense = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM expenses", 0);
        $totalExpenseCount = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM expenses", 0);
        $thisMonthExpense = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM expenses WHERE DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')", 0);
        
        $totalDonations = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'", 0);
        $netSurplus = $totalDonations - $totalExpense;

        $categorySpending = safeQueryAll($pdo, "SELECT c.category_name, SUM(e.amount) as total_spent, COUNT(e.id) as vouchers FROM expense_categories c LEFT JOIN expenses e ON c.id = e.category_id GROUP BY c.id ORDER BY total_spent DESC");
        $recentExpenses = safeQueryAll($pdo, "SELECT e.expense_code, e.purpose, e.amount, e.payment_mode, e.date, c.category_name FROM expenses e LEFT JOIN expense_categories c ON e.category_id = c.id ORDER BY e.date DESC, e.id DESC LIMIT 4");

        if ($isHinglish) {
            $text = "### 💸 **Kharcha Tracker & Vittiya Audit (Income vs Expense)**\n\n"
                  . "**Expense Management Module** ke dwara NGO ke sabhi administrative kharche, field visit costs, staff allowances aur camp samagri ka hisab rakha jata hai:\n\n"
                  . "#### 📊 **Financial Accounting Summary:**\n"
                  . "• **Kul Kul Kharcha (All-Time)**: `₹" . number_format($totalExpense, 2) . "` ({$totalExpenseCount} vouchers)\n"
                  . "• **Is Mahine Ka Kharcha (" . date('F Y') . ")**: `₹" . number_format($thisMonthExpense, 2) . "`\n"
                  . "• **Kul Prapt Daan (Total Revenue)**: `₹" . number_format($totalDonations, 2) . "`\n"
                  . "• **⚖️ Net Balance**: `" . ($netSurplus >= 0 ? "Surplus (Bachat): ₹" . number_format($netSurplus, 2) : "Deficit (Ghata): -₹" . number_format(abs($netSurplus), 2)) . "`\n\n"
                  . "#### 🏷️ **Category-Wise Kharcha:**\n";

            if (!empty($categorySpending)) {
                $text .= "| Category | Kul Kharcha | Vouchers Count |\n| :--- | :--- | :--- |\n";
                foreach ($categorySpending as $cs) {
                    $spent = (float)($cs['total_spent'] ?? 0);
                    $text .= "| **" . htmlspecialchars($cs['category_name']) . "** | ₹" . number_format($spent, 2) . " | " . (int)$cs['vouchers'] . " |\n";
                }
            }

            if (!empty($recentExpenses)) {
                $text .= "\n#### 🕒 **Haal Hi Ke 4 Kharcha Vouchers:**\n"
                       . "| Code | Category | Purpose | Amount | Mode | Date |\n| :--- | :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentExpenses as $re) {
                    $text .= "| `" . htmlspecialchars($re['expense_code'] ?: 'EXP') . "` | " . htmlspecialchars($re['category_name'] ?: 'General') . " | " . htmlspecialchars(substr($re['purpose'], 0, 30)) . "... | **₹" . number_format((float)$re['amount'], 2) . "** | " . htmlspecialchars($re['payment_mode']) . " | " . date('d M Y', strtotime($re['date'])) . " |\n";
                }
            }

            $text .= "\n#### 🧭 **Quick Links & Actions:**\n"
                   . "• Naye voucher aur bill upload karein: **[Expense Tracker](/admin/expenses)**.\n"
                   . "• Monthly aay aur vyay ka graph dekhein: **[Income vs Expense](/admin/income-vs-expense)**.";
        } else {
            $text = "### 💸 **Expense Tracker & Financial Audit**\n\n"
                  . "The **Expense Management Module** keeps an accurate accounting log of operational expenditures, field visit costs, staff allowances, and relief camp materials.\n\n"
                  . "#### 📊 **Financial Accounting Summary:**\n"
                  . "• **Total All-Time Expenses**: `₹" . number_format($totalExpense, 2) . "` ({$totalExpenseCount} vouchers)\n"
                  . "• **This Month's Spending (" . date('F Y') . ")**: `₹" . number_format($thisMonthExpense, 2) . "`\n"
                  . "• **Total Revenue Raised**: `₹" . number_format($totalDonations, 2) . "`\n"
                  . "• **⚖️ Net Operational Balance**: `" . ($netSurplus >= 0 ? "Surplus: ₹" . number_format($netSurplus, 2) : "Deficit: -₹" . number_format(abs($netSurplus), 2)) . "`\n\n"
                  . "#### 🏷️ **Expenditure by Category:**\n";

            if (!empty($categorySpending)) {
                $text .= "| Category | Total Spent | Vouchers Count |\n| :--- | :--- | :--- |\n";
                foreach ($categorySpending as $cs) {
                    $spent = (float)($cs['total_spent'] ?? 0);
                    $text .= "| **" . htmlspecialchars($cs['category_name']) . "** | ₹" . number_format($spent, 2) . " | " . (int)$cs['vouchers'] . " |\n";
                }
            }

            if (!empty($recentExpenses)) {
                $text .= "\n#### 🕒 **Latest 4 Expense Vouchers:**\n"
                       . "| Code | Category | Purpose | Amount | Mode | Date |\n| :--- | :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentExpenses as $re) {
                    $text .= "| `" . htmlspecialchars($re['expense_code'] ?: 'EXP') . "` | " . htmlspecialchars($re['category_name'] ?: 'General') . " | " . htmlspecialchars(substr($re['purpose'], 0, 30)) . "... | **₹" . number_format((float)$re['amount'], 2) . "** | " . htmlspecialchars($re['payment_mode']) . " | " . date('d M Y', strtotime($re['date'])) . " |\n";
                }
            }

            $text .= "\n#### 🧭 **Quick Links & Actions:**\n"
                   . "• Add vouchers and bill uploads at **[Expense Tracker](/admin/expenses)**.\n"
                   . "• Compare monthly income vs expenditure charts at **[Income vs Expense](/admin/income-vs-expense)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Expense Tracker', 'url' => '/admin/expenses', 'icon' => 'fa-receipt'],
                ['label' => 'Income vs Expense', 'url' => '/admin/income-vs-expense', 'icon' => 'fa-scale-balanced'],
                ['label' => 'Donations Panel', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Reports Panel', 'url' => '/admin/reports', 'icon' => 'fa-chart-column']
            ],
            'suggestions' => [
                'Show donation analytics and revenue',
                'Show custom receipts summary',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 10. CUSTOM RECEIPTS & 80G OFFLINE RECEIPT BOOK
    // -------------------------------------------------------------
    if (preg_match('/(custom receipt|offline receipt|manual receipt|receipt book|generate custom receipt|kaccha receipt|offline donation)/i', $q)) {
        $totalCustomReceipts = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM custom_receipts", 0);
        $totalCustomAmount = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM custom_receipts WHERE status = 'generated'", 0);
        $recentReceipts = safeQueryAll($pdo, "SELECT receipt_no, payer_name, amount, payment_mode, date FROM custom_receipts ORDER BY id DESC LIMIT 5");

        if ($isHinglish) {
            $text = "### 🧾 **Custom 80G Receipts & Offline Receipt Book**\n\n"
                  . "**Custom Receipts Module** ke jariye aap offline cash, cheque, bank transfer ya karyakram ke daan ke liye official 80G tax exemption receipt generate kar sakte hain:\n\n"
                  . "#### 📊 **Offline Custom Receipts Stats:**\n"
                  . "• **Kul Jari Ki Gayi Receipts**: `{$totalCustomReceipts}` receipts\n"
                  . "• **Kul Offline Daan Rashi**: `₹" . number_format($totalCustomAmount, 2) . "`\n\n";

            if (!empty($recentReceipts)) {
                $text .= "#### 🕒 **Haal Hi Ki Generated Receipts:**\n"
                       . "| Receipt No | Daan Data Ka Naam | Rashi | Mode | Date |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentReceipts as $cr) {
                    $text .= "| `" . htmlspecialchars($cr['receipt_no']) . "` | " . htmlspecialchars($cr['payer_name']) . " | **₹" . number_format((float)$cr['amount'], 2) . "** | " . htmlspecialchars($cr['payment_mode']) . " | " . date('d M Y', strtotime($cr['date'])) . " |\n";
                }
            }

            $text .= "\n👉 Nayi branded receipt banane ke liye **[Generate Custom Receipt](/admin/generate_custom_receipt.php)** par jayein.";
        } else {
            $text = "### 🧾 **Custom 80G Receipts & Offline Receipt Book**\n\n"
                  . "The **Custom Receipts Module** allows administrators and coordinators to generate branded, numbered 80G tax-exempt receipts for offline cash, cheque, bank transfer, or event contributions.\n\n"
                  . "#### 📊 **Offline Custom Receipts Stats:**\n"
                  . "• **Total Custom Receipts Issued**: `{$totalCustomReceipts}` receipts\n"
                  . "• **Total Offline Funds Documented**: `₹" . number_format($totalCustomAmount, 2) . "`\n\n";

            if (!empty($recentReceipts)) {
                $text .= "#### 🕒 **Recent Generated Custom Receipts:**\n"
                       . "| Receipt No | Payer / Donor Name | Amount | Mode | Date |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentReceipts as $cr) {
                    $text .= "| `" . htmlspecialchars($cr['receipt_no']) . "` | " . htmlspecialchars($cr['payer_name']) . " | **₹" . number_format((float)$cr['amount'], 2) . "** | " . htmlspecialchars($cr['payment_mode']) . " | " . date('d M Y', strtotime($cr['date'])) . " |\n";
                }
            }

            $text .= "\n👉 You can create a new branded receipt with auto amount-in-words at **[Generate Custom Receipt](/admin/generate_custom_receipt.php)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Generate Custom Receipt', 'url' => '/admin/generate_custom_receipt.php', 'icon' => 'fa-receipt'],
                ['label' => 'Online Donations', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Donation Analytics', 'url' => '/admin/donation-analytics', 'icon' => 'fa-chart-pie']
            ],
            'suggestions' => [
                'Show online donation revenue',
                'Show recurring donations / auto pay',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 11. AUTO-PAY / RECURRING DONATION SUBSCRIPTIONS
    // -------------------------------------------------------------
    if (preg_match('/(recurring|auto pay|subscription|monthly donor|autopay|mandate|razorpay plan)/i', $q)) {
        $totalSubscriptions = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM recurring_donations", 0);
        $activeSubscriptions = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM recurring_donations WHERE status = 'active'", 0);
        $recurringRevenue = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM recurring_donations WHERE status = 'active'", 0);
        $recentRec = safeQueryAll($pdo, "SELECT donor_name, amount, frequency, status, next_charge_date FROM recurring_donations ORDER BY id DESC LIMIT 4");

        if ($isHinglish) {
            $text = "### 🔄 **Auto-Pay & Monthly Recurring Daan Mandates**\n\n"
                  . "**Recurring Donations Module** ke jariye monthly/quarterly daan Razorpay Subscriptions aur UPI AutoPay se automate hota hai:\n\n"
                  . "#### 📊 **Subscription Overview:**\n"
                  . "• **Kul Subscriptions**: `{$totalSubscriptions}`\n"
                  . "• **Active Monthly Mandates**: `{$activeSubscriptions}` active daan-data\n"
                  . "• **Anumanit Monthly Recurring Daan**: `₹" . number_format($recurringRevenue, 2) . " / mahina`\n\n";

            if (!empty($recentRec)) {
                $text .= "#### 🕒 **Haal Hi Ke Recurring Donors:**\n"
                       . "| Donor Ka Naam | Rashi | Frequency | Status | Agla Charge |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentRec as $r) {
                    $statusEmoji = $r['status'] === 'active' ? '🟢 Active' : '⏳ ' . ucfirst($r['status']);
                    $text .= "| " . htmlspecialchars($r['donor_name']) . " | ₹" . number_format((float)$r['amount'], 2) . " | " . ucfirst($r['frequency']) . " | {$statusEmoji} | " . ($r['next_charge_date'] ? date('d M Y', strtotime($r['next_charge_date'])) : 'Pending') . " |\n";
                }
            }

            $text .= "\n👉 Mandates manage karne ke liye **[Auto Pay / Recurring](/admin/recurring-donations)** par jayein.";
        } else {
            $text = "### 🔄 **Recurring Donations & Auto-Pay Mandates**\n\n"
                  . "The **Recurring Donations Module** automates periodic giving (Monthly, Quarterly, Yearly) via Razorpay Subscription Mandates and UPI AutoPay.\n\n"
                  . "#### 📊 **Subscription Overview:**\n"
                  . "• **Total Subscriptions Created**: `{$totalSubscriptions}`\n"
                  . "• **Active Monthly Mandates**: `{$activeSubscriptions}` active donors\n"
                  . "• **Expected Recurring Monthly Revenue**: `₹" . number_format($recurringRevenue, 2) . " / month`\n\n";

            if (!empty($recentRec)) {
                $text .= "#### 🕒 **Recent Recurring Donors:**\n"
                       . "| Donor Name | Amount | Frequency | Status | Next Charge |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentRec as $r) {
                    $statusEmoji = $r['status'] === 'active' ? '🟢 Active' : '⏳ ' . ucfirst($r['status']);
                    $text .= "| " . htmlspecialchars($r['donor_name']) . " | ₹" . number_format((float)$r['amount'], 2) . " | " . ucfirst($r['frequency']) . " | {$statusEmoji} | " . ($r['next_charge_date'] ? date('d M Y', strtotime($r['next_charge_date'])) : 'Pending') . " |\n";
                }
            }

            $text .= "\n👉 Manage plans, customer mandates, and charge sync at **[Auto Pay / Recurring](/admin/recurring-donations)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Manage Recurring Donations', 'url' => '/admin/recurring-donations', 'icon' => 'fa-arrows-rotate'],
                ['label' => 'All Donations', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Payment Settings', 'url' => '/admin/settings?tab=payments', 'icon' => 'fa-gear']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 12. ITEM & IN-KIND DONATIONS
    // -------------------------------------------------------------
    if (preg_match('/(item donation|in-kind|\bration\b|dry ration|clothes|clothes donation|books donation|blanket|wheelchair|food materials|samaan|in kind)/i', $q)) {
        $totalItems = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM item_donations", 0);
        $totalCategories = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM item_donation_categories WHERE is_active = 1", 0);
        $categoriesList = safeQueryAll($pdo, "SELECT category_name, category_icon, unit_suggestions FROM item_donation_categories WHERE is_active = 1 ORDER BY display_order ASC");

        if ($isHinglish) {
            $text = "### 📦 **Samaan Ka Daan (Item & In-Kind Donations)**\n\n"
                  . "**Item Donations Module** ke jariye kapde, anaj ration kit, pustakein, medical equipment, kambal aur wheelchairs ka record rakha jata hai:\n\n"
                  . "#### 📊 **In-Kind Inventory Snapshot:**\n"
                  . "• **Active Item Categories**: `{$totalCategories}` categories\n"
                  . "• **Kul Item Daan Entries**: `{$totalItems}` records\n\n"
                  . "#### 🏷️ **Available Item Categories:**\n";

            if (!empty($categoriesList)) {
                foreach ($categoriesList as $cat) {
                    $text .= "• **" . htmlspecialchars($cat['category_name']) . "** (Units: `" . htmlspecialchars($cat['unit_suggestions'] ?: 'pcs, kg') . "`)\n";
                }
            }

            $text .= "\n👉 Categories aur units configure karne ke liye **[Item Categories](/admin/item-donation-categories)** par jayein.";
        } else {
            $text = "### 📦 **Item & In-Kind Donations Management**\n\n"
                  . "The **Item Donations Module** facilitates non-monetary giving such as clothes, dry ration kits, school textbooks, medical consumables, winter blankets, wheelchairs, and cooked meals.\n\n"
                  . "#### 📊 **In-Kind Inventory & Records:**\n"
                  . "• **Active Item Categories**: `{$totalCategories}` categories\n"
                  . "• **Total Item Donations Logged**: `{$totalItems}` records\n\n"
                  . "#### 🏷️ **Configured Item Categories:**\n";

            if (!empty($categoriesList)) {
                foreach ($categoriesList as $cat) {
                    $text .= "• **" . htmlspecialchars($cat['category_name']) . "** (Units: `" . htmlspecialchars($cat['unit_suggestions'] ?: 'pcs, kg') . "`)\n";
                }
            }

            $text .= "\n👉 Configure item types, units, and inventory categories in **[Item Categories](/admin/item-donation-categories)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Item Donation Categories', 'url' => '/admin/item-donation-categories', 'icon' => 'fa-boxes-stacked'],
                ['label' => 'Beneficiary Assistance', 'url' => '/admin/beneficiary-assistance', 'icon' => 'fa-hand-holding-heart']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 13. JOB OPENINGS, CAREERS & RECRUITMENT
    // -------------------------------------------------------------
    if (preg_match('/(job|jobs|career|vacancy|vacancies|recruitment|naukri|coordinator job|job application|applicant|resume|cv|hiring)/i', $q)) {
        $totalJobs = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM job_openings", 0);
        $activeJobs = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM job_openings WHERE status = 'active'", 0);
        $totalApplications = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM job_applications", 0);
        $pendingScreening = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM job_applications WHERE status = 'pending' OR status = 'under_review'", 0);
        
        $jobList = safeQueryAll($pdo, "SELECT job_code, title, category, location, openings_count, status FROM job_openings ORDER BY id DESC LIMIT 5");
        $recentApplicants = safeQueryAll($pdo, "SELECT a.applicant_name, a.contact, a.state, a.district, a.status, j.title as job_title FROM job_applications a LEFT JOIN job_openings j ON a.job_id = j.id ORDER BY a.id DESC LIMIT 4");

        if ($isHinglish) {
            $text = "### 💼 **Naukri, Vacancies & Coordinator Bharti (Jobs & Recruitment)**\n\n"
                  . "**Jobs & Careers Module** ke jariye aap State, District, Block aur Panchayat Coordinator vacancies post kar sakte hain aur candidate resumes screen kar sakte hain:\n\n"
                  . "#### 📊 **Hiring & Application Snapshot:**\n"
                  . "• **Active Job Vacancies**: `{$activeJobs}` active roles (Total: `{$totalJobs}`)\n"
                  . "• **Kul Prapt Job Applications**: `{$totalApplications}` applicants\n"
                  . "• **⏳ Awaiting Screening / Review**: `{$pendingScreening}` candidates\n\n";

            if (!empty($jobList)) {
                $text .= "#### 📋 **Mukhya Khali Vacancies:**\n"
                       . "| Code | Post / Role | Location | Openings | Status |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($jobList as $jb) {
                    $st = $jb['status'] === 'active' ? '🟢 Active' : '⚪ ' . ucfirst($jb['status']);
                    $text .= "| `" . htmlspecialchars($jb['job_code']) . "` | **" . htmlspecialchars($jb['title']) . "** | " . htmlspecialchars($jb['location']) . " | " . (int)$jb['openings_count'] . " | {$st} |\n";
                }
            }

            if (!empty($recentApplicants)) {
                $text .= "\n#### 🧑‍💼 **Haal Hi Ke Job Applicants:**\n"
                       . "| Candidate Ka Naam | Post | District / State | Contact | Status |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentApplicants as $app) {
                    $text .= "| **" . htmlspecialchars($app['applicant_name']) . "** | " . htmlspecialchars($app['job_title'] ?: 'General') . " | " . htmlspecialchars($app['district'] . ', ' . $app['state']) . " | " . htmlspecialchars($app['contact']) . " | " . ucfirst(htmlspecialchars($app['status'])) . " |\n";
                }
            }

            $text .= "\n#### 🧭 **Bharti Manage Karne Ka Tarika:**\n"
                   . "• Nayi vacancy create karein: **[Job Postings](/admin/jobs.php)**.\n"
                   . "• Applicant CV check karein aur shortlist karein: **[Job Applications](/admin/job_applications.php)**.";
        } else {
            $text = "### 💼 **Job Openings, Career Recruitment & Coordinator Hiring**\n\n"
                  . "The **Jobs & Career Management System** allows your NGO to publish vacancies for State, District, Block, and Panchayat Coordinators, screen applicant resumes, and track recruitment pipelines.\n\n"
                  . "#### 📊 **Hiring & Application Metrics:**\n"
                  . "• **Active Job Openings**: `{$activeJobs}` active roles (Total: `{$totalJobs}`)\n"
                  . "• **Total Job Applications Received**: `{$totalApplications}` applicants\n"
                  . "• **⏳ Awaiting Screening / Interview**: `{$pendingScreening}` candidates\n\n";

            if (!empty($jobList)) {
                $text .= "#### 📋 **Current Vacancies:**\n"
                       . "| Code | Designation / Role | Location | Openings | Status |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($jobList as $jb) {
                    $st = $jb['status'] === 'active' ? '🟢 Active' : '⚪ ' . ucfirst($jb['status']);
                    $text .= "| `" . htmlspecialchars($jb['job_code']) . "` | **" . htmlspecialchars($jb['title']) . "** | " . htmlspecialchars($jb['location']) . " | " . (int)$jb['openings_count'] . " | {$st} |\n";
                }
            }

            if (!empty($recentApplicants)) {
                $text .= "\n#### 🧑‍💼 **Recent Job Applicants:**\n"
                       . "| Candidate Name | Applied For | District / State | Contact | Status |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentApplicants as $app) {
                    $text .= "| **" . htmlspecialchars($app['applicant_name']) . "** | " . htmlspecialchars($app['job_title'] ?: 'General') . " | " . htmlspecialchars($app['district'] . ', ' . $app['state']) . " | " . htmlspecialchars($app['contact']) . " | " . ucfirst(htmlspecialchars($app['status'])) . " |\n";
                }
            }

            $text .= "\n#### 🧭 **How to Manage Hiring:**\n"
                   . "• Publish new coordinator openings at **[Job Postings](/admin/jobs.php)**.\n"
                   . "• Review applicant CVs and mark shortlist/interview status at **[Job Applications](/admin/job_applications.php)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Job Postings', 'url' => '/admin/jobs.php', 'icon' => 'fa-briefcase'],
                ['label' => 'Job Applications (' . $totalApplications . ')', 'url' => '/admin/job_applications.php', 'icon' => 'fa-users-viewfinder'],
                ['label' => 'HR Workplace Policies', 'url' => '/admin/hr_policies.php', 'icon' => 'fa-book-open-reader']
            ],
            'suggestions' => [
                'Show HR workplace policies',
                'Show staff and volunteer letters',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 14. HR POLICIES & WORKPLACE GOVERNANCE
    // -------------------------------------------------------------
    if (preg_match('/(hr policy|hr policies|code of conduct|posh|sexual harassment|child safeguarding|leave policy|whistleblower|travel allowance|staff rules|workplace policy)/i', $q)) {
        $totalPolicies = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM hr_policies", 0);
        $publicPolicies = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM hr_policies WHERE is_public = 1", 0);
        $policies = safeQueryAll($pdo, "SELECT policy_code, title, category, policy_version, is_public FROM hr_policies ORDER BY sort_order ASC, id ASC");

        if ($isHinglish) {
            $text = "### 📜 **HR Workplace Niyam & Governance Policies**\n\n"
                  . "**HR Policies Module** me NGO ke statutory compliance manuals, Code of Conduct, POSH Act, Child Protection aur Leave rules save rehte hain:\n\n"
                  . "#### 📊 **Policy Governance Stats:**\n"
                  . "• **Kul Darj Policies**: `{$totalPolicies}` policies (`{$publicPolicies}` staff/public download ke liye published)\n\n"
                  . "#### 📋 **Core Policy Repository:**\n";

            if (!empty($policies)) {
                $text .= "| Policy Code | Policy Title | Category | Version | Status |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($policies as $pol) {
                    $pub = $pol['is_public'] ? '✅ Published' : '🔒 Internal';
                    $text .= "| `" . htmlspecialchars($pol['policy_code']) . "` | **" . htmlspecialchars($pol['title']) . "** | `" . htmlspecialchars($pol['category']) . "` | " . htmlspecialchars($pol['policy_version'] ?: 'v1.0') . " | {$pub} |\n";
                }
            }

            $text .= "\n👉 Policies update karne ya signed PDF upload karne ke liye **[HR Policies](/admin/hr_policies.php)** par jayein.";
        } else {
            $text = "### 📜 **HR Workplace Policies & Governance Framework**\n\n"
                  . "The **HR Policies Module** maintains statutory compliance manuals and organizational codes of conduct for all NGO staff, interns, coordinators, and volunteers.\n\n"
                  . "#### 📊 **Policy Governance Stats:**\n"
                  . "• **Total Registered Policies**: `{$totalPolicies}` policies (`{$publicPolicies}` published for public/staff download)\n\n"
                  . "#### 📋 **Core Policy Repository:**\n";

            if (!empty($policies)) {
                $text .= "| Policy Code | Policy Title | Category | Version | Status |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($policies as $pol) {
                    $pub = $pol['is_public'] ? '✅ Published' : '🔒 Internal';
                    $text .= "| `" . htmlspecialchars($pol['policy_code']) . "` | **" . htmlspecialchars($pol['title']) . "** | `" . htmlspecialchars($pol['category']) . "` | " . htmlspecialchars($pol['policy_version'] ?: 'v1.0') . " | {$pub} |\n";
                }
            }

            $text .= "\n👉 Manage policy content, update versions, and upload official signed PDF documents at **[HR Policies](/admin/hr_policies.php)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Manage HR Policies', 'url' => '/admin/hr_policies.php', 'icon' => 'fa-book-open-reader'],
                ['label' => 'Staff Letters', 'url' => '/admin/staff_letters.php', 'icon' => 'fa-user-pen'],
                ['label' => 'Job Postings', 'url' => '/admin/jobs.php', 'icon' => 'fa-briefcase']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 15. CAREER GUIDANCE & VOCATIONAL SKILL COURSES CMS
    // -------------------------------------------------------------
    if (preg_match('/(career guidance|skill course|vocational training|counseling|student course|skill enrollment|free course)/i', $q)) {
        $totalCourses = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM skill_courses", 0);
        $activeCourses = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM skill_courses WHERE status = 'active'", 0);
        $totalEnrollments = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM skill_course_enrollments", 0);
        $courses = safeQueryAll($pdo, "SELECT title, category, duration, mode, fee_type, status FROM skill_courses ORDER BY id DESC LIMIT 5");

        if ($isHinglish) {
            $text = "### 🎓 **Career Guidance CMS & Hunar (Skill) Courses**\n\n"
                  . "**Career Guidance Module** ke jariye yuvaon ko computer, soft skills, competitive exam coaching aur free career counseling courses pradan kiye jate hain:\n\n"
                  . "#### 📊 **Skill Course Metrics:**\n"
                  . "• **Available Courses**: `{$activeCourses}` Active (Total `{$totalCourses}`)\n"
                  . "• **Student Enrollments**: `{$totalEnrollments}` yuva registered\n\n";

            if (!empty($courses)) {
                $text .= "#### 📚 **Mukhya Skill Training Courses:**\n"
                       . "| Course Ka Naam | Category | Duration | Mode | Fee Type | Status |\n| :--- | :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($courses as $c) {
                    $text .= "| **" . htmlspecialchars($c['title']) . "** | " . htmlspecialchars($c['category']) . " | " . htmlspecialchars($c['duration']) . " | " . htmlspecialchars($c['mode']) . " | " . htmlspecialchars($c['fee_type']) . " | " . ucfirst(htmlspecialchars($c['status'])) . " |\n";
                }
            }

            $text .= "\n👉 Courses aur student counseling requests manage karne ke liye **[Career Guidance CMS](/admin/career_guidance_manager.php)** par jayein.";
        } else {
            $text = "### 🎓 **Career Guidance CMS & Skill Training Courses**\n\n"
                  . "The **Career Guidance Module** empowers youth with vocational modules (IT/Computers, Soft Skills, Competitive Exam Coaching, Healthcare Aid) and free career counseling.\n\n"
                  . "#### 📊 **Skill Course Metrics:**\n"
                  . "• **Available Courses**: `{$activeCourses}` Active (Total `{$totalCourses}`)\n"
                  . "• **Student Course Applications**: `{$totalEnrollments}` youth enrollments\n\n";

            if (!empty($courses)) {
                $text .= "#### 📚 **Offered Skill Training Courses:**\n"
                       . "| Course Name | Category | Duration | Mode | Fee Type | Status |\n| :--- | :--- | :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($courses as $c) {
                    $text .= "| **" . htmlspecialchars($c['title']) . "** | " . htmlspecialchars($c['category']) . " | " . htmlspecialchars($c['duration']) . " | " . htmlspecialchars($c['mode']) . " | " . htmlspecialchars($c['fee_type']) . " | " . ucfirst(htmlspecialchars($c['status'])) . " |\n";
                }
            }

            $text .= "\n👉 Manage courses, syllabus, and student counseling requests at **[Career Guidance CMS](/admin/career_guidance_manager.php)**.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Career Guidance CMS', 'url' => '/admin/career_guidance_manager.php', 'icon' => 'fa-graduation-cap'],
                ['label' => 'Student Ambassador Program', 'url' => '/admin/student-directory', 'icon' => 'fa-user-graduate']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 16. STUDENT AMBASSADOR (SA) & YOUTH WING
    // -------------------------------------------------------------
    if (preg_match('/(student|student ambassador|ambassador|campus ambassador|student task|student submission|referral|vendor lead|point rules|penalty|student points|student certificate)/i', $q)) {
        $totalStudents = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_students", 0);
        $activeStudents = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_students WHERE status = 'active'", 0);
        $totalTasks = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_tasks", 0);
        $pendingSubmissions = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_submissions WHERE status = 'pending'", 0);
        $pendingReferrals = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_referrals WHERE status = 'pending'", 0);
        $totalVendorLeads = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_vendor_leads", 0);

        $text = "### 🎓 **Student Ambassador Program & Youth Network**\n\n"
              . "The **Student Ambassador Wing** empowers college students to champion social awareness, complete community tasks, earn gamified points, and obtain internship certifications.\n\n"
              . "#### 📊 **Student Ambassador Metrics:**\n"
              . "• **Enrolled Campus Ambassadors**: `{$totalStudents}` (`{$activeStudents}` Active)\n"
              . "• **Active Campaign Tasks**: `{$totalTasks}` tasks assigned\n"
              . "• **⏳ Task Submissions to Grade**: `{$pendingSubmissions}` pending reviews\n"
              . "• **🔗 Referral Verifications**: `{$pendingReferrals}` pending member referrals\n"
              . "• **🤝 Merchant / Vendor Leads**: `{$totalVendorLeads}` onboarded leads\n\n"
              . "#### 🧭 **Student Module Navigation:**\n"
              . "• **[Student Directory](/admin/student-directory)**: Ambassador roster, colleges, points balance, and contact cards.\n"
              . "• **[Campaign Tasks](/admin/student-tasks)**: Create social awareness drives, health camp duties, and donation targets.\n"
              . "• **[Task Submissions](/admin/student-submissions)**: Grade proof photos/links and award points.\n"
              . "• **[Referral Verification](/admin/student-referrals)**: Validate student-referred memberships and payments.\n"
              . "• **[Student Certificates](/admin/student-certificates)**: Issue merit internship certificates and LORs.\n"
              . "• **[Point Rules & Penalties](/admin/student-point-rules)**: Set gamification point values and penalty deduction rules.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Student Directory', 'url' => '/admin/student-directory', 'icon' => 'fa-graduation-cap'],
                ['label' => 'Campaign Tasks', 'url' => '/admin/student-tasks', 'icon' => 'fa-tasks'],
                ['label' => 'Submissions (' . $pendingSubmissions . ')', 'url' => '/admin/student-submissions', 'icon' => 'fa-clipboard-check'],
                ['label' => 'Referrals (' . $pendingReferrals . ')', 'url' => '/admin/student-referrals', 'icon' => 'fa-share-nodes'],
                ['label' => 'Student Certificates', 'url' => '/admin/student-certificates', 'icon' => 'fa-award']
            ],
            'suggestions' => [
                'How are student points awarded?',
                'Show volunteer management',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 17. FIELD AGENTS, DAILY ATTENDANCE & PAYROLL
    // -------------------------------------------------------------
    if (preg_match('/(field agent|field agents|field collector|agent attendance|punch in|cash deposit|agent payroll|commission|field staff)/i', $q)) {
        $totalAgents = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM field_agents", 0);
        $activeAgents = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM field_agents WHERE status = 'Active' OR status = 'active'", 0);
        $todayAttendance = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM agent_attendance WHERE punch_date = CURDATE()", 0);
        $pendingDeposits = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM cash_deposits WHERE status = 'Pending'", 0);
        $pendingDepositAmount = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM cash_deposits WHERE status = 'Pending'", 0);

        $text = "### 🏃 **Field Agent Management, GPS Attendance & Payroll**\n\n"
              . "The **Field Operations Module** oversees grassroots field collectors, daily mobile GPS punch-in/punch-out attendance, offline cash donation collections, and payroll reconciliation.\n\n"
              . "#### 📊 **Field Operations Snapshot:**\n"
              . "• **Registered Field Agents**: `{$totalAgents}` (`{$activeAgents}` Active)\n"
              . "• **Today's Attendance Punches**: `{$todayAttendance}` agents checked-in today\n"
              . "• **⏳ Pending Cash Bank Deposits**: `{$pendingDeposits}` deposits (`₹" . number_format($pendingDepositAmount, 2) . "` awaiting reconciliation)\n\n"
              . "#### 🧭 **Field Agent Workflows:**\n"
              . "• **[Field Agents](/admin/field-agents.php)**: Manage agent profiles, assigned territories, target collections, and status.\n"
              . "• **[Daily Attendance](/admin/agent-attendance.php)**: Monitor real-time GPS check-ins, punch timestamps, and working hours.\n"
              . "• **[Cash Deposits](/admin/cash-deposits.php)**: Verify offline cash handovers into the NGO's official bank account.\n"
              . "• **[Agent Payroll](/admin/agent-payroll.php)**: Compute monthly base salary, performance commissions, and disbursement slips.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Field Agents', 'url' => '/admin/field-agents.php', 'icon' => 'fa-person-walking'],
                ['label' => 'Daily Attendance', 'url' => '/admin/agent-attendance.php', 'icon' => 'fa-clipboard-user'],
                ['label' => 'Cash Deposits (' . $pendingDeposits . ')', 'url' => '/admin/cash-deposits.php', 'icon' => 'fa-money-bill-transfer'],
                ['label' => 'Agent Payroll', 'url' => '/admin/agent-payroll.php', 'icon' => 'fa-file-invoice-dollar']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 18. VOLUNTEERS & FIELD ACTIVITIES
    // -------------------------------------------------------------
    if (preg_match('/(volunteer|volunteers|swayamsevak|approval|pending volunteer|volunteer activity|volunteer hours)/i', $q)) {
        $totalVolunteers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers", 0);
        $activeVolunteers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Active'", 0);
        $pendingVolunteers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Pending'", 0);
        $pendingList = safeQueryAll($pdo, "SELECT id, name, email, phone, district, created_at FROM volunteers WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 5");

        $text = "### 👥 **Volunteers Management & Activities**\n\n"
              . "• **Total Registered Volunteers**: `{$totalVolunteers}`\n"
              . "• **Active Volunteers**: `{$activeVolunteers}`\n"
              . "• **Pending Applications for Approval**: `{$pendingVolunteers}`\n\n";

        if (!empty($pendingList)) {
            $text .= "#### ⚠️ **Pending Volunteer Applications (Needs Review):**\n"
                   . "| Name | District | Contact | Applied Date |\n| :--- | :--- | :--- | :--- |\n";
            foreach ($pendingList as $v) {
                $text .= "| " . htmlspecialchars($v['name']) . " | " . htmlspecialchars($v['district'] ?: 'N/A') . " | " . htmlspecialchars($v['phone']) . " | " . date('d M Y', strtotime($v['created_at'])) . " |\n";
            }
            $text .= "\n👉 You can approve or decline them directly in the **[Volunteers Management Panel](/admin/volunteers?status=Pending)**.";
        } else {
            $text .= "✅ **All volunteer applications are up to date!** No pending approvals at the moment.";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Review Pending (' . $pendingVolunteers . ')', 'url' => '/admin/volunteers?status=Pending', 'icon' => 'fa-user-clock'],
                ['label' => 'All Volunteers', 'url' => '/admin/volunteers', 'icon' => 'fa-users'],
                ['label' => 'Volunteer Activities', 'url' => '/admin/volunteer-activities', 'icon' => 'fa-list-check'],
                ['label' => 'Join Applications', 'url' => '/admin/join-applications', 'icon' => 'fa-id-card']
            ],
            'suggestions' => [
                'Show active members count',
                'Show student ambassador operations',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 19. MEMBERSHIPS & MEMBER INSIGHTS
    // -------------------------------------------------------------
    if (preg_match('/(member|members|membership|sadasya|birthday|id card|member id|member message)/i', $q) && !preg_match('/(management)/i', $q)) {
        $totalMembers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM members", 0);
        $activeMembers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM members WHERE status = 'Active'", 0);
        $recentMembers = safeQueryAll($pdo, "SELECT member_no, full_name, email, phone, created_at FROM members ORDER BY created_at DESC LIMIT 5");
        $todayBirthdays = safeQueryAll($pdo, "SELECT full_name, member_no, phone FROM members WHERE status='Active' AND dob IS NOT NULL AND DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d') ORDER BY full_name ASC");

        $text = "### 🪪 **Membership & Member Insights**\n\n"
              . "• **Total Members**: `{$totalMembers}`\n"
              . "• **Active Members**: `{$activeMembers}`\n";

        if (!empty($todayBirthdays)) {
            $text .= "\n🎂 **Today's Member Birthdays (" . count($todayBirthdays) . "):**\n";
            foreach ($todayBirthdays as $b) {
                $text .= "• **" . htmlspecialchars($b['full_name']) . "** (" . htmlspecialchars($b['member_no']) . ") - Phone: `" . htmlspecialchars($b['phone']) . "`\n";
            }
        } else {
            $text .= "• **Today's Birthdays**: No member birthdays today.\n";
        }

        if (!empty($recentMembers)) {
            $text .= "\n#### 🆕 **Recent Member Registrations:**\n"
                   . "| Member No | Name | Contact | Joined Date |\n| :--- | :--- | :--- | :--- |\n";
            foreach ($recentMembers as $m) {
                $text .= "| `" . htmlspecialchars($m['member_no']) . "` | " . htmlspecialchars($m['full_name']) . " | " . htmlspecialchars($m['phone']) . " | " . date('d M Y', strtotime($m['created_at'])) . " |\n";
            }
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Manage Members', 'url' => '/admin/memberships', 'icon' => 'fa-id-card'],
                ['label' => 'Document Studio', 'url' => '/admin/document-studio', 'icon' => 'fa-file-signature'],
                ['label' => 'Member Messages', 'url' => '/admin/member-messages', 'icon' => 'fa-envelope']
            ],
            'suggestions' => [
                'Show volunteer status',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 20. GRIEVANCES, COMPLAINTS & SUGGESTIONS
    // -------------------------------------------------------------
    if (preg_match('/(complaint|complaints|shikayat|grievance|ticket|tickets|suggestion|suggestions)/i', $q)) {
        $totalComplaints = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM complaints", 0);
        $pendingComplaints = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM complaints WHERE status = 'pending'", 0);
        $inProgress = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM complaints WHERE status = 'in_progress'", 0);
        $resolved = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM complaints WHERE status = 'resolved'", 0);
        $recentTickets = safeQueryAll($pdo, "SELECT ticket_no, name, subject, priority, status, created_at FROM complaints ORDER BY id DESC LIMIT 5");

        $text = "### 📢 **Complaints & Grievance Redressal System**\n\n"
              . "The **Complaints Module** enables beneficiaries, members, and citizens to raise tickets with tracking IDs, priorities, and administrative resolution notes.\n\n"
              . "#### 📊 **Grievance Ticket Overview:**\n"
              . "• **Total Tickets Received**: `{$totalComplaints}`\n"
              . "• **⏳ Pending Review**: `{$pendingComplaints}` tickets\n"
              . "• **🔄 In-Progress**: `{$inProgress}` tickets\n"
              . "• **✅ Resolved Tickets**: `{$resolved}` tickets\n\n";

        if (!empty($recentTickets)) {
            $text .= "#### 📋 **Recent Grievance Tickets:**\n"
                   . "| Ticket ID | Submitter Name | Subject | Priority | Status |\n| :--- | :--- | :--- | :--- |\n";
            foreach ($recentTickets as $tk) {
                $prio = $tk['priority'] === 'urgent' ? '🔴 Urgent' : ($tk['priority'] === 'high' ? '🟠 High' : '🟡 ' . ucfirst($tk['priority']));
                $text .= "| `" . htmlspecialchars($tk['ticket_no']) . "` | " . htmlspecialchars($tk['name']) . " | " . htmlspecialchars(substr($tk['subject'], 0, 30)) . "... | {$prio} | " . ucfirst($tk['status']) . " |\n";
            }
        }

        $text .= "\n👉 Respond to tickets and update resolution status at **[Complaints & Suggestions](/admin/complaints)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'View Complaints (' . $pendingComplaints . ')', 'url' => '/admin/complaints', 'icon' => 'fa-comments'],
                ['label' => 'Staff & Member Feedback', 'url' => '/admin/feedbacks.php', 'icon' => 'fa-comment-dots'],
                ['label' => 'Inquiries', 'url' => '/admin/inquiries', 'icon' => 'fa-circle-question']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 21. FEEDBACK & STAKEHOLDER RATINGS
    // -------------------------------------------------------------
    if (preg_match('/(feedback|feedbacks|rating|review|pratikriya|employee feedback|member feedback)/i', $q)) {
        $totalFeedback = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM feedbacks", 0);
        $avgRating = (float)safeQueryScalar($pdo, "SELECT AVG(rating) FROM feedbacks", 5.0);
        $pendingFeedback = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM feedbacks WHERE status = 'pending'", 0);
        $recentFeedbacks = safeQueryAll($pdo, "SELECT feedback_no, name, submitter_type, rating, subject, status FROM feedbacks ORDER BY id DESC LIMIT 4");

        $text = "### 💬 **Stakeholder, Member & Employee Feedback**\n\n"
              . "The **Feedback Module** collects 1-to-5 star satisfaction ratings, operational suggestions, and anonymous employee insights.\n\n"
              . "#### 📊 **Feedback Metrics:**\n"
              . "• **Total Submissions**: `{$totalFeedback}` records\n"
              . "• **⭐ Overall Average Rating**: `" . number_format($avgRating, 1) . " / 5.0`\n"
              . "• **⏳ Awaiting Review**: `{$pendingFeedback}` entries\n\n";

        if (!empty($recentFeedbacks)) {
            $text .= "#### 📋 **Recent Feedback Entries:**\n"
                   . "| No | Submitter | Type | Rating | Subject | Status |\n| :--- | :--- | :--- | :--- | :--- | :--- |\n";
            foreach ($recentFeedbacks as $fb) {
                $stars = str_repeat('⭐', max(1, (int)$fb['rating']));
                $text .= "| `" . htmlspecialchars($fb['feedback_no']) . "` | " . htmlspecialchars($fb['name']) . " | " . ucfirst(htmlspecialchars($fb['submitter_type'])) . " | {$stars} | " . htmlspecialchars(substr($fb['subject'], 0, 25)) . "... | " . ucfirst($fb['status']) . " |\n";
            }
        }

        $text .= "\n👉 View full feedback responses at **[Member & Staff Feedback](/admin/feedbacks.php)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Member & Staff Feedback', 'url' => '/admin/feedbacks.php', 'icon' => 'fa-comment-dots'],
                ['label' => 'Complaints Panel', 'url' => '/admin/complaints', 'icon' => 'fa-comments']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 22. ORGANIZATION STRUCTURE & MANAGEMENT BODY
    // -------------------------------------------------------------
    if (preg_match('/(org structure|organization structure|hierarchy|board of directors|trustee|management body|president|secretary|director|hierarchy tree|leadership)/i', $q)) {
        $totalOrgNodes = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM org_structure WHERE is_active = 1", 0);
        $totalMgmt = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM management_body", 0);
        $topLeaders = safeQueryAll($pdo, "SELECT title, holder_name, department, holder_designation FROM org_structure WHERE is_active = 1 ORDER BY level_tier ASC, sort_order ASC LIMIT 6");

        $text = "### 🏛️ **Organization Structure & Management Hierarchy**\n\n"
              . "The **Organization Structure Module** maps out the NGO's governance tree, National Executive Board, Board of Trustees, Patron, Directors, and Departmental Coordinators.\n\n"
              . "#### 📊 **Governance Nodes:**\n"
              . "• **Active Hierarchy Positions**: `{$totalOrgNodes}` leadership roles\n"
              . "• **Management Body Members**: `{$totalMgmt}` registered officers\n\n";

        if (!empty($topLeaders)) {
            $text .= "#### 👑 **Executive Board & Leadership:**\n";
            foreach ($topLeaders as $ldr) {
                $text .= "• **" . htmlspecialchars($ldr['title']) . "**: " . htmlspecialchars($ldr['holder_name'] ?: 'Vacant') . " (_" . htmlspecialchars($ldr['holder_designation'] ?: $ldr['department']) . "_)\n";
            }
        }

        $text .= "\n#### 🧭 **Manage Leadership:**\n"
               . "• Visual interactive tree chart: **[Org Structure Tree](/admin/org_structure.php)**\n"
               . "• Governing body records: **[Management Body](/admin/admin_management_body)**";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Org Structure Tree', 'url' => '/admin/org_structure.php', 'icon' => 'fa-sitemap'],
                ['label' => 'Management Body', 'url' => '/admin/admin_management_body', 'icon' => 'fa-user-tie'],
                ['label' => 'Access Control', 'url' => '/admin/access-control', 'icon' => 'fa-user-shield']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 23. CMS & WEBSITE CONTENT (ABOUT, SLIDERS, TESTIMONIALS, NEWS, AWARDS)
    // -------------------------------------------------------------
    if (preg_match('/(cms|slider|banner|testimonial|testimonials|news|award|awards|sponsor|sponsors|training video|about page|privacy policy|terms|refund)/i', $q)) {
        $text = "### 🖥️ **CMS & Public Website Content Management**\n\n"
              . "Manage all dynamic front-facing content directly from the administrative portal:\n\n"
              . "• 🖼️ **[Homepage Slider Manager](/admin/slider-manager)**: Hero banners, heading titles, button links.\n"
              . "• ℹ️ **[About Us Manager](/admin/about-manager)**: Mission statement, vision, founder messages.\n"
              . "• 🎯 **[Objectives Manager](/admin/objectives-manager)**: NGO core goals and social pillars.\n"
              . "• 🏆 **[Awards & Recognition](/admin/awards-manager)**: State/National awards, trophies, citations.\n"
              . "• 💬 **[Testimonials](/admin/testimonials)**: Donor, volunteer, and beneficiary testimonials.\n"
              . "• 📰 **[News & Press Releases](/admin/news)**: Media coverage, print news, blog updates.\n"
              . "• 🤝 **[Sponsors & Partners](/admin/sponsor-manager)**: Corporate CSR partners and logos.\n"
              . "• 🎥 **[Training Videos](/admin/training-videos)**: Educational and orientation video catalog.\n"
              . "• 📜 **[Legal Pages CMS](/admin/privacy-manager)**: Privacy Policy, Terms & Conditions, Refund Policy.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Slider Manager', 'url' => '/admin/slider-manager', 'icon' => 'fa-sliders'],
                ['label' => 'About Page', 'url' => '/admin/about-manager', 'icon' => 'fa-circle-info'],
                ['label' => 'News & Updates', 'url' => '/admin/news', 'icon' => 'fa-newspaper'],
                ['label' => 'Testimonials', 'url' => '/admin/testimonials', 'icon' => 'fa-quote-left']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 24. INQUIRIES & CONTACT LEADS
    // -------------------------------------------------------------
    if (preg_match('/(inquir|inquiries|contact|message|messages|lead|leads|sandesh)/i', $q)) {
        $unreadMessages = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM contact_messages WHERE status = 'New'", 0);
        $totalMessages = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM contact_messages", 0);
        $newInquiries = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM inquiries WHERE status = 'New' OR status = 'Pending'", 0);
        $recentContacts = safeQueryAll($pdo, "SELECT name, email, phone, subject, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 4");

        $text = "### 📬 **Inquiries & Visitor Communications**\n\n"
              . "• **Unread Contact Messages**: `{$unreadMessages}` (Total: `{$totalMessages}`)\n"
              . "• **Pending General Inquiries**: `{$newInquiries}`\n\n";

        if (!empty($recentContacts)) {
            $text .= "#### 📩 **Latest Messages from Website Visitors:**\n";
            foreach ($recentContacts as $c) {
                $text .= "• **" . htmlspecialchars($c['name']) . "** (" . htmlspecialchars($c['phone'] ?: $c['email']) . ") - _" . htmlspecialchars($c['subject'] ?: 'General Message') . "_ (" . date('d M', strtotime($c['created_at'])) . ")\n";
            }
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'View Inquiries (' . $newInquiries . ')', 'url' => '/admin/inquiries', 'icon' => 'fa-circle-question'],
                ['label' => 'Contact Manager', 'url' => '/admin/contact-manager', 'icon' => 'fa-address-book']
            ]
        ];
    }

    // -------------------------------------------------------------
    // 25. DONATIONS & FINANCIAL QUERIES (ONLINE)
    // -------------------------------------------------------------
    if (preg_match('/(donation|donations|fund|money|revenue|amount|paisa|collection|raised|payment|bank|qr|upi)/i', $q)) {
        $totalDonation = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'", 0);
        $thisMonthDonation = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success' AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')", 0);
        $todayDonation = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success' AND DATE(created_at) = CURDATE()", 0);
        $pendingDonations = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM donations WHERE payment_status = 'Pending'", 0);
        $recentDonations = safeQueryAll($pdo, "SELECT donor_name, amount, payment_gateway, payment_status, created_at FROM donations ORDER BY created_at DESC LIMIT 5");

        if ($isHinglish) {
            $text = "### 💰 **Daan & Financial Collections (Live Status)**\n\n"
                  . "**Donations Module** me sabhi online Razorpay, UPI QR aur gateway payments ka live hisab rehta hai:\n\n"
                  . "• **Kul Kul Daan (All-Time)**: `₹" . number_format($totalDonation, 2) . "`\n"
                  . "• **Is Mahine Ka Daan (" . date('F Y') . ")**: `₹" . number_format($thisMonthDonation, 2) . "`\n"
                  . "• **Aaj Ka Daan (Today)**: `₹" . number_format($todayDonation, 2) . "`\n"
                  . "• **Pending / Incomplete Transactions**: `{$pendingDonations}`\n\n"
                  . "#### 🕒 **Haal Hi Ke 5 Daan Records:**\n";

            if (!empty($recentDonations)) {
                $text .= "| Daan-Data Ka Naam | Rashi | Gateway | Status | Date |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentDonations as $d) {
                    $statusBadge = $d['payment_status'] === 'Success' ? '✅ Success' : '⏳ ' . $d['payment_status'];
                    $text .= "| " . htmlspecialchars($d['donor_name'] ?: 'Anonymous') . " | ₹" . number_format((float)$d['amount'], 2) . " | " . htmlspecialchars($d['payment_gateway'] ?: 'Online') . " | {$statusBadge} | " . date('d M, Y', strtotime($d['created_at'])) . " |\n";
                }
            } else {
                $text .= "_Abhi tak koi daan record nahi mila._\n";
            }
        } else {
            $text = "### 💰 **Donations & Financial Insights**\n\n"
                  . "• **All-Time Total Collections**: `₹" . number_format($totalDonation, 2) . "`\n"
                  . "• **This Month (" . date('F Y') . ")**: `₹" . number_format($thisMonthDonation, 2) . "`\n"
                  . "• **Today's Collections**: `₹" . number_format($todayDonation, 2) . "`\n"
                  . "• **Pending / Incomplete Transactions**: `{$pendingDonations}`\n\n"
                  . "#### 🕒 **Latest 5 Donation Records:**\n";

            if (!empty($recentDonations)) {
                $text .= "| Donor Name | Amount | Mode | Status | Date |\n| :--- | :--- | :--- | :--- | :--- |\n";
                foreach ($recentDonations as $d) {
                    $statusBadge = $d['payment_status'] === 'Success' ? '✅ Success' : '⏳ ' . $d['payment_status'];
                    $text .= "| " . htmlspecialchars($d['donor_name'] ?: 'Anonymous') . " | ₹" . number_format((float)$d['amount'], 2) . " | " . htmlspecialchars($d['payment_gateway'] ?: 'Online') . " | {$statusBadge} | " . date('d M, Y', strtotime($d['created_at'])) . " |\n";
                }
            } else {
                $text .= "_No donation records found yet._\n";
            }
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Open Donations Page', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Donation Analytics', 'url' => '/admin/donation-analytics', 'icon' => 'fa-chart-pie'],
                ['label' => 'Custom Receipts', 'url' => '/admin/generate_custom_receipt.php', 'icon' => 'fa-receipt'],
                ['label' => 'Expense Tracker', 'url' => '/admin/expenses', 'icon' => 'fa-receipt']
            ],
            'suggestions' => [
                'Show expense summary and financial balance',
                'Show custom receipts summary',
                'Where are payment gateway settings?'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 26. OVERALL SUMMARY / EXECUTIVE OVERVIEW
    // -------------------------------------------------------------
    if (preg_match('/(summary|overview|stats|status|kya chal raha|report|dashboard|overall)/i', $q)) {
        $totalDonation = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'", 0);
        $totalDonationCount = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM donations WHERE payment_status = 'Success'", 0);
        $totalExpense = (float)safeQueryScalar($pdo, "SELECT SUM(amount) FROM expenses", 0);
        $activeVolunteers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Active'", 0);
        $pendingVolunteers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Pending'", 0);
        $activeMembers = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM members WHERE status = 'Active'", 0);
        $totalBeneficiaries = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiaries", 0);
        $totalProviders = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers", 0);
        $totalProjects = (int)safeQueryScalar($pdo, "SELECT COUNT(*) FROM projects", 0);

        $netBalance = $totalDonation - $totalExpense;

        if ($isHinglish) {
            $text = "### 📈 **NGO Operations Ka Real-Time Live Overview**\n\n"
                  . "Yahan sanstha ke sabhi mukhya vibhagon ka live consolidated snapshot diya gaya hai:\n\n"
                  . "| Vibhag / Module | Live Data | Direct Link |\n"
                  . "| :--- | :--- | :--- |\n"
                  . "| **💰 Kul Prapt Daan** | **₹" . number_format($totalDonation, 2) . "** ({$totalDonationCount} receipts) | [Donations Dekhein](/admin/donations) |\n"
                  . "| **💸 Kul Kharcha** | **₹" . number_format($totalExpense, 2) . "** (Net: ₹" . number_format($netBalance, 2) . ") | [Expense Tracker](/admin/expenses) |\n"
                  . "| **🤝 Registered Labharthi** | **{$totalBeneficiaries} Labharthi** | [Labharthi List](/admin/beneficiaries) |\n"
                  . "| **🏥 Healthcare Network** | **{$totalProviders} Facilities/Doctors** | [Healthcare Directory](/admin/healthcare_directory.php) |\n"
                  . "| **🪪 Active Sadasya** | **{$activeMembers} Active Members** | [Sadasya Panel](/admin/memberships) |\n"
                  . "| **👥 Swayamsevak (Volunteers)** | **{$activeVolunteers} Active / {$pendingVolunteers} Pending** | [Volunteers Review](/admin/volunteers) |\n"
                  . "| **🌱 Chal Rahe Projects** | **{$totalProjects} Projects** | [Projects Manager](/admin/projects) |\n\n";
        } else {
            $text = "### 📈 **Executive NGO Operations & Real-Time Overview**\n\n"
                  . "Here is the consolidated live snapshot across all key operational departments:\n\n"
                  . "| Operational Wing | Real-Time Metrics | Quick Action Link |\n"
                  . "| :--- | :--- | :--- |\n"
                  . "| **💰 Total Donations** | **₹" . number_format($totalDonation, 2) . "** ({$totalDonationCount} receipts) | [View Donations](/admin/donations) |\n"
                  . "| **💸 Total Expenses** | **₹" . number_format($totalExpense, 2) . "** (Net: ₹" . number_format($netBalance, 2) . ") | [Expense Tracker](/admin/expenses) |\n"
                  . "| **🤝 Beneficiaries Enrolled** | **{$totalBeneficiaries}** | [Manage Beneficiaries](/admin/beneficiaries) |\n"
                  . "| **🏥 Healthcare Network** | **{$totalProviders} Facilities** | [Healthcare Directory](/admin/healthcare_directory.php) |\n"
                  . "| **🪪 Active Members** | **{$activeMembers} Active Members** | [Manage Members](/admin/memberships) |\n"
                  . "| **👥 Volunteers** | **{$activeVolunteers} Active / {$pendingVolunteers} Pending** | [Review Volunteers](/admin/volunteers) |\n"
                  . "| **🌱 Active Social Projects** | **{$totalProjects} Projects** | [Projects Manager](/admin/projects) |\n\n";
        }

        return [
            'reply' => $text,
            'actions' => [
                ['label' => 'Full Dashboard', 'url' => '/admin/dashboard', 'icon' => 'fa-gauge-high'],
                ['label' => 'Donations Panel', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Beneficiaries', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child'],
                ['label' => 'Healthcare Network', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user'],
                ['label' => 'Expense Tracker', 'url' => '/admin/expenses', 'icon' => 'fa-receipt']
            ],
            'suggestions' => [
                'Show healthcare directory and doctor MOUs',
                'Show expense summary and financial balance',
                'Show beneficiaries and assistance log',
                'Show all modules'
            ]
        ];
    }

    // -------------------------------------------------------------
    // 27. GENERAL AI / LLM FALLBACK FOR ADMIN (GEMINI API)
    // -------------------------------------------------------------
    $geminiApiKey = $settings['gemini_api_key'] ?? '';
    if (!empty($geminiApiKey)) {
        $llmResponse = callGeminiApi($geminiApiKey, $query, buildAdminSystemPrompt($pdo, $settings));
        if ($llmResponse) {
            return [
                'reply' => $llmResponse,
                'actions' => [
                    ['label' => 'Admin Dashboard', 'url' => '/admin/dashboard', 'icon' => 'fa-gauge-high'],
                    ['label' => 'Healthcare Directory', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user'],
                    ['label' => 'Beneficiaries', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child'],
                    ['label' => 'Donations', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                    ['label' => 'Expenses', 'url' => '/admin/expenses', 'icon' => 'fa-receipt']
                ]
            ];
        }
    }

    // Default Smart Admin Response
    if ($isHinglish) {
        return [
            'reply' => "Aapka sawal: **\"{$query}\"** ke baare me hai.\n\n"
                     . "Aapke **Admin AI Copilot** ke paas NGO ke sabhi 25+ modules ka live access hai. Aap ye sawal pooch sakte hain:\n"
                     . "• `Show all modules` ya `Sabhi modules dikhao` → Puri system directory\n"
                     . "• `Healthcare directory and doctors` ya `Doctor list dikhao` → Hospitals, clinics, doctor MOUs\n"
                     . "• `Beneficiaries and assistance log` ya `Labharthi ki list` → BPL parivar, sahayata vitaran\n"
                     . "• `Expense tracker` ya `Kitna kharcha hua` → Spending vouchers aur financial balance\n"
                     . "• `Job postings` ya `Naukri vacancies` → Coordinator bharti aur candidate resumes\n"
                     . "• `Settings and payments` ya `Razorpay setting kahan hai` → Payment keys, SMTP, access control",
            'actions' => [
                ['label' => '🗺️ Sabhi Modules', 'url' => '/admin/dashboard', 'icon' => 'fa-compass'],
                ['label' => 'Healthcare Directory', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user'],
                ['label' => 'Labharthi Directory', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child'],
                ['label' => 'Daan Panel', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
                ['label' => 'Kharcha Tracker', 'url' => '/admin/expenses', 'icon' => 'fa-receipt']
            ]
        ];
    }

    return [
        'reply' => "I understand you are asking about: **\"{$query}\"**.\n\n"
                 . "As your **Admin AI Copilot**, I have complete access across all 25+ NGO modules. You can try asking:\n"
                 . "• `Show all modules` → Complete categorized system directory\n"
                 . "• `Healthcare directory and doctors` → Empaneled hospitals, clinics, doctor MOUs\n"
                 . "• `Beneficiaries and assistance log` → Enrolled BPL families, aid distribution history\n"
                 . "• `Expense tracker and income vs expense` → Spending vouchers and financial balances\n"
                 . "• `Job postings and applications` → Vacancy recruitment and candidate resumes\n"
                 . "• `Student ambassador tasks` → Campus ambassador tasks, submissions, point rules\n"
                 . "• `Document studio and official letters` → Official letterheads, MOUs, certificates\n"
                 . "• `Settings and payments` → Razorpay, UPI QR, SMTP, access control",
        'actions' => [
            ['label' => '🗺️ All Modules', 'url' => '/admin/dashboard', 'icon' => 'fa-compass'],
            ['label' => 'Healthcare Directory', 'url' => '/admin/healthcare_directory.php', 'icon' => 'fa-hospital-user'],
            ['label' => 'Beneficiaries', 'url' => '/admin/beneficiaries', 'icon' => 'fa-hands-holding-child'],
            ['label' => 'Donations Panel', 'url' => '/admin/donations', 'icon' => 'fa-sack-dollar'],
            ['label' => 'Expense Tracker', 'url' => '/admin/expenses', 'icon' => 'fa-receipt']
        ]
    ];
}


// ==========================================
// USER QUERY PROCESSOR FUNCTION (PUBLIC)
// ==========================================
function processUserQuery(PDO $pdo, string $query, array $settings): array {
    $q = mb_strtolower(trim($query));
    $siteName = $settings['site_name'] ?? 'Jaysmrutti Foundation';
    $ngoPhone = $settings['ngo_phone'] ?? '+91 7651910331';
    $ngoEmail = $settings['ngo_email'] ?? 'info@velnixsoft.com';
    $ngoAddress = $settings['ngo_address'] ?? 'Jaunpur, Uttar Pradesh, India';
    $regNo = $settings['reg_no'] ?? '123455';
    $whatsappNo = $settings['whatsapp_number'] ?? '917651910331';
    $disclaimer = $settings['receipt_disclaimer'] ?? 'Donations are tax exempted u/s 80G.';

    // 1. How to Donate / Tax Exemption / Receipts
    if (preg_match('/(donate|donation|daan|tax|80g|receipt|paisa|contribute|payment|qr|bank transfer|kaise donate|kaise daan)/i', $q)) {
        $text = "### 💖 **How to Make a Donation & Support {$siteName}**\n\n"
              . "Thank you for your generous heart! Donating to **{$siteName}** is quick, 100% secure, and eligible for tax benefits:\n\n"
              . "#### 📋 **Step-by-Step Donation Guide:**\n"
              . "1. **Visit the Donation Page**: Click on the **[Donate Now](/donate)** button below.\n"
              . "2. **Select Cause & Amount**: Choose your preferred program (Child Education, Healthcare, Food Distribution, Tree Plantation, or General Fund) or enter a custom amount.\n"
              . "3. **Fill Donor Details**: Enter your Name, PAN Number (for tax exemption), Email, and Phone number.\n"
              . "4. **Choose Payment Mode**: Pay securely via **UPI (GPay/PhonePe/Paytm), Credit/Debit Card, Netbanking, or QR Code** via Razorpay.\n"
              . "5. **Instant 80G Receipt**: Once payment is completed, you can instantly download your official donation receipt!\n\n"
              . "> 🛡️ **Tax Exemption**: " . htmlspecialchars($disclaimer) . "\n\n"
              . "Already donated? You can view your past donations anytime at **[Donation History](/donor-history)** or download receipts from **[Download Receipt](/download-receipt)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '💖 Donate Online Now', 'url' => '/donate', 'icon' => 'fa-heart'],
                ['label' => '📄 Download 80G Receipt', 'url' => '/download-receipt', 'icon' => 'fa-file-arrow-down'],
                ['label' => '📊 My Donation History', 'url' => '/donor-history', 'icon' => 'fa-clock-rotate-left']
            ],
            'suggestions' => [
                'How to become a member?',
                'How to become a volunteer?',
                'What are your ongoing projects?'
            ]
        ];
    }

    // 2. How to Become a Member / Membership Guidance
    if (preg_match('/(member|membership|sadasya|sadasyata|id card|member verify|member kaise bane|join member)/i', $q)) {
        $text = "### 🤝 **How to Become a Member of {$siteName}**\n\n"
              . "Becoming a member allows you to be an integral part of our mission, join our governing initiatives, and lead community welfare drives.\n\n"
              . "#### 🌟 **Member Benefits & Perks:**\n"
              . "• 🪪 **Instant Digital ID Card** with verifiable QR Code.\n"
              . "• 📜 **Official Membership Certificate** signed by NGO leadership.\n"
              . "• 🗳️ **Voting & Participation Rights** in key NGO meetings and social initiatives.\n"
              . "• 📱 **Member Portal Access** to track your activities and notices.\n\n"
              . "#### 📝 **Steps to Register as a Member:**\n"
              . "1. Go to the **[Member Registration Page](/member-register)**.\n"
              . "2. Fill in your personal details, Aadhaar/ID number, photo, and address.\n"
              . "3. Choose your membership tier and submit the nominal membership fee.\n"
              . "4. Upon approval, log into the **[Member Login](/member-login)** to download your ID card and certificate!\n\n"
              . "> 🔍 *Want to verify an existing membership?* Visit **[Verify Member](/member-verify)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '🤝 Become a Member', 'url' => '/member-register', 'icon' => 'fa-user-plus'],
                ['label' => '🔐 Member Login', 'url' => '/member-login', 'icon' => 'fa-right-to-bracket'],
                ['label' => '🔍 Verify Member ID', 'url' => '/member-verify', 'icon' => 'fa-shield-check']
            ],
            'suggestions' => [
                'How to become a volunteer?',
                'How to make a donation?',
                'Tell me about your organization'
            ]
        ];
    }

    // 3. How to Become a Volunteer / Volunteering Guidance
    if (preg_match('/(volunteer|volunteering|swayamsevak|social work|join as volunteer|volunteer kaise bane|field work)/i', $q)) {
        $text = "### 🙋 **How to Become a Volunteer at {$siteName}**\n\n"
              . "Volunteers are the backbone of our grassroots impact! Whether you have 2 hours a week or want to lead field drives, your contribution matters.\n\n"
              . "#### 🎯 **Volunteer Areas & Opportunities:**\n"
              . "• 📚 **Education & Youth Mentoring**: Teach underprivileged children.\n"
              . "• 🏥 **Healthcare & Medical Camps**: Assist in free health checkup drives.\n"
              . "• 🌳 **Environmental & Cleanliness Drives**: Tree plantation & sustainability campaigns.\n"
              . "• 💻 **Digital & Outreach Volunteering**: Social media advocacy, design, and fundraising.\n\n"
              . "#### 📜 **What You Receive:**\n"
              . "• **Official Volunteer Identity Card**\n"
              . "• **Verified Volunteer Certificate & Letter of Recommendation**\n"
              . "• Practical leadership experience and networking with like-minded changemakers.\n\n"
              . "#### 🚀 **How to Apply:**\n"
              . "Simply click **[Become a Volunteer](/volunteer-register)**, complete the short form with your skills and city, and our team will connect with you!";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '🙋 Volunteer Registration', 'url' => '/volunteer-register', 'icon' => 'fa-hand-holding-heart'],
                ['label' => '🔐 Volunteer Login', 'url' => '/volunteer-login', 'icon' => 'fa-right-to-bracket'],
                ['label' => '🔍 Verify Volunteer ID', 'url' => '/volunteer-verify', 'icon' => 'fa-id-card-clip']
            ],
            'suggestions' => [
                'Student Ambassador Program',
                'How to donate?',
                'Upcoming events'
            ]
        ];
    }

    // 4. Student Ambassador & Internship Program
    if (preg_match('/(student|intern|internship|ambassador|campus|points|badges|college)/i', $q)) {
        $text = "### 🎓 **Student Ambassador & Youth Internship Program**\n\n"
              . "Are you a student looking to make social impact while building leadership, communication, and event management skills?\n\n"
              . "#### 🌟 **Program Highlights:**\n"
              . "• 🏆 **Earn Points & Badges**: Complete wellness, awareness, and social campaigns.\n"
              . "• 📜 **Internship Certificate & LOR**: Government-aligned NGO certification.\n"
              . "• 🤝 **Campus Leadership**: Represent {$siteName} in your college or community.\n"
              . "• 🎁 **Rewards & Recognitions**: Top student performers get felicitated at NGO annual events.\n\n"
              . "Click below to join our Student Ambassador Community!";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '🎓 Student Registration', 'url' => '/student-register', 'icon' => 'fa-user-graduate'],
                ['label' => '📱 Student Portal Login', 'url' => '/student-login', 'icon' => 'fa-arrow-right-to-bracket'],
                ['label' => '🏆 View Leaderboard', 'url' => '/student-leaderboard', 'icon' => 'fa-trophy']
            ]
        ];
    }

    // 5. Active Projects & Campaigns
    if (preg_match('/(project|campaign|cause|karya|yojana|drive|crowdfund|work)/i', $q)) {
        $projects = safeQueryAll($pdo, "SELECT title, target_amount, raised_amount, description FROM projects WHERE status = 'Active' OR status = 'active' OR status = '1' OR status IS NULL LIMIT 4");

        $text = "### 🌱 **Our Active Projects & Social Initiatives**\n\n"
              . "Here are some of the key initiatives currently being run by **{$siteName}**:\n\n";

        if (!empty($projects)) {
            foreach ($projects as $p) {
                $pct = $p['target_amount'] > 0 ? round(($p['raised_amount'] / $p['target_amount']) * 100) : 0;
                $text .= "• **" . htmlspecialchars($p['title']) . "**\n"
                       . "  Raised: `₹" . number_format((float)$p['raised_amount']) . "` of `₹" . number_format((float)$p['target_amount']) . "` ({$pct}% funded)\n"
                       . "  _" . htmlspecialchars(substr($p['description'] ?? '', 0, 100)) . "..._\n\n";
            }
        } else {
            $text .= "• **Healthcare & Nutrition Drives**: Free medical checkups and child nutrition.\n"
                   . "• **Youth Education & Digital Literacy**: Sponsoring education for underprivileged students.\n"
                   . "• **Green Planet Initiative**: Mass tree plantation and environmental sustainability.\n"
                   . "• **Women Livelihood Empowerment**: Skill development workshops.\n\n";
        }

        $text .= "You can view complete project details and support specific causes below:";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '🌱 View All Projects', 'url' => '/projects', 'icon' => 'fa-seedling'],
                ['label' => '💖 Donate to a Project', 'url' => '/donate', 'icon' => 'fa-heart']
            ]
        ];
    }

    // 6. Events & Activities
    if (preg_match('/(event|activity|karyakram|camp|upcoming|workshop)/i', $q)) {
        $events = safeQueryAll($pdo, "SELECT title, event_date, location FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3");
        $text = "### 📅 **Upcoming NGO Events & Charity Drives**\n\n";

        if (!empty($events)) {
            foreach ($events as $ev) {
                $text .= "• **" . htmlspecialchars($ev['title']) . "**\n"
                       . "  📅 Date: " . date('d M, Y', strtotime($ev['event_date'])) . "\n"
                       . "  📍 Location: " . htmlspecialchars($ev['location'] ?: 'Jaunpur') . "\n\n";
            }
        } else {
            $text .= "We organize regular health camps, educational workshops, food distribution, and environmental drives throughout the year.\n\n";
        }

        $text .= "Check out all scheduled events and register as a participant or volunteer:";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '📅 View All Events', 'url' => '/events', 'icon' => 'fa-calendar-days'],
                ['label' => '🙋 Volunteer for Events', 'url' => '/volunteer-register', 'icon' => 'fa-hand-holding-heart']
            ]
        ];
    }

    // 7. Verification & Downloads
    if (preg_match('/(verify|verification|check|certificate|id card verify|download|receipt download|praman patra)/i', $q)) {
        $text = "### 🔍 **Official Verification & Document Download Center**\n\n"
              . "All documents, ID cards, and certificates issued by **{$siteName}** are digitally signed and verifiable:\n\n"
              . "• **Verify Member ID**: Check member authenticity and status at **[Verify Member](/member-verify)**.\n"
              . "• **Verify Volunteer ID**: Validate active volunteers at **[Verify Volunteer](/volunteer-verify)**.\n"
              . "• **Verify Certificate**: Authenticate student, event, or visitor certificates at **[Verify Certificate](/verify)**.\n"
              . "• **Download 80G Receipt**: Download your donation tax receipts at **[Download Receipt](/download-receipt)**.";

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '🪪 Verify Member', 'url' => '/member-verify', 'icon' => 'fa-id-card'],
                ['label' => '🙋 Verify Volunteer', 'url' => '/volunteer-verify', 'icon' => 'fa-user-check'],
                ['label' => '📜 Verify Certificate', 'url' => '/verify', 'icon' => 'fa-certificate'],
                ['label' => '📄 Download Receipt', 'url' => '/download-receipt', 'icon' => 'fa-receipt']
            ]
        ];
    }

    // 8. Contact Info / Address / Office / NGO Details
    if (preg_match('/(contact|address|phone|email|whatsapp|office|location|kaha hai|sampark|mobile|number|reg no|registration)/i', $q)) {
        $text = "### 📞 **Contact Information & Head Office**\n\n"
              . "**{$siteName}**\n"
              . "• **Registration No**: `{$regNo}`\n"
              . "• 📍 **Head Office Address**: " . nl2br(htmlspecialchars($ngoAddress)) . "\n"
              . "• 📞 **Phone**: `" . htmlspecialchars($ngoPhone) . "`\n"
              . "• ✉️ **Email**: `" . htmlspecialchars($ngoEmail) . "`\n"
              . "• 💬 **WhatsApp**: `" . htmlspecialchars($whatsappNo) . "`\n\n"
              . "Feel free to get in touch with our team or send us a message via our contact form!";

        $whatsappUrl = "https://wa.me/{$whatsappNo}?text=" . urlencode("Hello, I would like to inquire about " . $siteName);

        return [
            'reply' => $text,
            'actions' => [
                ['label' => '📩 Send Message', 'url' => '/contact', 'icon' => 'fa-envelope'],
                ['label' => '💬 Chat on WhatsApp', 'url' => $whatsappUrl, 'target' => '_blank', 'icon' => 'fa-whatsapp'],
                ['label' => 'ℹ️ About Us', 'url' => '/about', 'icon' => 'fa-circle-info']
            ]
        ];
    }

    // 9. Optional LLM (Gemini API) Integration for Public
    $geminiApiKey = $settings['gemini_api_key'] ?? '';
    if (!empty($geminiApiKey)) {
        $systemPrompt = "You are the friendly, helpful AI Guide for '{$siteName}', a registered non-profit organization (Reg: {$regNo}) located at {$ngoAddress}. "
                      . "Phone: {$ngoPhone}, Email: {$ngoEmail}. "
                      . "Guide visitors clearly on how to donate (80G tax benefit), become a member, become a volunteer, student internship, and verify certificates. "
                      . "Keep answers polite, structured, concise, and inspiring. Use markdown formatting with bullet points.";
        $llmResponse = callGeminiApi($geminiApiKey, $query, $systemPrompt);
        if ($llmResponse) {
            return [
                'reply' => $llmResponse,
                'actions' => [
                    ['label' => '💖 Donate Now', 'url' => '/donate', 'icon' => 'fa-heart'],
                    ['label' => '🤝 Become a Member', 'url' => '/member-register', 'icon' => 'fa-user-plus'],
                    ['label' => '🙋 Join as Volunteer', 'url' => '/volunteer-register', 'icon' => 'fa-hand-holding-heart'],
                    ['label' => '📞 Contact Us', 'url' => '/contact', 'icon' => 'fa-phone']
                ]
            ];
        }
    }

    // Default User Guidance Response
    return [
        'reply' => "Namaste! 🙏 I am here to inform and guide you on everything related to **{$siteName}**.\n\n"
                 . "Here are the most common things I can help you with:\n"
                 . "• **Donations**: Learn how to donate online and get instant 80G tax receipts.\n"
                 . "• **Membership**: Learn how to become a member and get your official ID card.\n"
                 . "• **Volunteering**: Join our social welfare drives and get volunteer certificates.\n"
                 . "• **Projects & Events**: Discover our active causes and upcoming camps.\n"
                 . "• **Document Verification**: Verify any Member ID, Volunteer ID, or Certificate.\n\n"
                 . "Please select one of the quick options below or ask me any question!",
        'actions' => [
            ['label' => '💖 How to Donate', 'url' => '/donate', 'icon' => 'fa-heart'],
            ['label' => '🤝 Become a Member', 'url' => '/member-register', 'icon' => 'fa-user-plus'],
            ['label' => '🙋 Become a Volunteer', 'url' => '/volunteer-register', 'icon' => 'fa-hand-holding-heart'],
            ['label' => '📞 Contact Info', 'url' => '/contact', 'icon' => 'fa-phone']
        ],
        'suggestions' => [
            'How can I donate?',
            'How to become a member?',
            'How to apply as a volunteer?',
            'What is your contact number?'
        ]
    ];
}


// ==========================================
// OPTIONAL GEMINI LLM API CALLER
// ==========================================
function callGeminiApi(string $apiKey, string $userPrompt, string $systemPrompt): ?string {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode(trim($apiKey));
    $payload = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $systemPrompt . "\n\nUser Question: " . $userPrompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1000
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $json = json_decode($res, true);
        if (!empty($json['candidates'][0]['content']['parts'][0]['text'])) {
            return trim($json['candidates'][0]['content']['parts'][0]['text']);
        }
    }

    return null;
}

function buildAdminSystemPrompt(PDO $pdo, array $settings): string {
    $siteName = $settings['site_name'] ?? 'NGO System';
    
    // Quick snapshot counts
    $donations = safeQueryScalar($pdo, "SELECT SUM(amount) FROM donations WHERE payment_status = 'Success'", 0);
    $expenses = safeQueryScalar($pdo, "SELECT SUM(amount) FROM expenses", 0);
    $members = safeQueryScalar($pdo, "SELECT COUNT(*) FROM members WHERE status = 'Active'", 0);
    $volunteers = safeQueryScalar($pdo, "SELECT COUNT(*) FROM volunteers WHERE status = 'Active'", 0);
    $beneficiaries = safeQueryScalar($pdo, "SELECT COUNT(*) FROM beneficiaries", 0);
    $providers = safeQueryScalar($pdo, "SELECT COUNT(*) FROM healthcare_providers", 0);
    $students = safeQueryScalar($pdo, "SELECT COUNT(*) FROM sa_students", 0);
    $jobs = safeQueryScalar($pdo, "SELECT COUNT(*) FROM job_openings", 0);
    $complaints = safeQueryScalar($pdo, "SELECT COUNT(*) FROM complaints WHERE status = 'pending'", 0);

    return "You are the Admin AI Copilot for '{$siteName}', an enterprise NGO Management Portal. "
         . "You have complete authority, database awareness, and operational guidance across all system modules.\n\n"
         . "Current Live Database Snapshot:\n"
         . "- Total Raised: ₹" . number_format((float)$donations, 2) . "\n"
         . "- Total Expenses: ₹" . number_format((float)$expenses, 2) . "\n"
         . "- Active Members: {$members}\n"
         . "- Active Volunteers: {$volunteers}\n"
         . "- Beneficiaries Enrolled: {$beneficiaries}\n"
         . "- Healthcare Providers (Hospitals/Clinics): {$providers}\n"
         . "- Student Ambassadors: {$students}\n"
         . "- Job Openings: {$jobs}\n"
         . "- Pending Complaints: {$complaints}\n\n"
         . "System Module & URL Sitemap:\n"
         . "1. Overview & Dashboard: /admin/dashboard, /admin/ai-assistant\n"
         . "2. Healthcare & Welfare: /admin/healthcare_directory.php (Providers & map pins), /admin/doctor_agreements.php (Doctor MOUs), /admin/health_card_applications.php (Swasthya cards), /admin/beneficiaries (BPL/Widow/Orphan profiles), /admin/beneficiary-assistance (Aid log), /admin/beneficiary-reports\n"
         . "3. Finance & Accounting: /admin/donations, /admin/generate_custom_receipt.php (80G Offline receipts), /admin/recurring-donations (AutoPay mandates), /admin/item-donation-categories (Clothes, ration, blankets), /admin/expenses (Vouchers), /admin/income-vs-expense, /admin/donation-analytics\n"
         . "4. Community & Members: /admin/memberships (Tiers & ID cards), /admin/document-studio, /admin/letters.php (Official Letterhead), /admin/staff_letters.php, /admin/visitor-certificates, /admin/sanstha-certificates, /admin/volunteers, /admin/volunteer-activities\n"
         . "5. Student Ambassadors: /admin/student-directory, /admin/student-tasks, /admin/student-submissions, /admin/student-referrals, /admin/student-certificates, /admin/student-point-rules\n"
         . "6. Careers & HR: /admin/jobs.php (Vacancies), /admin/job_applications.php (Applicant resumes), /admin/hr_policies.php (Code of Conduct, POSH, Child Safety), /admin/career_guidance_manager.php (Skill courses), /admin/agreements.php (MoUs), /admin/org_structure.php (Hierarchy tree), /admin/admin_management_body\n"
         . "7. Field Operations: /admin/field-agents.php, /admin/agent-attendance.php (GPS punch-in), /admin/cash-deposits.php, /admin/agent-payroll.php\n"
         . "8. Operations & CMS: /admin/projects, /admin/crowdfunding, /admin/events, /admin/complaints (Grievance tickets), /admin/feedbacks.php, /admin/inquiries, /admin/slider-manager, /admin/settings, /admin/access-control, /admin/audit-logs\n\n"
         . "Format all answers cleanly in markdown with headings, bold terms, structured tables, exact URL markdown links, and step-by-step guidance.";
}
