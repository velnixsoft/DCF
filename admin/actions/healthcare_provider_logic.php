<?php
// ============================================================
// admin/actions/healthcare_provider_logic.php
// Controller for Healthcare Providers CRUD Operations
// Compatible with Partner Directory & Beneficiary logic pattern
// ============================================================

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/upload_validator.php';
require_once __DIR__ . '/../../includes/admin_audit.php';
require_once __DIR__ . '/../../includes/healthcare_map_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── AUTH & ROLE CHECK ─────────────────────────────────────────
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_GET['action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

if (!checkRole($pdo, 'coordinator')) {
    if (isset($_GET['action']) || isset($_POST['is_ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Access denied. Coordinator/Manager/Admin role required.']);
        exit;
    }
    setFlash('error', 'Access denied. Coordinator/Manager/Admin role required.');
    header('Location: ../dashboard.php');
    exit;
}

$adminUserId = (int)($_SESSION['user_id'] ?? 0);

// ── HELPER: Generate Provider Code ────────────────────────────
function generateProviderCode(PDO $pdo): string {
    $year = date('Y');
    $prefix = "HCP-{$year}-";
    $stmt = $pdo->prepare("SELECT provider_code FROM healthcare_providers WHERE provider_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastCode = $stmt->fetchColumn();

    if ($lastCode && preg_match('/HCP-\d{4}-(\d+)/', $lastCode, $m)) {
        $nextNum = (int)$m[1] + 1;
    } else {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM healthcare_providers");
        $nextNum = ((int)$stmt2->fetchColumn()) + 1;
    }

    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

// ── GET ACTIONS (AJAX / JSON) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = cleanInput($_GET['action'] ?? '');

    if ($action === 'get_provider_json') {
        header('Content-Type: application/json; charset=utf-8');
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid provider ID.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM healthcare_providers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            echo json_encode(['success' => false, 'message' => 'Healthcare provider not found.']);
            exit;
        }

        $mapInfo = getProviderMapDetails($provider);
        $provider['map_details'] = $mapInfo;

        echo json_encode(['success' => true, 'data' => $provider]);
        exit;
    }

    // Default GET fallback
    header('Location: ../healthcare_directory.php');
    exit;
}

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isAjax = isset($_POST['is_ajax']) && $_POST['is_ajax'] === '1';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // CSRF Check
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Security token (CSRF) validation failed.']);
            exit;
        }
        setFlash('error', 'Security token (CSRF) validation failed. Please try again.');
        header('Location: ../healthcare_directory.php');
        exit;
    }

    $action = cleanInput($_POST['action'] ?? '');

    try {
        // ------------------------------------------------------
        // 1. CREATE HEALTHCARE PROVIDER
        // ------------------------------------------------------
        if ($action === 'create_provider') {
            $name = cleanInput($_POST['name'] ?? '');
            $type = cleanInput($_POST['type'] ?? 'hospital');
            $speciality = cleanInput($_POST['speciality'] ?? 'other');
            $specialityCustom = cleanInput($_POST['speciality_custom'] ?? '');
            $contactPerson = cleanInput($_POST['contact_person'] ?? '');
            $contact = cleanInput($_POST['contact'] ?? '');
            $alternateContact = cleanInput($_POST['alternate_contact'] ?? '');
            $email = cleanInput($_POST['email'] ?? '');
            $website = cleanInput($_POST['website'] ?? '');
            $state = cleanInput($_POST['state'] ?? '');
            $district = cleanInput($_POST['district'] ?? '');
            $block = cleanInput($_POST['block'] ?? '');
            $pincode = cleanInput($_POST['pincode'] ?? '');
            $address = cleanInput($_POST['address'] ?? '');
            $landmark = cleanInput($_POST['landmark'] ?? '');

            // Map Location & Geolocation fields
            $rawMapLocation = trim($_POST['map_location'] ?? '');
            $rawLat = trim($_POST['latitude'] ?? '');
            $rawLng = trim($_POST['longitude'] ?? '');
            $rawEmbed = trim($_POST['map_embed_url'] ?? '');

            $parsedMap = parseGoogleMapLocationInput(
                $rawMapLocation,
                $rawLat !== '' ? $rawLat : null,
                $rawLng !== '' ? $rawLng : null,
                $rawEmbed !== '' ? $rawEmbed : null,
                $address,
                $district,
                $state,
                $name
            );
            $mapLocation = $parsedMap['map_location'];
            $latitude = $parsedMap['latitude'];
            $longitude = $parsedMap['longitude'];
            $mapEmbedUrl = $parsedMap['map_embed_url'];

            $timing = cleanInput($_POST['timing'] ?? '');
            $emergencyAvailable = isset($_POST['emergency_available']) && $_POST['emergency_available'] == '1' ? 1 : 0;
            $discountOffered = cleanInput($_POST['discount_offered'] ?? '');
            $servicesOffered = cleanInput($_POST['services_offered'] ?? '');
            $remarks = cleanInput($_POST['remarks'] ?? '');
            $status = cleanInput($_POST['status'] ?? 'active');
            $isVerified = isset($_POST['is_verified']) && $_POST['is_verified'] == '1' ? 1 : 0;

            // Validations
            if (empty($name)) {
                throw new Exception('Healthcare Provider / Facility Name is required.');
            }
            if (empty($contact)) {
                throw new Exception('Primary Contact Number is required.');
            }
            if (empty($state) || empty($district)) {
                throw new Exception('State and District are required.');
            }
            if (empty($address)) {
                throw new Exception('Address is required.');
            }

            $allowedTypes = ['hospital', 'clinic', 'pathology_lab', 'pharmacy', 'doctor'];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'hospital';
            }

            $allowedSpecialities = ['eye', 'dental', 'other'];
            if (!in_array($speciality, $allowedSpecialities, true)) {
                $speciality = 'other';
            }

            $allowedStatuses = ['active', 'inactive', 'pending_approval'];
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'active';
            }

            // Generate unique provider code
            $providerCode = generateProviderCode($pdo);

            // Handle Photo Upload
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadDir = __DIR__ . '/../../uploads/healthcare';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $val = validateUploadedFile(
                    $_FILES['photo'],
                    ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    5 * 1024 * 1024,
                    ['jpg', 'jpeg', 'png', 'webp', 'gif']
                );

                if (!$val['success']) {
                    throw new Exception('Photo upload error: ' . ($val['message'] ?? 'Invalid file'));
                }

                $stored = storeValidatedUpload($_FILES['photo'], $uploadDir, 'uploads/healthcare', 'hcp');
                if ($stored['success']) {
                    $photoPath = $stored['relative_path'];
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO healthcare_providers (
                    provider_code, name, type, speciality, speciality_custom,
                    contact_person, contact, alternate_contact, email, website,
                    state, district, block, pincode, address, landmark,
                    map_location, latitude, longitude, map_embed_url,
                    photo, timing, emergency_available, discount_offered,
                    services_offered, remarks, status, is_verified, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $providerCode, $name, $type, $speciality, $specialityCustom,
                $contactPerson, $contact, $alternateContact, $email, $website,
                $state, $district, $block, $pincode, $address, $landmark,
                $mapLocation, $latitude, $longitude, $mapEmbedUrl,
                $photoPath, $timing, $emergencyAvailable, $discountOffered,
                $servicesOffered, $remarks, $status, $isVerified, $adminUserId
            ]);

            $newId = (int)$pdo->lastInsertId();

            // Admin Audit Log
            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'create_healthcare_provider', 'healthcare_providers', $newId, "Created provider {$providerCode} - {$name}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Healthcare Provider '{$name}' ({$providerCode}) added successfully.",
                    'provider_id' => $newId,
                    'provider_code' => $providerCode
                ]);
                exit;
            }

            setFlash('success', "Healthcare Provider '{$name}' ({$providerCode}) added successfully.");
            header('Location: ../healthcare_directory.php');
            exit;
        }

        // ------------------------------------------------------
        // 2. UPDATE HEALTHCARE PROVIDER
        // ------------------------------------------------------
        elseif ($action === 'update_provider') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid provider ID specified for update.');
            }

            // Check existence
            $stmtCheck = $pdo->prepare("SELECT * FROM healthcare_providers WHERE id = ? LIMIT 1");
            $stmtCheck->execute([$id]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                throw new Exception('Healthcare provider not found.');
            }

            $name = cleanInput($_POST['name'] ?? '');
            $type = cleanInput($_POST['type'] ?? 'hospital');
            $speciality = cleanInput($_POST['speciality'] ?? 'other');
            $specialityCustom = cleanInput($_POST['speciality_custom'] ?? '');
            $contactPerson = cleanInput($_POST['contact_person'] ?? '');
            $contact = cleanInput($_POST['contact'] ?? '');
            $alternateContact = cleanInput($_POST['alternate_contact'] ?? '');
            $email = cleanInput($_POST['email'] ?? '');
            $website = cleanInput($_POST['website'] ?? '');
            $state = cleanInput($_POST['state'] ?? '');
            $district = cleanInput($_POST['district'] ?? '');
            $block = cleanInput($_POST['block'] ?? '');
            $pincode = cleanInput($_POST['pincode'] ?? '');
            $address = cleanInput($_POST['address'] ?? '');
            $landmark = cleanInput($_POST['landmark'] ?? '');

            // Map Location & Geolocation fields
            $rawMapLocation = trim($_POST['map_location'] ?? '');
            $rawLat = trim($_POST['latitude'] ?? '');
            $rawLng = trim($_POST['longitude'] ?? '');
            $rawEmbed = trim($_POST['map_embed_url'] ?? '');

            $parsedMap = parseGoogleMapLocationInput(
                $rawMapLocation,
                $rawLat !== '' ? $rawLat : null,
                $rawLng !== '' ? $rawLng : null,
                $rawEmbed !== '' ? $rawEmbed : null,
                $address,
                $district,
                $state,
                $name
            );
            $mapLocation = $parsedMap['map_location'];
            $latitude = $parsedMap['latitude'];
            $longitude = $parsedMap['longitude'];
            $mapEmbedUrl = $parsedMap['map_embed_url'];

            $timing = cleanInput($_POST['timing'] ?? '');
            $emergencyAvailable = isset($_POST['emergency_available']) && $_POST['emergency_available'] == '1' ? 1 : 0;
            $discountOffered = cleanInput($_POST['discount_offered'] ?? '');
            $servicesOffered = cleanInput($_POST['services_offered'] ?? '');
            $remarks = cleanInput($_POST['remarks'] ?? '');
            $status = cleanInput($_POST['status'] ?? 'active');
            $isVerified = isset($_POST['is_verified']) && $_POST['is_verified'] == '1' ? 1 : 0;

            if (empty($name)) {
                throw new Exception('Healthcare Provider / Facility Name is required.');
            }
            if (empty($contact)) {
                throw new Exception('Primary Contact Number is required.');
            }
            if (empty($state) || empty($district)) {
                throw new Exception('State and District are required.');
            }
            if (empty($address)) {
                throw new Exception('Address is required.');
            }

            $allowedTypes = ['hospital', 'clinic', 'pathology_lab', 'pharmacy', 'doctor'];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'hospital';
            }

            $allowedSpecialities = ['eye', 'dental', 'other'];
            if (!in_array($speciality, $allowedSpecialities, true)) {
                $speciality = 'other';
            }

            $allowedStatuses = ['active', 'inactive', 'pending_approval'];
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'active';
            }

            // Handle Photo Upload (Keep old if not uploaded)
            $photoPath = $existing['photo'];
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadDir = __DIR__ . '/../../uploads/healthcare';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $val = validateUploadedFile(
                    $_FILES['photo'],
                    ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    5 * 1024 * 1024,
                    ['jpg', 'jpeg', 'png', 'webp', 'gif']
                );

                if (!$val['success']) {
                    throw new Exception('Photo upload error: ' . ($val['message'] ?? 'Invalid file'));
                }

                $stored = storeValidatedUpload($_FILES['photo'], $uploadDir, 'uploads/healthcare', 'hcp');
                if ($stored['success']) {
                    // Remove old photo if exists and is local
                    if (!empty($existing['photo'])) {
                        $oldFullPath = __DIR__ . '/../../' . ltrim($existing['photo'], '/');
                        if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                            @unlink($oldFullPath);
                        }
                    }
                    $photoPath = $stored['relative_path'];
                }
            }

            $stmtUpdate = $pdo->prepare("
                UPDATE healthcare_providers SET
                    name = ?,
                    type = ?,
                    speciality = ?,
                    speciality_custom = ?,
                    contact_person = ?,
                    contact = ?,
                    alternate_contact = ?,
                    email = ?,
                    website = ?,
                    state = ?,
                    district = ?,
                    block = ?,
                    pincode = ?,
                    address = ?,
                    landmark = ?,
                    map_location = ?,
                    latitude = ?,
                    longitude = ?,
                    map_embed_url = ?,
                    photo = ?,
                    timing = ?,
                    emergency_available = ?,
                    discount_offered = ?,
                    services_offered = ?,
                    remarks = ?,
                    status = ?,
                    is_verified = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");

            $stmtUpdate->execute([
                $name, $type, $speciality, $specialityCustom,
                $contactPerson, $contact, $alternateContact, $email, $website,
                $state, $district, $block, $pincode, $address, $landmark,
                $mapLocation, $latitude, $longitude, $mapEmbedUrl,
                $photoPath, $timing, $emergencyAvailable, $discountOffered,
                $servicesOffered, $remarks, $status, $isVerified,
                $id
            ]);

            // Admin Audit Log
            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'update_healthcare_provider', 'healthcare_providers', $id, "Updated provider {$existing['provider_code']} - {$name}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Healthcare Provider '{$name}' updated successfully."
                ]);
                exit;
            }

            setFlash('success', "Healthcare Provider '{$name}' updated successfully.");
            header('Location: ../healthcare_directory.php');
            exit;
        }

        // ------------------------------------------------------
        // 3. DELETE HEALTHCARE PROVIDER
        // ------------------------------------------------------
        elseif ($action === 'delete_provider') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid provider ID specified for deletion.');
            }

            $stmt = $pdo->prepare("SELECT * FROM healthcare_providers WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$provider) {
                throw new Exception('Healthcare provider not found or already deleted.');
            }

            // Remove photo if exists
            if (!empty($provider['photo'])) {
                $photoPath = __DIR__ . '/../../' . ltrim($provider['photo'], '/');
                if (file_exists($photoPath) && is_file($photoPath)) {
                    @unlink($photoPath);
                }
            }

            // Delete record (associated services & referrals cascade via DB constraints)
            $delStmt = $pdo->prepare("DELETE FROM healthcare_providers WHERE id = ?");
            $delStmt->execute([$id]);

            if (function_exists('admin_audit_log')) {
                admin_audit_log($pdo, 'delete_healthcare_provider', 'healthcare_providers', $id, "Deleted provider {$provider['provider_code']} - {$provider['name']}");
            }

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Healthcare Provider '{$provider['name']}' ({$provider['provider_code']}) deleted successfully."
                ]);
                exit;
            }

            setFlash('success', "Healthcare Provider '{$provider['name']}' ({$provider['provider_code']}) deleted successfully.");
            header('Location: ../healthcare_directory.php');
            exit;
        }

        // ------------------------------------------------------
        // 4. TOGGLE STATUS
        // ------------------------------------------------------
        elseif ($action === 'toggle_status') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid provider ID.');
            }

            $stmt = $pdo->prepare("SELECT status, name FROM healthcare_providers WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Provider not found.');
            }

            $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
            $upd = $pdo->prepare("UPDATE healthcare_providers SET status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newStatus, $id]);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => "Status changed to " . ucfirst($newStatus),
                    'new_status' => $newStatus
                ]);
                exit;
            }

            setFlash('success', "Status of '{$row['name']}' changed to " . ucfirst($newStatus));
            header('Location: ../healthcare_directory.php');
            exit;
        }

        // ------------------------------------------------------
        // 5. TOGGLE VERIFIED BADGE
        // ------------------------------------------------------
        elseif ($action === 'toggle_verified') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception('Invalid provider ID.');
            }

            $stmt = $pdo->prepare("SELECT is_verified, name FROM healthcare_providers WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Provider not found.');
            }

            $newVal = ((int)$row['is_verified'] === 1) ? 0 : 1;
            $upd = $pdo->prepare("UPDATE healthcare_providers SET is_verified = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newVal, $id]);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => true,
                    'message' => $newVal === 1 ? 'Provider verified.' : 'Verification removed.',
                    'is_verified' => $newVal
                ]);
                exit;
            }

            setFlash('success', $newVal === 1 ? 'Provider verified.' : 'Verification removed.');
            header('Location: ../healthcare_directory.php');
            exit;
        }

        else {
            throw new Exception("Unknown action: {$action}");
        }

    } catch (Throwable $e) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }

        setFlash('error', $e->getMessage());
        header('Location: ../healthcare_directory.php');
        exit;
    }
}
