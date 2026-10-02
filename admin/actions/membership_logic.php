<?php
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/member_module.php';
require '../../includes/qr_attendance.php';
require '../../libs/fpdf/fpdf.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../memberships.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.memberships')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Security Token Invalid');
    header('Location: ../memberships.php');
    exit;
}

$action = $_POST['action'] ?? '';
$settings = mm_load_settings($pdo);
mm_ensure_member_registration_columns($pdo);
mm_ensure_achievement_positions_table($pdo);

function mm_activate_member(PDO $pdo, array $settings, $memberId)
{
    $member = mm_get_member($pdo, $memberId);
    if (!$member) {
        return false;
    }

    $memberNoPrefix = $settings['member_prefix'] ?? 'SEED';
    $receiptPrefix = $settings['member_receipt_prefix'] ?? 'MRCPT-';

    $memberNo = $member['member_no'] ?: mm_next_member_no($pdo, $memberNoPrefix);
    $receiptNo = $member['member_receipt_no'] ?: mm_next_receipt_no($pdo, $receiptPrefix);
    $refCode = $member['referral_code'] ?: mm_rand_token('MRF-');
    $donationRefCode = $member['donation_ref_code'] ?: mm_rand_token('DRF-');

    $validUntil = !empty($member['valid_until']) ? $member['valid_until'] : date('Y-m-d', strtotime('+1 year'));
    $memberSince = !empty($member['member_since']) ? $member['member_since'] : date('Y-m-d');

    $sql = "UPDATE members
            SET member_no = ?, member_receipt_no = ?, referral_code = ?, donation_ref_code = ?,
                payment_status = 'Success', status = 'Active', member_since = ?, valid_until = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$memberNo, $receiptNo, $refCode, $donationRefCode, $memberSince, $validUntil, $memberId]);
}

