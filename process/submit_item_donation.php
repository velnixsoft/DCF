<?php
// ============================================================
// process/submit_item_donation.php
// Handles submission of in-kind/item donation pledges from the website
// ============================================================

require '../config/db.php';
require '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../donate-items.php');
    exit;
}

// 1. Verify CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    setFlash('error', 'Security validation failed. Please refresh the page and try again.');
    header('Location: ../donate-items.php');
    exit;
}

// 2. Sanitize & Collect Form Inputs
$categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
$donorName = cleanInput($_POST['name'] ?? $_POST['donor_name'] ?? '');
$donorEmail = filter_var($_POST['email'] ?? $_POST['donor_email'] ?? '', FILTER_VALIDATE_EMAIL);
$donorMobile = cleanInput($_POST['mobile'] ?? $_POST['donor_mobile'] ?? '');
$donorPan = strtoupper(cleanInput($_POST['pan'] ?? $_POST['donor_pan'] ?? ''));

$itemDescription = cleanInput($_POST['item_description'] ?? '');
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
$unit = cleanInput($_POST['unit'] ?? 'pcs');
$conditionType = cleanInput($_POST['condition_type'] ?? 'New');
$estimatedValue = filter_input(INPUT_POST, 'estimated_value', FILTER_VALIDATE_FLOAT) ?: 0.00;

$pickupCity = cleanInput($_POST['pickup_city'] ?? '');
$pickupPincode = cleanInput($_POST['pickup_pincode'] ?? '');
$pickupAddress = cleanInput($_POST['pickup_address'] ?? '');
$projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
$remarks = cleanInput($_POST['remarks'] ?? '');
$referralCode = cleanInput($_POST['referral_code'] ?? '');

// 3. Validate Inputs
$errors = [];

if (empty($categoryId)) {
    $errors[] = 'Please select a valid item category.';
}

if (empty($donorName)) {
    $errors[] = 'Full name is required.';
}

if (!$donorEmail) {
    $errors[] = 'A valid email address is required.';
}

if (empty($donorMobile) || !preg_match('/^[0-9]{10}$/', $donorMobile)) {
    $errors[] = 'A valid 10-digit mobile number is required.';
}

if (!empty($donorPan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $donorPan)) {
    $errors[] = 'Invalid PAN format (e.g. ABCDE1234F).';
}

if (empty($itemDescription) || mb_strlen($itemDescription) < 4) {
    $errors[] = 'Please provide a clear description of the items being donated.';
}

if (!$quantity || $quantity <= 0) {
    $errors[] = 'Item quantity must be greater than 0.';
}

if (empty($pickupCity)) {
    $errors[] = 'Pickup / Donor city is required.';
}

if (empty($pickupPincode) || !preg_match('/^[0-9]{6}$/', $pickupPincode)) {
    $errors[] = 'A valid 6-digit postal pincode is required.';
}

if (empty($pickupAddress)) {
    $errors[] = 'Full pickup / drop address is required.';
}

if (!in_array($conditionType, ['New', 'Gently Used', 'Refurbished', 'Usable'], true)) {
    $conditionType = 'New';
}

if (!empty($errors)) {
    setFlash('error', implode('<br>', $errors));
    header('Location: ../donate-items.php');
    exit;
}

// 4. Check Referral Code (Student Ambassador / Field Agent)
$saStudentId = null;
$fieldAgentId = null;

if (!empty($referralCode)) {
    $stStmt = $pdo->prepare("SELECT id FROM sa_students WHERE referral_code = ? AND status = 'Active' LIMIT 1");
    $stStmt->execute([$referralCode]);
    $saStudentId = $stStmt->fetchColumn() ?: null;

    if (!$saStudentId) {
        $agStmt = $pdo->prepare("SELECT id FROM field_agents WHERE agent_code = ? AND status = 'Active' LIMIT 1");
        $agStmt->execute([$referralCode]);
        $fieldAgentId = $agStmt->fetchColumn() ?: null;
    }
}

// 5. Handle Optional Item Photo Upload
$itemPhotoPath = null;

