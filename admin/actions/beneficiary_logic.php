<?php
// ============================================================
// admin/actions/beneficiary_logic.php
// Controller for Beneficiary Management and Assistance History
// Follows existing Membership and Volunteer logic architecture
// ============================================================

require_once '../../config/db.php';
require_once '../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_GET['action']) && $_GET['action'] === 'get_beneficiary_json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'coordinator', 'page.beneficiaries')) {
    if (isset($_GET['action']) && $_GET['action'] === 'get_beneficiary_json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access Denied']);
        exit;
    }
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: ../dashboard.php');
    exit;
}

// ── GET ACTIONS (AJAX / JSON) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');

    if ($action === 'get_beneficiary_json') {
        header('Content-Type: application/json');
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT b.*, c.category_name, p.title AS project_title 
            FROM beneficiaries b 
            LEFT JOIN beneficiary_categories c ON b.category_id = c.id 
            LEFT JOIN projects p ON b.project_id = p.id 
            WHERE b.id = ? 
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $beneficiary = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$beneficiary) {
            echo json_encode(['success' => false, 'message' => 'Beneficiary not found']);
            exit;
        }

        echo json_encode(['success' => true, 'data' => $beneficiary]);
        exit;
    }
}

// ── POST ACTIONS (Mutations) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../beneficiaries.php');
    exit;
}

// CSRF Validation
if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    setFlash('error', 'Security validation failed (Invalid CSRF Token). Please try again.');
    header('Location: ../beneficiaries.php');
    exit;
}

$action = cleanInput($_POST['action'] ?? '');