if ($action === 'add_designation') {
    $title = mm_clean($_POST['title'] ?? '');
    $fee = (float)($_POST['fee_amount'] ?? 0);
    if ($title === '' || $fee < 0) {
        setFlash('error', 'Designation title and valid fee are required.');
        header('Location: ../memberships.php');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO member_designations (title, fee_amount, is_active) VALUES (?, ?, 1)");
    $stmt->execute([$title, $fee]);
    setFlash('success', 'Designation added.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'toggle_designation') {
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = (int)($_POST['is_active'] ?? 0);
    $stmt = $pdo->prepare("UPDATE member_designations SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    setFlash('success', 'Designation status updated.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'update_designation') {
    $id = (int)($_POST['id'] ?? 0);
    $title = mm_clean($_POST['title'] ?? '');
    $fee = (float)($_POST['fee_amount'] ?? 0);
    if ($id <= 0 || $title === '' || $fee < 0) {
        setFlash('error', 'Designation title and valid fee are required.');
        header('Location: ../memberships.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE member_designations SET title = ?, fee_amount = ? WHERE id = ?");
    $stmt->execute([$title, $fee, $id]);
    setFlash('success', 'Designation updated.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'add_achievement_position') {
    $title = mm_clean($_POST['title'] ?? '');
    if ($title === '') {
        setFlash('error', 'Result / position title is required.');
        header('Location: ../memberships.php');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO achievement_positions (title, is_active) VALUES (?, 1)");
    $stmt->execute([$title]);
    setFlash('success', 'Result / position added.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'toggle_achievement_position') {
    $id = (int)($_POST['id'] ?? 0);
    $newStatus = (int)($_POST['is_active'] ?? 0);
    $stmt = $pdo->prepare("UPDATE achievement_positions SET is_active = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);
    setFlash('success', 'Result / position status updated.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'verify_member') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        setFlash('error', 'Invalid member id.');
        header('Location: ../memberships.php');
        exit;
    }

    mm_activate_member($pdo, $settings, $id);

    // Track who verified the member (for coordinator/manager reports).
    try {
        $setParts = [];
        $params = [];
        if (dbColumnExists($pdo, 'members', 'verified_by')) {
            $setParts[] = "verified_by = ?";
            $params[] = (int)($_SESSION['user_id'] ?? 0);
        }
        if (dbColumnExists($pdo, 'members', 'verified_at')) {
            $setParts[] = "verified_at = NOW()";
        }
        if (!empty($setParts)) {
            $params[] = $id;
            $pdo->prepare("UPDATE members SET " . implode(', ', $setParts) . " WHERE id = ?")->execute($params);
        }
    } catch (Throwable $e) {
    }

    $member = mm_get_member($pdo, $id);
    if ($member && !empty($member['email'])) {
        $siteName = $settings['site_name'] ?? 'NGO';
        $tpl = mm_get_pdf_color_template($settings);
        $verifyUrl = qa_member_verify_url($pdo, $settings, (int)$member['id'], 'receipt');
        $qrPayload = $verifyUrl !== '' ? $verifyUrl : ((($settings['ngo_website'] ?? '') ? rtrim($settings['ngo_website'], '/') : '') . '/member-verify.php?member=' . urlencode((string)$member['member_no']));
        if ($qrPayload === '') {
            $qrPayload = 'MemberNo:' . $member['member_no'] . ';Receipt:' . $member['member_receipt_no'];
        }

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFillColor($tpl['primary'][0], $tpl['primary'][1], $tpl['primary'][2]);
        $pdf->Rect(0, 0, 210, 28, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetXY(15, 8);
        $pdf->Cell(0, 8, strtoupper($siteName) . ' - Membership Receipt', 0, 1);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->SetY(42);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, 'MEMBERSHIP RECEIPT', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(65, 8, 'Receipt No', 0, 0, 'R');
        $pdf->Cell(5, 8, ':', 0, 0, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, $member['member_receipt_no'], 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(65, 8, 'Member Name', 0, 0, 'R');
        $pdf->Cell(5, 8, ':', 0, 0, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, $member['full_name'], 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(65, 8, 'Member No', 0, 0, 'R');
        $pdf->Cell(5, 8, ':', 0, 0, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 8, $member['member_no'], 0, 1);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(65, 8, 'Designation', 0, 0, 'R');
        $pdf->Cell(5, 8, ':', 0, 0, 'C');
        $pdf->Cell(0, 8, $member['designation_title'] ?: '-', 0, 1);
        $pdf->Cell(65, 8, 'Amount', 0, 0, 'R');
        $pdf->Cell(5, 8, ':', 0, 0, 'C');
        $pdf->Cell(0, 8, 'INR ' . number_format((float)$member['membership_fee'], 2), 0, 1);
        $pdf->Image(mm_qr_image_url($qrPayload), 20, 186, 30, 30, 'PNG');
        $pdf->SetXY(15, 220);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 5, 'Scan QR for verification. This is a computer-generated receipt.', 0, 1);
        $pdfContent = $pdf->Output('S');

        try {
            mm_send_email(
                $settings,
                $member['email'],
                $member['full_name'],
                'Membership Activated - ' . $siteName,
                '<p>Dear ' . htmlspecialchars($member['full_name']) . ',</p>'
                    . '<p>Your membership has been activated successfully.</p>'
                    . '<p>Member No: <strong>' . htmlspecialchars($member['member_no']) . '</strong></p>'
                    . '<p>Your receipt is attached. You can also verify via QR.</p>',
                [[
                    'name' => 'Membership_Receipt_' . preg_replace('/[^A-Za-z0-9\-]/', '', $member['member_receipt_no']) . '.pdf',
                    'content' => $pdfContent
                ]]
            );
        } catch (Exception $e) {
        }
    }

    setFlash('success', 'Member verified, activated, and receipt email queued.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'update_payment') {
    $id = (int)($_POST['id'] ?? 0);
    $gateway = cleanInput($_POST['payment_gateway'] ?? 'Manual');
    $paymentStatus = cleanInput($_POST['payment_status'] ?? 'Pending');
    $txnId = cleanInput($_POST['payment_txn_id'] ?? '');
    $membershipFee = (float)($_POST['membership_fee'] ?? 0);
    $validUntil = cleanInput($_POST['valid_until'] ?? '');
    $achievementPosition = cleanInput($_POST['achievement_position'] ?? '');

    $allowedGateways = ['Manual', 'Razorpay'];
    $allowedStatuses = ['Pending', 'Success', 'Failed'];
    if ($id <= 0 || !in_array($gateway, $allowedGateways, true) || !in_array($paymentStatus, $allowedStatuses, true) || $membershipFee < 0) {
        setFlash('error', 'Invalid payment update request.');
        header('Location: ../memberships.php');
        exit;
    }

    $proofPath = null;
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['payment_proof']['size'] > 2 * 1024 * 1024) {
            setFlash('error', 'Payment proof must be under 2MB.');
            header('Location: ../memberships.php');
            exit;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $_FILES['payment_proof']['tmp_name']) : ($_FILES['payment_proof']['type'] ?? '');
        if ($finfo) finfo_close($finfo);

        if (!in_array($mime, $allowedTypes, true)) {
            setFlash('error', 'Invalid payment proof file type.');
            header('Location: ../memberships.php');
            exit;
        }

        $targetDir = "../../uploads/members/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
        $fileName = 'member_pay_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
        if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetDir . $fileName)) {
            setFlash('error', 'Failed to upload payment proof.');
            header('Location: ../memberships.php');
            exit;
        }
        $proofPath = 'uploads/members/' . $fileName;
    }

    try {
        $member = mm_get_member($pdo, $id);
        if (!$member) {
            setFlash('error', 'Member not found.');
            header('Location: ../memberships.php');
            exit;
        }

        $set = [
            'membership_fee = ?',
            'payment_gateway = ?',
            'payment_status = ?'
        ];
        $params = [$membershipFee, $gateway, $paymentStatus];

        if ($txnId !== '') {
            $set[] = 'payment_txn_id = ?';
            $params[] = $txnId;
        }

        if ($proofPath !== null) {
            $set[] = 'payment_proof = ?';
            $params[] = $proofPath;
        }

        if ($validUntil !== '') {
            $set[] = 'valid_until = ?';
            $params[] = $validUntil;
        }

        $set[] = 'achievement_position = ?';
        $params[] = ($achievementPosition !== '') ? $achievementPosition : null;

        if ($paymentStatus === 'Success') {
            // Activate membership, issue member_no/receipt/referral codes.
            mm_activate_member($pdo, $settings, $id);

            // Track verifier for coordinator/manager reports.
            try {
                $setParts = [];
                $vParams = [];
                if (dbColumnExists($pdo, 'members', 'verified_by')) {
                    $setParts[] = "verified_by = ?";
                    $vParams[] = (int)($_SESSION['user_id'] ?? 0);
                }
                if (dbColumnExists($pdo, 'members', 'verified_at')) {
                    $setParts[] = "verified_at = NOW()";
                }
                if (!empty($setParts)) {
                    $vParams[] = $id;
                    $pdo->prepare("UPDATE members SET " . implode(', ', $setParts) . " WHERE id = ?")->execute($vParams);
                }
            } catch (Throwable $e) {
            }
        } elseif ($paymentStatus === 'Failed') {
            $set[] = "status = 'Blocked'";
        } else {
            $set[] = "status = 'Pending'";
        }

        $params[] = $id;
        $pdo->prepare("UPDATE members SET " . implode(', ', $set) . " WHERE id = ?")->execute($params);

        setFlash('success', 'Payment details updated.');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        setFlash('error', 'Unable to update payment right now.');
    }

    header('Location: ../memberships.php');
    exit;
}

if ($action === 'update_member_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    $allowed = ['Pending', 'Active', 'Blocked'];
    if (!in_array($status, $allowed, true)) {
        setFlash('error', 'Invalid status.');
        header('Location: ../memberships.php');
        exit;
    }
    $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    setFlash('success', 'Member status updated.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'edit_member') {
    $id = (int)($_POST['id'] ?? 0);
    $fullName = mm_clean($_POST['full_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = mm_clean($_POST['phone'] ?? '');
    $bloodGroup = mm_clean($_POST['blood_group'] ?? '');
    $qualification = mm_clean($_POST['qualification'] ?? '');
    $profession = mm_clean($_POST['profession'] ?? '');
    $maritalStatus = mm_clean($_POST['marital_status'] ?? '');
    $designationId = (int)($_POST['designation_id'] ?? 0);
    $status = mm_clean($_POST['status'] ?? 'Pending');
    $address = mm_clean($_POST['address'] ?? '');
    $district = mm_clean($_POST['district'] ?? '');
    $state = mm_clean($_POST['state'] ?? '');
    $localBodyType = mm_clean($_POST['local_body_type'] ?? '');
    $localBodyName = mm_clean($_POST['local_body_name'] ?? '');
    $wardNo = mm_clean($_POST['ward_no'] ?? '');
    $wardName = mm_clean($_POST['ward_name'] ?? '');
    $kudumbhaSamithi = mm_clean($_POST['kudumbha_samithi'] ?? '');
    $existingPhoto = mm_clean($_POST['existing_photo'] ?? '');

    if ($id <= 0 || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
        setFlash('error', 'Please fill all required member fields.');
        header('Location: ../memberships.php');
        exit;
    }

    if (!in_array($status, ['Pending', 'Active', 'Blocked'], true)) {
        setFlash('error', 'Invalid member status.');
        header('Location: ../memberships.php');
        exit;
    }

    $photoPath = $existingPhoto !== '' ? $existingPhoto : null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            setFlash('error', 'Photo must be under 2MB.');
            header('Location: ../memberships.php');
            exit;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $_FILES['photo']['tmp_name']) : ($_FILES['photo']['type'] ?? '');
        if ($finfo) finfo_close($finfo);

        if (!in_array($mime, $allowedTypes, true)) {
            setFlash('error', 'Photo must be JPG, PNG, or WEBP.');
            header('Location: ../memberships.php');
            exit;
        }

        $targetDir = "../../uploads/members/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $fileName = 'member_photo_' . time() . '_' . bin2hex(random_bytes(6)) . ($ext ? ('.' . $ext) : '');
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $targetDir . $fileName)) {
            setFlash('error', 'Failed to upload member photo.');
            header('Location: ../memberships.php');
            exit;
        }
        $photoPath = 'uploads/members/' . $fileName;
    }

    $dupStmt = $pdo->prepare("SELECT id FROM members WHERE email = ? AND id <> ? LIMIT 1");
    $dupStmt->execute([$email, $id]);
    if ($dupStmt->fetchColumn()) {
        setFlash('error', 'Another member already uses this email.');
        header('Location: ../memberships.php');
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE members
        SET full_name = ?, email = ?, phone = ?, blood_group = ?, qualification = ?, profession = ?, marital_status = ?, designation_id = ?, status = ?, address = ?, district = ?, state = ?, local_body_type = ?, local_body_name = ?, ward_no = ?, ward_name = ?, kudumbha_samithi = ?, photo = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $fullName,
        $email,
        $phone,
        $bloodGroup !== '' ? $bloodGroup : null,
        $qualification !== '' ? $qualification : null,
        $profession !== '' ? $profession : null,
        $maritalStatus !== '' ? $maritalStatus : null,
        $designationId > 0 ? $designationId : null,
        $status,
        $address !== '' ? $address : null,
        $district !== '' ? $district : null,
        $state !== '' ? $state : null,
        $localBodyType !== '' ? $localBodyType : null,
        $localBodyName !== '' ? $localBodyName : null,
        $wardNo !== '' ? $wardNo : null,
        $wardName !== '' ? $wardName : null,
        $kudumbhaSamithi !== '' ? $kudumbhaSamithi : null,
        $photoPath,
        $id
    ]);

    setFlash('success', 'Member updated successfully.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'delete_member') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        setFlash('error', 'Invalid member.');
        header('Location: ../memberships.php');
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM members WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    setFlash('success', 'Member deleted successfully.');
    header('Location: ../memberships.php');
    exit;
}

if ($action === 'mark_payment_failed') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE members SET payment_status = 'Failed', status = 'Blocked' WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Payment marked failed and member blocked.');
    header('Location: ../memberships.php');
    exit;
}

setFlash('error', 'Unknown action.');
header('Location: ../memberships.php');
exit;