if (!empty($_FILES['item_photo']['name']) && $_FILES['item_photo']['error'] === UPLOAD_ERR_OK) {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $fileTmp = $_FILES['item_photo']['tmp_name'];
    $fileSize = $_FILES['item_photo']['size'];
    $fileMime = mime_content_type($fileTmp);
    $fileExt = strtolower(pathinfo($_FILES['item_photo']['name'], PATHINFO_EXTENSION));

    if (!in_array($fileMime, $allowedMimes, true) || !in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        setFlash('error', 'Invalid photo format. Only JPG, PNG, and WebP images are allowed.');
        header('Location: ../donate-items.php');
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        setFlash('error', 'Item photo must not exceed 5MB in size.');
        header('Location: ../donate-items.php');
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/item_donations/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFileName = 'item_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $fileExt;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        $itemPhotoPath = 'uploads/item_donations/' . $newFileName;
    }
}

// 6. Generate Codes & Insert into Database
$donationCode = 'ITM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
$receiptNo = 'RCP-ITM-' . date('Ymd') . '-' . mt_rand(1000, 9999);
$donationDate = date('Y-m-d');

try {
    $stmt = $pdo->prepare("
        INSERT INTO item_donations (
            donation_code,
            category_id,
            donor_name,
            donor_email,
            donor_mobile,
            donor_pan,
            donor_address,
            pickup_city,
            pickup_pincode,
            pickup_address,
            item_description,
            quantity,
            unit,
            estimated_value,
            condition_type,
            donation_date,
            status,
            project_id,
            receipt_no,
            field_agent_id,
            sa_student_id,
            referral_code,
            item_photo,
            remarks
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pledged', ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $stmt->execute([
        $donationCode,
        $categoryId,
        $donorName,
        $donorEmail,
        $donorMobile,
        $donorPan ?: null,
        $pickupAddress,
        $pickupCity,
        $pickupPincode,
        $pickupAddress,
        $itemDescription,
        $quantity,
        $unit,
        $estimatedValue,
        $conditionType,
        $donationDate,
        $projectId,
        $receiptNo,
        $fieldAgentId,
        $saStudentId,
        $referralCode ?: null,
        $itemPhotoPath,
        $remarks ?: null
    ]);

    $insertedId = (int)$pdo->lastInsertId();

    // Fetch created_at timestamp for consistent token generation
    $timeStmt = $pdo->prepare("SELECT created_at FROM item_donations WHERE id = ?");
    $timeStmt->execute([$insertedId]);
    $createdAt = (string)($timeStmt->fetchColumn() ?: date('Y-m-d H:i:s'));

    // 7. Fetch Category Name for Acknowledgment
    $catName = (string)($pdo->query("SELECT category_name FROM item_donation_categories WHERE id = " . (int)$categoryId)->fetchColumn() ?: 'Essential Items');
    $siteName = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'")->fetchColumn() ?: 'NGO System');

    // Generate Secure Download Token & URLs
    $downloadToken = generateItemDonationReceiptToken($insertedId, $donationCode, $receiptNo, $donorEmail, $createdAt);
    $receiptUrl = rtrim(appBaseUrl(), '/') . '/download-item-receipt.php?id=' . $insertedId . '&token=' . urlencode($downloadToken);
    $verifyUrl = rtrim(appBaseUrl(), '/') . '/verify-item.php?code=' . urlencode($donationCode) . '&token=' . urlencode($downloadToken);

    // 8. Send Acknowledgment & Receipt Email
    try {
        $settings = [];
        $st = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key = 'site_name' OR setting_key = 'contact_email'");
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        $emailSubject = "Item Donation Receipt & Acknowledgment - " . $receiptNo;
        $estValFormatted = ($estimatedValue > 0) ? ('INR ' . number_format((float)$estimatedValue, 2)) : 'In-Kind Charitable Contribution';

        $emailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; color: #333; line-height: 1.6;'>
                <div style='background: #0F8B8D; padding: 22px 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: bold;'>" . htmlspecialchars($siteName) . "</h1>
                    <p style='color: #e0f2f1; margin: 6px 0 0 0; font-size: 13px;'>Official In-Kind Item Donation Receipt</p>
                </div>
                <div style='padding: 26px; border: 1px solid #e2e8f0; border-top: none; background: #ffffff; border-radius: 0 0 10px 10px;'>
                    <p style='font-size: 15px; margin-top: 0;'>Dear <strong>" . htmlspecialchars($donorName) . "</strong>,</p>
                    <p style='font-size: 14px; color: #475569;'>Thank you for your generous in-kind contribution! We have received and recorded your item donation pledge.</p>
                    
                    <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin: 20px 0;'>
                        <table style='width: 100%; font-size: 13px; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b; width: 40%;'><strong>Receipt No:</strong></td>
                                <td style='padding: 6px 0; font-weight: bold; color: #0F8B8D;'>" . htmlspecialchars($receiptNo) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Pledge Code:</strong></td>
                                <td style='padding: 6px 0; font-family: monospace; font-weight: bold;'>" . htmlspecialchars($donationCode) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Donation Date:</strong></td>
                                <td style='padding: 6px 0;'>" . date('d F, Y', strtotime($donationDate)) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Item Category:</strong></td>
                                <td style='padding: 6px 0; font-weight: 600;'>" . htmlspecialchars($catName) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Item Details:</strong></td>
                                <td style='padding: 6px 0;'>" . htmlspecialchars($itemDescription) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Quantity & Condition:</strong></td>
                                <td style='padding: 6px 0; font-weight: bold; color: #059669;'>" . htmlspecialchars($quantity . ' ' . $unit) . " (" . htmlspecialchars($conditionType) . ")</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Estimated Value:</strong></td>
                                <td style='padding: 6px 0;'>" . htmlspecialchars($estValFormatted) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Pickup Location:</strong></td>
                                <td style='padding: 6px 0;'>" . htmlspecialchars($pickupCity . ' (' . $pickupPincode . ')') . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 6px 0; color: #64748b;'><strong>Status:</strong></td>
                                <td style='padding: 6px 0;'><span style='background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px;'>PLEDGED / RECORDED</span></td>
                            </tr>
                        </table>
                    </div>

                    <div style='text-align: center; margin: 28px 0 20px;'>
                        <a href='" . htmlspecialchars($receiptUrl) . "' style='background: #0F8B8D; color: #ffffff; padding: 13px 26px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px; display: inline-block; box-shadow: 0 4px 6px -1px rgba(15, 139, 141, 0.3);'>
                            📄 Download Item Donation Receipt (PDF)
                        </a>
                    </div>

                    <p style='font-size: 12px; color: #64748b; text-align: center; margin-bottom: 24px;'>
                        Or verify digitally with QR authentication:<br>
                        <a href='" . htmlspecialchars($verifyUrl) . "' style='color: #0F8B8D; text-decoration: underline; word-break: break-all;'>" . htmlspecialchars($verifyUrl) . "</a>
                    </p>

                    <p style='font-size: 13px; color: #475569; margin: 16px 0;'>
                        Our logistics/volunteer coordinator will contact you at <strong>" . htmlspecialchars($donorMobile) . "</strong> shortly to arrange collection or drop-off.
                    </p>

                    <div style='margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;'>
                        Warm regards,<br>
                        <strong>" . htmlspecialchars($siteName) . " Team</strong>
                    </div>
                </div>
            </div>
        ";

        mm_send_email($settings, $donorEmail, $donorName, $emailSubject, $emailBody);
    } catch (Throwable $e) {
        error_log("Item donation pledge email notification error: " . $e->getMessage());
    }

    setFlash('success', 'Thank you! Your item donation pledge (' . $donationCode . ') has been submitted successfully. Our team will contact you soon.');
    header('Location: ../thankyou.php?type=item&code=' . urlencode($donationCode));
    exit;

} catch (PDOException $e) {
    error_log("submit_item_donation error: " . $e->getMessage());
    setFlash('error', 'Database error: ' . $e->getMessage());
    header('Location: ../donate-items.php');
    exit;
}