// Helper to generate next Beneficiary Tracking Code
function getNextBeneficiaryCode(PDO $pdo): string {
    $year = date('Y');
    $prefix = 'BEN-' . $year . '-';
    $stmt = $pdo->prepare("SELECT beneficiary_code FROM beneficiaries WHERE beneficiary_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode && preg_match('/BEN-\d{4}-(\d+)/', $lastCode, $m)) {
        $nextNum = (int)$m[1] + 1;
    } else {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM beneficiaries");
        $nextNum = ((int)$stmt2->fetchColumn()) + 1;
    }

    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// Helper to generate next Assistance Tracking Code
function getNextAssistanceCode(PDO $pdo): string {
    $year = date('Y');
    $prefix = 'AST-' . $year . '-';
    $stmt = $pdo->prepare("SELECT assistance_code FROM beneficiary_assistance_history WHERE assistance_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode && preg_match('/AST-\d{4}-(\d+)/', $lastCode, $m)) {
        $nextNum = (int)$m[1] + 1;
    } else {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM beneficiary_assistance_history");
        $nextNum = ((int)$stmt2->fetchColumn()) + 1;
    }

    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// ── 1. ADD BENEFICIARY ────────────────────────────────────────
if ($action === 'add_beneficiary') {
    $name = cleanInput($_POST['name'] ?? '');
    $fatherOrSpouse = cleanInput($_POST['father_or_spouse_name'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? 'Male');
    $dob = cleanInput($_POST['dob'] ?? '') ?: null;
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT) ?: null;
    $contact = cleanInput($_POST['contact'] ?? '');
    $altContact = cleanInput($_POST['alternate_contact'] ?? '') ?: null;
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
    $aadharNo = cleanInput($_POST['aadhar_no'] ?? '') ?: null;
    $rationCardNo = cleanInput($_POST['ration_card_no'] ?? '') ?: null;
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
    $beneficiaryType = cleanInput($_POST['beneficiary_type'] ?? 'General / BPL');
    $annualIncome = filter_input(INPUT_POST, 'annual_income', FILTER_VALIDATE_FLOAT) ?: null;
    $familyMembers = filter_input(INPUT_POST, 'family_members_count', FILTER_VALIDATE_INT) ?: 1;
    $disabilityStatus = cleanInput($_POST['disability_status'] ?? 'No');
    $disabilityDetails = cleanInput($_POST['disability_details'] ?? '') ?: null;
    
    $address = cleanInput($_POST['address'] ?? '');
    $block = cleanInput($_POST['block'] ?? '') ?: null;
    $district = cleanInput($_POST['district'] ?? '');
    $state = cleanInput($_POST['state'] ?? '');
    $villageCity = cleanInput($_POST['village_city'] ?? '') ?: null;
    $pincode = cleanInput($_POST['pincode'] ?? '') ?: null;

    $registrationDate = cleanInput($_POST['registration_date'] ?? date('Y-m-d')) ?: date('Y-m-d');
    $status = cleanInput($_POST['status'] ?? 'Active');
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
    $coordinatorId = filter_input(INPUT_POST, 'coordinator_id', FILTER_VALIDATE_INT) ?: null;
    $remarks = cleanInput($_POST['remarks'] ?? '') ?: null;

    // Validation
    if (empty($name)) {
        setFlash('error', 'Beneficiary Full Name is required.');
        header('Location: ../beneficiaries.php');
        exit;
    }

    if (empty($contact) || !preg_match('/^[0-9]{10,12}$/', $contact)) {
        setFlash('error', 'A valid 10-digit primary mobile number is required.');
        header('Location: ../beneficiaries.php');
        exit;
    }

    if (empty($address) || empty($district) || empty($state)) {
        setFlash('error', 'Full Address, District, and State are required.');
        header('Location: ../beneficiaries.php');
        exit;
    }

    // Handle Photo Upload
    $photoPath = null;
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $mime = mime_content_type($_FILES['photo']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed, true) && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $uploadDir = __DIR__ . '/../../uploads/beneficiaries/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'ben_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
                $photoPath = 'uploads/beneficiaries/' . $fileName;
            }
        }
    }

    // Handle ID Proof Document Upload
    $idProofPath = null;
    if (!empty($_FILES['id_proof_doc']['name']) && $_FILES['id_proof_doc']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'application/pdf'];
        $mime = mime_content_type($_FILES['id_proof_doc']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['id_proof_doc']['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed, true)) {
            $uploadDir = __DIR__ . '/../../uploads/beneficiaries/docs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'id_proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['id_proof_doc']['tmp_name'], $uploadDir . $fileName)) {
                $idProofPath = 'uploads/beneficiaries/docs/' . $fileName;
            }
        }
    }

    $beneficiaryCode = getNextBeneficiaryCode($pdo);
    $registeredBy = (int)($_SESSION['user_id'] ?? 1);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO beneficiaries (
                beneficiary_code, name, father_or_spouse_name, gender, dob, age,
                contact, alternate_contact, email, aadhar_no, ration_card_no,
                category_id, beneficiary_type, annual_income, family_members_count,
                disability_status, disability_details, address, block, district, state,
                village_city, pincode, photo, id_proof_doc, registration_date,
                status, project_id, registered_by, coordinator_id, remarks
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $beneficiaryCode, $name, $fatherOrSpouse, $gender, $dob, $age,
            $contact, $altContact, $email, $aadharNo, $rationCardNo,
            $categoryId, $beneficiaryType, $annualIncome, $familyMembers,
            $disabilityStatus, $disabilityDetails, $address, $block, $district, $state,
            $villageCity, $pincode, $photoPath, $idProofPath, $registrationDate,
            $status, $projectId, $registeredBy, $coordinatorId, $remarks
        ]);

        $newId = (int)$pdo->lastInsertId();
        setFlash('success', 'Beneficiary registered successfully with Code: <strong>' . htmlspecialchars($beneficiaryCode) . '</strong>');
        header('Location: ../beneficiary_profile.php?id=' . $newId);
        exit;

    } catch (PDOException $e) {
        error_log("add_beneficiary error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
        header('Location: ../beneficiaries.php');
        exit;
    }
}

