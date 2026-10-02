<?php
/**
 * Donation Achievements Dashboard Widget
 * 
 * Display donation milestone progress and achievements on student dashboard
 */

// Requires: $studentId to be set in calling context
// Returns: HTML widget for displaying in dashboard

if (empty($studentId)) {
    return '';
}

require_once __DIR__ . '/donation_achievements.php';

$engine = new DonationAchievementEngine($pdo);
$progress = $engine->getDonationProgress($studentId);
$achievements = $engine->getStudentAchievements($studentId);

// Build widget HTML
$widget = '';

// Main Card
$widget .= '<div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl border border-amber-200 shadow-lg overflow-hidden mb-6">';
$widget .= '  <div class="p-6 bg-gradient-to-r from-amber-500 to-orange-500">';
$widget .= '    <h3 class="text-xl font-bold text-white flex items-center gap-2">';
$widget .= '      <i class="fas fa-hand-holding-heart"></i> Donation Achievements';
$widget .= '    </h3>';
$widget .= '  </div>';

$widget .= '  <div class="p-6 space-y-6">';

// Total Donated
$totalDonated = number_format($progress['total_donated'], 2);
$widget .= '  <div class="bg-white rounded-xl p-5 border border-amber-100">';
$widget .= '    <div class="flex items-center justify-between">';
$widget .= '      <div>';
$widget .= '        <p class="text-sm font-medium text-gray-600 mb-1">Total Donations Facilitated</p>';
$widget .= '        <p class="text-3xl font-bold text-amber-600">₹' . $totalDonated . '</p>';
$widget .= '      </div>';
$widget .= '      <div class="text-4xl text-amber-200"><i class="fas fa-gift"></i></div>';
$widget .= '    </div>';
$widget .= '  </div>';

// Achieved Milestones
if (!empty($achievements)) {
    $widget .= '  <div class="space-y-3">';
    $widget .= '    <p class="text-sm font-semibold text-gray-700 px-2">Achieved Milestones</p>';
    
    foreach ($achievements as $achievement) {
        $badgeIcon = '';
        if ($achievement['badge_awarded']) {
            $badgeIcon = ' <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold"><i class="fas fa-badge"></i> Badge Earned</span>';
        }
        
        $widget .= '    <div class="bg-green-50 rounded-lg p-3 border border-green-200">';
        $widget .= '      <div class="flex items-start justify-between">';
        $widget .= '        <div>';
        $widget .= '          <p class="font-semibold text-green-900">' . htmlspecialchars($achievement['milestone_name']) . '</p>';
        $widget .= '          <p class="text-sm text-green-700">₹' . number_format($achievement['milestone_amount'], 2) . ' Achieved</p>';
        if ($achievement['points_awarded'] > 0) {
            $widget .= '          <p class="text-xs text-green-600 mt-1"><i class="fas fa-star"></i> +' . $achievement['points_awarded'] . ' Points</p>';
        }
        $widget .= '        </div>';
        $widget .= '        <div class="text-right">';
        $widget .= '          <p class="text-xs text-gray-500">' . date('M d, Y', strtotime($achievement['achieved_at'])) . '</p>';
        $widget .= '          ' . $badgeIcon;
        $widget .= '        </div>';
        $widget .= '      </div>';
        $widget .= '    </div>';
    }
    
    $widget .= '  </div>';
}

// Next Milestone Progress
if (!empty($progress['next_milestone'])) {
    $nextMilestone = $progress['next_milestone'];
    $progressPercentage = $progress['progress_percentage'] ?? 0;
    $amountNeeded = number_format($progress['amount_needed'], 2);
    $nextAmount = number_format($nextMilestone['milestone_amount'], 2);
    
    $widget .= '  <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">';
    $widget .= '    <p class="text-sm font-semibold text-blue-900 mb-3">Next Milestone</p>';
    $widget .= '    <p class="text-lg font-bold text-blue-600 mb-2">' . htmlspecialchars($nextMilestone['milestone_name']) . '</p>';
    $widget .= '    <p class="text-sm text-blue-700 mb-3">₹' . $nextAmount . ' Target</p>';
    
    // Progress Bar
    $widget .= '    <div class="w-full bg-blue-200 rounded-full h-3 overflow-hidden mb-2">';
    $widget .= '      <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full" style="width: ' . min($progressPercentage, 100) . '%"></div>';
    $widget .= '    </div>';
    
    $widget .= '    <div class="flex justify-between items-center text-xs">';
    $widget .= '      <span class="text-blue-600 font-semibold">' . $progressPercentage . '% Complete</span>';
    $widget .= '      <span class="text-blue-600">₹' . $amountNeeded . ' to go</span>';
    $widget .= '    </div>';
    
    if (!empty($nextMilestone['reward_points']) || !empty($nextMilestone['badge_code'])) {
        $widget .= '    <div class="mt-3 pt-3 border-t border-blue-200 text-xs text-blue-700">';
        $widget .= '      <p class="font-semibold mb-1">Rewards at ₹' . $nextAmount . ':</p>';
        if ($nextMilestone['reward_points'] > 0) {
            $widget .= '      <p><i class="fas fa-star text-yellow-500"></i> +' . $nextMilestone['reward_points'] . ' Points</p>';
        }
        if ($nextMilestone['badge_code']) {
            $widget .= '      <p><i class="fas fa-badge text-yellow-500"></i> Special Badge Unlock</p>';
        }
        $widget .= '    </div>';
    }
    
    $widget .= '  </div>';
} else {
    // All milestones achieved
    $widget .= '  <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-4 border border-emerald-300">';
    $widget .= '    <p class="text-center text-emerald-700 font-semibold">';
    $widget .= '      <i class="fas fa-trophy text-emerald-600 mr-2"></i>';
    $widget .= '      🎉 All milestones achieved! Keep raising impact!';
    $widget .= '    </p>';
    $widget .= '  </div>';
}

$widget .= '  </div>';
$widget .= '</div>';

echo $widget;
