<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/student/portal_helpers.php';

$student = student_portal_require_student($pdo, 'profile');
$profileProgress = student_portal_profile_progress($student);
$_SESSION['student_profile_csrf'] = $_SESSION['student_profile_csrf'] ?? bin2hex(random_bytes(16));

student_portal_render_shell_start($pdo, $student, 'Student Profile', 'profile', [
    'profile_progress' => $profileProgress,
]);

$requiredFields = student_portal_profile_fields();
require_once __DIR__ . '/includes/india_locations.php';
$stateOptions = india_state_list();
$yearOptions = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Post Graduate', 'Other'];
$profileFieldClass = 'w-full rounded-2xl border border-slate-200 bg-white px-4 py-3.5 text-xs font-semibold text-slate-900 caret-slate-900 outline-none transition duration-150 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 dark:bg-white dark:text-slate-900';
?>

<style>
    .student-profile-field,
    .student-profile-field:focus,
    .student-profile-field:active {
        color: #0f172a !important;
        background: #ffffff !important;
        -webkit-text-fill-color: #0f172a !important;
        caret-color: #0f172a !important;
    }

    .student-profile-field::placeholder {
        color: #94a3b8 !important;
        -webkit-text-fill-color: #94a3b8 !important;
    }
</style>

<div class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] font-sans">
    <section class="rounded-[2rem] border border-slate-100 bg-white p-6 md:p-8 shadow-sm space-y-6">
        <div class="flex items-start justify-between gap-4 border-b pb-4 border-slate-100">
            <div>
                <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Profile Score</span>
                <h2 class="mt-2 text-xl font-black text-slate-900"><?php echo (int)$profileProgress['percent']; ?>% Complete</h2>
                <p class="text-xs text-slate-400 mt-1">Complete your profile to unlock full dashboard privileges.</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/50 px-4 py-3.5 text-right">
                <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Missing</p>
                <p class="text-lg font-black text-slate-900 mt-0.5"><?php echo count($profileProgress['missing']); ?></p>
            </div>
        </div>

        <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-600 transition-all duration-500" style="width: <?php echo (int)$profileProgress['percent']; ?>%"></div>
        </div>

        <form action="process/update_student_profile.php" method="POST" class="grid gap-5 md:grid-cols-2">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['student_profile_csrf']); ?>">
            <?php foreach ($requiredFields as $field => $label): ?>
                <?php
                    $value = (string)($student[$field] ?? '');
                    $isTextarea = $field === 'address';
                    $isRequired = in_array($field, ['full_name', 'email', 'mobile', 'gender', 'college_name', 'department_name', 'year_of_study', 'state_name', 'city_name'], true);
                ?>
                <div class="<?php echo $field === 'address' ? 'md:col-span-2' : ''; ?> space-y-1.5">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <?php echo htmlspecialchars($label); ?><?php echo $isRequired ? ' *' : ''; ?>
                    </label>
                    <?php if ($isTextarea): ?>
                        <textarea name="<?php echo htmlspecialchars($field); ?>" rows="3" 
                            class="student-profile-field w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold outline-none transition duration-150 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 text-slate-900 dark:bg-white dark:text-slate-900 placeholder:text-slate-400"><?php echo htmlspecialchars($value); ?></textarea>
                    <?php elseif ($field === 'gender'): ?>
                        <select name="gender" required
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                            <?php foreach (['' => 'Select gender', 'Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'] as $genderValue => $genderLabel): ?>
                                <option value="<?php echo htmlspecialchars($genderValue); ?>" <?php echo $value === $genderValue ? 'selected' : ''; ?>><?php echo htmlspecialchars($genderLabel); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field === 'year_of_study'): ?>
                        <select name="year_of_study" required
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                            <option value="">Select Year</option>
                            <?php foreach ($yearOptions as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo $value === $year ? 'selected' : ''; ?>><?php echo htmlspecialchars($year); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field === 'state_name'): ?>
                        <select name="state_name" id="profile_state_name" onchange="updateProfileCities()" required
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                            <option value="">Select State</option>
                            <?php foreach ($stateOptions as $state): ?>
                                <option value="<?php echo htmlspecialchars($state); ?>" <?php echo $value === $state ? 'selected' : ''; ?>><?php echo htmlspecialchars($state); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field === 'city_name'): ?>
                        <select name="city_name" id="profile_city_name" required
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                            <option value="">Select City / District</option>
                        </select>
                    <?php elseif ($field === 'full_name'): ?>
                        <input type="text" name="full_name" required pattern="^[a-zA-Z\s'\.\-]+$" title="Full Name should only contain letters, spaces, hyphens, apostrophes, and dots" oninput="this.value = this.value.replace(/[^a-zA-Z\s'\.\-]/g, '')" value="<?php echo htmlspecialchars($value); ?>"
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                    <?php elseif ($field === 'email'): ?>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($value); ?>"
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                    <?php elseif ($field === 'mobile'): ?>
                        <input type="tel" name="mobile" required maxlength="10" pattern="(?!([0-9])\1{9})[6-9][0-9]{9}" title="Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9 (cannot be all identical digits)" placeholder="e.g. 9876543210" value="<?php echo htmlspecialchars($value); ?>"
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                    <?php else: ?>
                        <input type="text" name="<?php echo htmlspecialchars($field); ?>" value="<?php echo htmlspecialchars($value); ?>" 
                            class="student-profile-field <?php echo $profileFieldClass; ?>">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="md:col-span-2 flex flex-wrap gap-2.5 pt-3 border-t border-slate-100">
                <button type="submit" class="rounded-2xl bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white px-5 py-3 text-xs font-bold shadow-md shadow-emerald-500/10 hover:shadow-lg transition active:scale-95">Save Profile Details</button>
                <a href="student-dashboard.php" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">Back to Dashboard</a>
            </div>
        </form>
    </section>

    <aside class="space-y-6">
        <!-- Change Password Section -->
        <section class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider border-b pb-3 border-slate-100">Update Password</h3>
            <form action="process/change_student_password.php" method="POST" class="space-y-3.5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['student_profile_csrf']); ?>">
                <div class="space-y-1">
                    <input type="password" name="current_password" required placeholder="Current Password" 
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-xs font-semibold outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 text-slate-800">
                </div>
                <div class="space-y-1">
                    <input type="password" name="new_password" required minlength="6" placeholder="New Password (min 6)" 
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-xs font-semibold outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 text-slate-800">
                </div>
                <div class="space-y-1">
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Confirm New Password" 
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-xs font-semibold outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 text-slate-800">
                </div>
                <button type="submit" class="w-full rounded-2xl bg-slate-900 hover:bg-slate-850 text-white py-3 text-xs font-bold shadow transition active:scale-95">Update Password</button>
            </form>
        </section>

        <!-- Status Alerts Section -->
        <section class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-900 uppercase tracking-wider border-b pb-3 border-slate-100">Profile Completeness</h3>
            <ul class="space-y-2 text-xs font-semibold text-slate-600">
                <?php if (empty($profileProgress['missing'])): ?>
                    <li class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-emerald-800 flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        <span>Awesome! Your profile is complete.</span>
                    </li>
                <?php else: ?>
                    <li class="text-slate-400 font-bold uppercase tracking-wider mb-2">Pending Information:</li>
                    <?php foreach ($profileProgress['missing'] as $item): ?>
                        <li class="rounded-xl bg-slate-50 border border-slate-100 px-4 py-2.5 text-slate-500 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400 flex-shrink-0"></span>
                            <span><?php echo htmlspecialchars($item); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </section>

        <!-- Identity Summary Card -->
        <section class="rounded-[2rem] border border-emerald-900 bg-slate-900 p-6 text-white shadow-lg space-y-4 relative overflow-hidden">
            <div class="absolute -right-5 -bottom-5 w-20 h-20 bg-emerald-500/10 rounded-full blur-2xl"></div>
            <h3 class="text-xs font-bold uppercase tracking-widest text-emerald-400 border-b pb-2 border-slate-800">Identity Profile</h3>
            <div class="space-y-2 text-xs text-slate-300 font-semibold">
                <p>Ambassador ID: <span class="text-white font-mono bg-white/5 px-2 py-0.5 rounded ml-1"><?php echo htmlspecialchars($student['student_no']); ?></span></p>
                <p>Level Tier: <span class="text-white bg-white/5 px-2 py-0.5 rounded ml-1"><?php echo htmlspecialchars($student['level_name'] ?? 'Student Ambassador'); ?></span></p>
                <p>Attribution Code: <span class="text-emerald-300 font-mono bg-white/5 px-2 py-0.5 rounded ml-1"><?php echo htmlspecialchars($student['referral_code']); ?></span></p>
            </div>
        </section>
    </aside>
<script>
const stateDistrictMap = <?php echo india_state_district_js(); ?>;
function updateProfileCities() {
    const stateEl = document.getElementById('profile_state_name');
    const cityEl = document.getElementById('profile_city_name');
    if (!stateEl || !cityEl) return;
    const selectedState = stateEl.value;
    const currentCity = <?php echo json_encode($student['city_name'] ?? ''); ?>;
    const cities = stateDistrictMap[selectedState] || [];
    let html = '<option value="">Select City / District</option>';
    cities.forEach(c => {
        const sel = (c === currentCity || (cityEl.value && c === cityEl.value)) ? 'selected' : '';
        html += `<option value="${c.replace(/"/g, '&quot;')}" ${sel}>${c}</option>`;
    });
    cityEl.innerHTML = html;
}
document.addEventListener('DOMContentLoaded', () => {
    updateProfileCities();
});
</script>

<?php student_portal_render_shell_end(); ?>