// ── 2. UPDATE BENEFICIARY ─────────────────────────────────────
if ($action === 'update_beneficiary') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        setFlash('error', 'Invalid Beneficiary ID.');
        header('Location: ../beneficiaries.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM beneficiaries WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        setFlash('error', 'Beneficiary record not found.');
        header('Location: ../beneficiaries.php');
        exit;
    }

    $name = cleanInput($_POST['name'] ?? $existing['name']);
    $fatherOrSpouse = cleanInput($_POST['father_or_spouse_name'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? 'Male');
    $dob = cleanInput($_POST['dob'] ?? '') ?: null;
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT) ?: null;
    $contact = cleanInput($_POST['contact'] ?? $existing['contact']);
    $altContact = cleanInput($_POST['alternate_contact'] ?? '') ?: null;
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ?: null;
    $aadharNo = cleanInput($_POST['aadhar_no'] ?? '') ?: null;
    $rationCardNo = cleanInput($_POST['ration_card_no'] ?? '') ?: null;
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null;
    $beneficiaryType = cleanInput($_POST['beneficiary_type'] ?? 'General / BPL');
    $annualIncome = filter_input(INPUT_POST, 'annual_income', FILTER_VALIDATE_FLOAT) ?: null;
    $familyMembers = filter_input(INPUT_POST, 'family_members_count', FILTER_VALIDATE_INT) ?: 1;
    $disabilityStatus = cleanInput($_POST['disability_status'] ?? 'No');
    $disabilityDetails = cleanInput($_POST['disability_details'] ?? '') ?: null;

    $address = cleanInput($_POST['address'] ?? $existing['address']);
    $block = cleanInput($_POST['block'] ?? '') ?: null;
    $district = cleanInput($_POST['district'] ?? $existing['district']);
    $state = cleanInput($_POST['state'] ?? $existing['state']);
    $villageCity = cleanInput($_POST['village_city'] ?? '') ?: null;
    $pincode = cleanInput($_POST['pincode'] ?? '') ?: null;

    $registrationDate = cleanInput($_POST['registration_date'] ?? $existing['registration_date']);
    $status = cleanInput($_POST['status'] ?? $existing['status']);
    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
    $coordinatorId = filter_input(INPUT_POST, 'coordinator_id', FILTER_VALIDATE_INT) ?: null;
    $remarks = cleanInput($_POST['remarks'] ?? '') ?: null;

    // Handle Photo Replacement
    $photoPath = $existing['photo'];
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $mime = mime_content_type($_FILES['photo']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed, true)) {
            $uploadDir = __DIR__ . '/../../uploads/beneficiaries/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'ben_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
                $photoPath = 'uploads/beneficiaries/' . $fileName;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE beneficiaries SET
                name = ?, father_or_spouse_name = ?, gender = ?, dob = ?, age = ?,
                contact = ?, alternate_contact = ?, email = ?, aadhar_no = ?, ration_card_no = ?,
                category_id = ?, beneficiary_type = ?, annual_income = ?, family_members_count = ?,
                disability_status = ?, disability_details = ?, address = ?, block = ?, district = ?, state = ?,
                village_city = ?, pincode = ?, photo = ?, registration_date = ?, status = ?,
                project_id = ?, coordinator_id = ?, remarks = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name, $fatherOrSpouse, $gender, $dob, $age,
            $contact, $altContact, $email, $aadharNo, $rationCardNo,
            $categoryId, $beneficiaryType, $annualIncome, $familyMembers,
            $disabilityStatus, $disabilityDetails, $address, $block, $district, $state,
            $villageCity, $pincode, $photoPath, $registrationDate, $status,
            $projectId, $coordinatorId, $remarks, $id
        ]);

        setFlash('success', 'Beneficiary details updated successfully.');
        $redirectUrl = isset($_POST['redirect_to_profile']) ? ('../beneficiary_profile.php?id=' . $id) : '../beneficiaries.php';
        header('Location: ' . $redirectUrl);
        exit;

    } catch (PDOException $e) {
        error_log("update_beneficiary error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
        header('Location: ../beneficiaries.php');
        exit;
    }
}

// ── 3. DELETE BENEFICIARY ─────────────────────────────────────
if ($action === 'delete_beneficiary') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        setFlash('error', 'Invalid Beneficiary ID.');
        header('Location: ../beneficiaries.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM beneficiaries WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Beneficiary record and assistance history removed.');
        header('Location: ../beneficiaries.php');
        exit;
    } catch (PDOException $e) {
        error_log("delete_beneficiary error: " . $e->getMessage());
        setFlash('error', 'Could not delete beneficiary: ' . $e->getMessage());
        header('Location: ../beneficiaries.php');
        exit;
    }
}

// ── 4. TOGGLE STATUS ──────────────────────────────────────────
if ($action === 'toggle_status') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = cleanInput($_POST['status'] ?? 'Active');

    if ($id && in_array($newStatus, ['Active', 'Inactive', 'Under Review', 'Assisted', 'Archived'], true)) {
        $stmt = $pdo->prepare("UPDATE beneficiaries SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        setFlash('success', 'Beneficiary status updated to ' . htmlspecialchars($newStatus));
    }
    header('Location: ../beneficiaries.php');
    exit;
}

// ── 5. ADD ASSISTANCE RECORD (Aid Handover) ───────────────────
if ($action === 'add_assistance') {
    $beneficiaryId = filter_input(INPUT_POST, 'beneficiary_id', FILTER_VALIDATE_INT);
    $assistanceType = cleanInput($_POST['assistance_type'] ?? 'Ration & Food Kit');
    $description = cleanInput($_POST['description'] ?? '');
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT) ?: 0.00;
    $itemsDetail = cleanInput($_POST['items_detail'] ?? '') ?: null;
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT) ?: 1.00;
    $unit = cleanInput($_POST['unit'] ?? 'units');
    $estimatedValue = filter_input(INPUT_POST, 'estimated_value', FILTER_VALIDATE_FLOAT) ?: 0.00;
    $date = cleanInput($_POST['date'] ?? date('Y-m-d')) ?: date('Y-m-d');
    $givenBy = cleanInput($_POST['given_by'] ?? '') ?: null;
    
    // RBAC: If coordinator, lock coordinator_id to current logged-in user
    $isManagerOrAdmin = checkRole($pdo, 'manager');
    if (!$isManagerOrAdmin) {
        $coordinatorId = (int)$_SESSION['user_id'];
    } else {
        $coordinatorId = filter_input(INPUT_POST, 'coordinator_id', FILTER_VALIDATE_INT) ?: (int)$_SESSION['user_id'];
    }

    $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT) ?: null;
    $itemDonationId = filter_input(INPUT_POST, 'item_donation_id', FILTER_VALIDATE_INT) ?: null;
    $distributionLocation = cleanInput($_POST['distribution_location'] ?? '') ?: null;
    $receiptNo = cleanInput($_POST['receipt_no'] ?? '') ?: null;
    $status = cleanInput($_POST['status'] ?? 'Distributed');
    $remarks = cleanInput($_POST['remarks'] ?? '') ?: null;
    $redirectUrl = cleanInput($_POST['redirect_url'] ?? '');

    if (!$beneficiaryId) {
        setFlash('error', 'Please select a valid beneficiary.');
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }

    if (empty($description)) {
        setFlash('error', 'Assistance description / particulars are required.');
        header('Location: ' . ($redirectUrl ?: ('../beneficiary_profile.php?id=' . $beneficiaryId)));
        exit;
    }

    // Handle Proof Photo Upload
    $proofPhotoPath = null;
    if (!empty($_FILES['proof_photo']['name']) && $_FILES['proof_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $mime = mime_content_type($_FILES['proof_photo']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed, true)) {
            $uploadDir = __DIR__ . '/../../uploads/assistance/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'aid_proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['proof_photo']['tmp_name'], $uploadDir . $fileName)) {
                $proofPhotoPath = 'uploads/assistance/' . $fileName;
            }
        }
    }

    $assistanceCode = getNextAssistanceCode($pdo);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO beneficiary_assistance_history (
                assistance_code, beneficiary_id, assistance_type, description,
                amount, items_detail, quantity, unit, estimated_value,
                date, given_by, coordinator_id, project_id, item_donation_id,
                distribution_location, proof_photo, receipt_no, status, remarks
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $assistanceCode, $beneficiaryId, $assistanceType, $description,
            $amount, $itemsDetail, $quantity, $unit, $estimatedValue,
            $date, $givenBy, $coordinatorId, $projectId, $itemDonationId,
            $distributionLocation, $proofPhotoPath, $receiptNo, $status, $remarks
        ]);

        // Automatically update beneficiary status to 'Assisted' if currently 'Active' or 'Under Review'
        $pdo->prepare("UPDATE beneficiaries SET status = 'Assisted' WHERE id = ? AND status IN ('Active', 'Under Review')")->execute([$beneficiaryId]);

        setFlash('success', 'Assistance record logged successfully (' . htmlspecialchars($assistanceCode) . ')');
        $target = $redirectUrl ?: ('../beneficiary_profile.php?id=' . $beneficiaryId);
        header('Location: ' . $target);
        exit;

    } catch (PDOException $e) {
        error_log("add_assistance error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
        header('Location: ' . ($redirectUrl ?: ('../beneficiary_profile.php?id=' . $beneficiaryId)));
        exit;
    }
}

// ── 6. UPDATE ASSISTANCE RECORD ───────────────────────────────
if ($action === 'update_assistance') {
    $assistanceId = filter_input(INPUT_POST, 'assistance_id', FILTER_VALIDATE_INT);
    $beneficiaryId = filter_input(INPUT_POST, 'beneficiary_id', FILTER_VALIDATE_INT);
    $redirectUrl = cleanInput($_POST['redirect_url'] ?? '');

    if (!$assistanceId) {
        setFlash('error', 'Invalid assistance ID.');
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }

    // Check existing record & verify RBAC ownership
    $stmt = $pdo->prepare("SELECT * FROM beneficiary_assistance_history WHERE id = ?");
    $stmt->execute([$assistanceId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        setFlash('error', 'Assistance record not found.');
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }

    $isManagerOrAdmin = checkRole($pdo, 'manager');
    if (!$isManagerOrAdmin && (int)$existing['coordinator_id'] !== (int)$_SESSION['user_id']) {
        setFlash('error', 'Access Denied: You can only edit assistance records logged by you.');
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }

    $assistanceType = cleanInput($_POST['assistance_type'] ?? $existing['assistance_type']);
    $description = cleanInput($_POST['description'] ?? $existing['description']);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT) ?: 0.00;
    $itemsDetail = cleanInput($_POST['items_detail'] ?? '') ?: null;
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT) ?: 1.00;
    $unit = cleanInput($_POST['unit'] ?? 'units');
    $estimatedValue = filter_input(INPUT_POST, 'estimated_value', FILTER_VALIDATE_FLOAT) ?: 0.00;
    $date = cleanInput($_POST['date'] ?? $existing['date']);
    $givenBy = cleanInput($_POST['given_by'] ?? $existing['given_by']);
    $distributionLocation = cleanInput($_POST['distribution_location'] ?? $existing['distribution_location']);
    $receiptNo = cleanInput($_POST['receipt_no'] ?? $existing['receipt_no']);
    $status = cleanInput($_POST['status'] ?? $existing['status']);
    $remarks = cleanInput($_POST['remarks'] ?? $existing['remarks']);

    $proofPhotoPath = $existing['proof_photo'];
    if (!empty($_FILES['proof_photo']['name']) && $_FILES['proof_photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $mime = mime_content_type($_FILES['proof_photo']['tmp_name']);
        $ext = strtolower(pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION));

        if (in_array($mime, $allowed, true)) {
            $uploadDir = __DIR__ . '/../../uploads/assistance/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = 'aid_proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['proof_photo']['tmp_name'], $uploadDir . $fileName)) {
                $proofPhotoPath = 'uploads/assistance/' . $fileName;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE beneficiary_assistance_history SET
                assistance_type = ?, description = ?, amount = ?, items_detail = ?,
                quantity = ?, unit = ?, estimated_value = ?, date = ?, given_by = ?,
                distribution_location = ?, proof_photo = ?, receipt_no = ?, status = ?, remarks = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $assistanceType, $description, $amount, $itemsDetail,
            $quantity, $unit, $estimatedValue, $date, $givenBy,
            $distributionLocation, $proofPhotoPath, $receiptNo, $status, $remarks,
            $assistanceId
        ]);

        setFlash('success', 'Assistance record updated successfully.');
        $target = $redirectUrl ?: ($beneficiaryId ? ('../beneficiary_profile.php?id=' . $beneficiaryId) : '../beneficiaries.php');
        header('Location: ' . $target);
        exit;

    } catch (PDOException $e) {
        error_log("update_assistance error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }
}

// ── 7. DELETE ASSISTANCE RECORD (RBAC Scoped) ─────────────────
if ($action === 'delete_assistance') {
    $assistanceId = filter_input(INPUT_POST, 'assistance_id', FILTER_VALIDATE_INT);
    $beneficiaryId = filter_input(INPUT_POST, 'beneficiary_id', FILTER_VALIDATE_INT);
    $redirectUrl = cleanInput($_POST['redirect_url'] ?? '');

    if (!$assistanceId) {
        setFlash('error', 'Invalid assistance ID.');
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }

    // RBAC: Coordinators can ONLY delete assistance records created by themselves
    $isManagerOrAdmin = checkRole($pdo, 'manager');
    if (!$isManagerOrAdmin) {
        $stmtCheck = $pdo->prepare("SELECT coordinator_id FROM beneficiary_assistance_history WHERE id = ?");
        $stmtCheck->execute([$assistanceId]);
        $ownerId = (int)$stmtCheck->fetchColumn();

        if ($ownerId !== (int)$_SESSION['user_id']) {
            setFlash('error', 'Access Denied: You can only delete assistance records created by you.');
            header('Location: ' . ($redirectUrl ?: ($beneficiaryId ? ('../beneficiary_profile.php?id=' . $beneficiaryId) : '../beneficiaries.php')));
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM beneficiary_assistance_history WHERE id = ?");
        $stmt->execute([$assistanceId]);

        setFlash('success', 'Assistance record removed.');
        $target = $redirectUrl ?: ($beneficiaryId ? ('../beneficiary_profile.php?id=' . $beneficiaryId) : '../beneficiaries.php');
        header('Location: ' . $target);
        exit;
    } catch (PDOException $e) {
        error_log("delete_assistance error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
        header('Location: ' . ($redirectUrl ?: '../beneficiaries.php'));
        exit;
    }
}

header('Location: ../beneficiaries.php');
exit;
