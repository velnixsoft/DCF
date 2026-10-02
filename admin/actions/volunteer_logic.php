<?php
require '../../config/db.php';
require '../../includes/functions.php';
require '../../includes/member_module.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
if (!canAccessModule($pdo, 'coordinator', 'page.volunteers')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

mm_ensure_volunteer_registration_columns($pdo);

function getNextVolunteerCardNo($pdo) {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'volunteer_prefix'");
    $prefix = $stmt->fetchColumn() ?: 'VOL-';

    $stmt = $pdo->query("SELECT id_card_no FROM volunteers WHERE id_card_no IS NOT NULL AND id_card_no != ''");
    $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $maxNum = 0;
    foreach ($existingIds as $idCardNo) {
        if (strpos($idCardNo, $prefix) === 0) {
            $numPart = substr($idCardNo, strlen($prefix));
            if (is_numeric($numPart)) {
                $num = (int)$numPart;
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        } else {
            if (preg_match('/VOL-(\d+)/i', $idCardNo, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
    }

    $nextNum = $maxNum + 1;
    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        setFlash('error', 'Token Invalid');
        header('Location: ../volunteers.php');
        exit;
    }

    $action = $_POST['action'];

    if ($action === 'bulk_upload') {
        if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
            setFlash('error', 'Failed to upload file.');
            header('Location: ../volunteers.php');
            exit;
        }

        $fileTmpPath = $_FILES['upload_file']['tmp_name'];
        $fileName = $_FILES['upload_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $rows = [];

        if ($fileExtension === 'xlsx') {
            require_once '../../libs/SimpleXLSX.php';
            if ($xlsx = \Shuchkin\SimpleXLSX::parse($fileTmpPath)) {
                $rows = $xlsx->rows();
            } else {
                setFlash('error', 'Failed to parse Excel file: ' . \Shuchkin\SimpleXLSX::parseError());
                header('Location: ../volunteers.php');
                exit;
            }
        } elseif ($fileExtension === 'csv') {
            if (($handle = fopen($fileTmpPath, 'r')) !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rows[] = $row;
                }
                fclose($handle);
            } else {
                setFlash('error', 'Failed to open CSV file.');
                header('Location: ../volunteers.php');
                exit;
            }
        } else {
            setFlash('error', 'Only .xlsx (Excel) and .csv files are supported.');
            header('Location: ../volunteers.php');
            exit;
        }

        if (empty($rows)) {
            setFlash('error', 'The uploaded file is empty.');
            header('Location: ../volunteers.php');
            exit;
        }

        // The first row is the header
        $header = array_shift($rows);
        $header = array_map(function($h) { return strtolower(trim((string)$h)); }, $header);

        // Find mandatory fields
        $nameIdx = array_search('name', $header);
        $emailIdx = array_search('email', $header);
        $phoneIdx = array_search('phone', $header);

        if ($nameIdx === false || $emailIdx === false || $phoneIdx === false) {
            setFlash('error', 'File is missing one or more mandatory headers: name, email, phone.');
            header('Location: ../volunteers.php');
            exit;
        }

        // Map optional fields index
        $fields = [
            'qualification' => array_search('qualification', $header),
            'profession' => array_search('profession', $header),
            'marital_status' => array_search('marital_status', $header),
            'blood_group' => array_search('blood_group', $header),
            'address' => array_search('address', $header),
            'district' => array_search('district', $header),
            'state' => array_search('state', $header),
            'local_body_type' => array_search('local_body_type', $header),
            'local_body_name' => array_search('local_body_name', $header),
            'ward_no' => array_search('ward_no', $header),
            'ward_name' => array_search('ward_name', $header),
            'kudumbha_samithi' => array_search('kudumbha_samithi', $header)
        ];

        // Fetch prefix and max ID for card generation
        $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'volunteer_prefix'");
        $prefix = $stmt->fetchColumn() ?: 'VOL-';

        $stmt = $pdo->query("SELECT id_card_no FROM volunteers WHERE id_card_no IS NOT NULL AND id_card_no != ''");
        $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maxNum = 0;
        foreach ($existingIds as $idCardNo) {
            if (strpos($idCardNo, $prefix) === 0) {
                $numPart = substr($idCardNo, strlen($prefix));
                if (is_numeric($numPart)) {
                    $num = (int)$numPart;
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            } else {
                if (preg_match('/VOL-(\d+)/i', $idCardNo, $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
        }
        $currentMaxNum = $maxNum;

        $validFrom = date('Y-m-d');
        $validUntil = date('Y-m-d', strtotime('+1 year'));

        $successCount = 0;
        $skippedCount = 0;

        $pdo->beginTransaction();

        try {
            $insertSql = "INSERT INTO volunteers (
                name, email, phone, qualification, profession, marital_status, blood_group, address, 
                district, state, local_body_type, local_body_name, ward_no, ward_name, kudumbha_samithi, 
                status, id_card_no, valid_from, valid_until
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?)";
            $insertStmt = $pdo->prepare($insertSql);

            $checkSql = "SELECT COUNT(*) FROM volunteers WHERE email = ?";
            $checkStmt = $pdo->prepare($checkSql);

            foreach ($rows as $row) {
                // Skip completely empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                $name = isset($row[$nameIdx]) ? cleanInput($row[$nameIdx]) : '';
                $email = isset($row[$emailIdx]) ? filter_var(trim($row[$emailIdx]), FILTER_SANITIZE_EMAIL) : '';
                $phone = isset($row[$phoneIdx]) ? cleanInput($row[$phoneIdx]) : '';

                // Validate mandatory fields
                if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($phone)) {
                    $skippedCount++;
                    continue;
                }

                // Check duplicate email
                $checkStmt->execute([$email]);
                if ($checkStmt->fetchColumn() > 0) {
                    $skippedCount++;
                    continue;
                }

                // Gather optionals
                $qualVal = ($fields['qualification'] !== false && isset($row[$fields['qualification']])) ? cleanInput($row[$fields['qualification']]) : null;
                $profVal = ($fields['profession'] !== false && isset($row[$fields['profession']])) ? cleanInput($row[$fields['profession']]) : null;
                $maritalVal = ($fields['marital_status'] !== false && isset($row[$fields['marital_status']])) ? cleanInput($row[$fields['marital_status']]) : null;
                $bloodVal = ($fields['blood_group'] !== false && isset($row[$fields['blood_group']])) ? cleanInput($row[$fields['blood_group']]) : null;
                $addressVal = ($fields['address'] !== false && isset($row[$fields['address']])) ? cleanInput($row[$fields['address']]) : null;
                $districtVal = ($fields['district'] !== false && isset($row[$fields['district']])) ? cleanInput($row[$fields['district']]) : null;
                $stateVal = ($fields['state'] !== false && isset($row[$fields['state']])) ? cleanInput($row[$fields['state']]) : null;
                $localTypeVal = ($fields['local_body_type'] !== false && isset($row[$fields['local_body_type']])) ? cleanInput($row[$fields['local_body_type']]) : null;
                $localNameVal = ($fields['local_body_name'] !== false && isset($row[$fields['local_body_name']])) ? cleanInput($row[$fields['local_body_name']]) : null;
                $wardNoVal = ($fields['ward_no'] !== false && isset($row[$fields['ward_no']])) ? cleanInput($row[$fields['ward_no']]) : null;
                $wardNameVal = ($fields['ward_name'] !== false && isset($row[$fields['ward_name']])) ? cleanInput($row[$fields['ward_name']]) : null;
                $samithiVal = ($fields['kudumbha_samithi'] !== false && isset($row[$fields['kudumbha_samithi']])) ? cleanInput($row[$fields['kudumbha_samithi']]) : null;

                if ($addressVal && strlen($addressVal) > 250) {
                    $addressVal = substr($addressVal, 0, 250);
                }

                $nextNum = ++$currentMaxNum;
                $idCardNo = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                $insertStmt->execute([
                    $name,
                    $email,
                    $phone,
                    $qualVal ?: null,
                    $profVal ?: null,
                    $maritalVal ?: null,
                    $bloodVal ?: null,
                    $addressVal ?: null,
                    $districtVal ?: null,
                    $stateVal ?: null,
                    $localTypeVal ?: null,
                    $localNameVal ?: null,
                    $wardNoVal ?: null,
                    $wardNameVal ?: null,
                    $samithiVal ?: null,
                    $idCardNo,
                    $validFrom,
                    $validUntil
                ]);

                $successCount++;
            }

            $pdo->commit();
            setFlash('success', "Bulk upload completed. Imported: $successCount, Skipped: $skippedCount.");
        } catch (Exception $ex) {
            $pdo->rollBack();
            setFlash('error', 'Import error: ' . $ex->getMessage());
        }

        header('Location: ../volunteers.php');
        exit;
    }

    if ($action === 'save') {
        $id = !empty($_POST['id']) ? $_POST['id'] : null;
        $name = cleanInput($_POST['name']);
        $email = cleanInput($_POST['email']);
        $phone = cleanInput($_POST['phone']);
        $qualification = cleanInput($_POST['qualification'] ?? '');
        $profession = cleanInput($_POST['profession'] ?? '');
        $maritalStatus = cleanInput($_POST['marital_status'] ?? '');
        $blood = cleanInput($_POST['blood_group']);
        $address = cleanInput($_POST['address']);
        $district = cleanInput($_POST['district'] ?? '');
        $state = cleanInput($_POST['state'] ?? '');
        $localBodyType = cleanInput($_POST['local_body_type'] ?? '');
        $localBodyName = cleanInput($_POST['local_body_name'] ?? '');
        $wardNo = cleanInput($_POST['ward_no'] ?? '');
        $wardName = cleanInput($_POST['ward_name'] ?? '');
        $kudumbhaSamithi = cleanInput($_POST['kudumbha_samithi'] ?? '');

        $checkSql = "SELECT id FROM volunteers WHERE email = ? AND id != ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$email, $id ?? 0]);

        if ($checkStmt->fetch()) {
            setFlash('error', 'This email address is already registered!');
            header('Location: ../volunteers.php');
            exit;
        }

        if (strlen($address) > 250) {
            setFlash('error', 'Address too long (Max 250 chars).');
            header('Location: ../volunteers.php');
            exit;
        }

        $validityType = $_POST['validity_type'] ?? '1Y';
        $customDate = $_POST['custom_date'] ?? null;
        $validUntil = null;

        if ($validityType === '1M') $validUntil = date('Y-m-d', strtotime('+1 month'));
        elseif ($validityType === '3M') $validUntil = date('Y-m-d', strtotime('+3 months'));
        elseif ($validityType === '6M') $validUntil = date('Y-m-d', strtotime('+6 months'));
        elseif ($validityType === '1Y') $validUntil = date('Y-m-d', strtotime('+1 year'));
        elseif ($validityType === 'Lifetime') $validUntil = '2099-12-31';
        elseif ($validityType === 'Custom' && $customDate) $validUntil = $customDate;

        $photoPath = $_POST['existing_photo'] ?? null;
        if (!empty($_FILES['photo']['name'])) {
            $targetDir = dirname(dirname(__DIR__)) . "/uploads/volunteers/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = basename($_FILES['photo']['name']);
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileType, ['jpg', 'jpeg', 'png'], true)) {
                setFlash('error', 'Only JPG, JPEG, or PNG photos are allowed.');
                header('Location: ../volunteers.php');
                exit;
            }

            $newFileName = time() . '_' . uniqid() . '.' . ($fileType === 'png' ? 'png' : 'jpg');
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetDir . $newFileName)) {
                $photoPath = 'uploads/volunteers/' . $newFileName;
            }
        }

        try {
            if ($id) {

                $sql = "UPDATE volunteers SET name=?, email=?, phone=?, qualification=?, profession=?, marital_status=?, blood_group=?, address=?, district=?, state=?, local_body_type=?, local_body_name=?, ward_no=?, ward_name=?, kudumbha_samithi=?, photo=?";
                $params = [
                    $name,
                    $email,
                    $phone,
                    $qualification ?: null,
                    $profession ?: null,
                    $maritalStatus ?: null,
                    $blood ?: null,
                    $address ?: null,
                    $district ?: null,
                    $state ?: null,
                    $localBodyType ?: null,
                    $localBodyName ?: null,
                    $wardNo ?: null,
                    $wardName ?: null,
                    $kudumbhaSamithi ?: null,
                    $photoPath
                ];

                if ($validUntil) {
                    $sql .= ", valid_until=?";
                    $params[] = $validUntil;
                }

                $sql .= " WHERE id=?";
                $params[] = $id;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                setFlash('success', 'Volunteer updated successfully.');
            } else {

                $idCardNo = getNextVolunteerCardNo($pdo);

                $validFrom = date('Y-m-d');
                if (!$validUntil) $validUntil = date('Y-m-d', strtotime('+1 year'));

                $sql = "INSERT INTO volunteers (name, email, phone, qualification, profession, marital_status, blood_group, address, district, state, local_body_type, local_body_name, ward_no, ward_name, kudumbha_samithi, photo, status, id_card_no, valid_from, valid_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $name,
                    $email,
                    $phone,
                    $qualification ?: null,
                    $profession ?: null,
                    $maritalStatus ?: null,
                    $blood ?: null,
                    $address ?: null,
                    $district ?: null,
                    $state ?: null,
                    $localBodyType ?: null,
                    $localBodyName ?: null,
                    $wardNo ?: null,
                    $wardName ?: null,
                    $kudumbhaSamithi ?: null,
                    $photoPath,
                    $idCardNo,
                    $validFrom,
                    $validUntil
                ]);
                setFlash('success', 'Volunteer added & approved.');
            }
        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                setFlash('error', 'Email already exists in the system.');
            } else {
                setFlash('error', 'Database Error: ' . $e->getMessage());
            }
        }
    }

    if ($action === 'approve') {
        $id = $_POST['id'];

        $check = $pdo->prepare("SELECT id_card_no FROM volunteers WHERE id = ?");
        $check->execute([$id]);

        if (!$check->fetchColumn()) {
            $idCardNo = getNextVolunteerCardNo($pdo);
            $validFrom = date('Y-m-d');
            $validUntil = date('Y-m-d', strtotime('+1 year'));

            $sql = "UPDATE volunteers SET status='Active', id_card_no=?, valid_from=?, valid_until=? WHERE id=?";
            $pdo->prepare($sql)->execute([$idCardNo, $validFrom, $validUntil, $id]);
        } else {
            $pdo->prepare("UPDATE volunteers SET status='Active' WHERE id=?")->execute([$id]);
        }
        setFlash('success', 'Volunteer Approved.');
    }

    if ($action === 'update_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $pdo->prepare("UPDATE volunteers SET status=? WHERE id=?")->execute([$status, $id]);
        setFlash('success', "Status updated to $status.");
    }

    if ($action === 'renew') {
        $id = $_POST['id'];
        $pdo->prepare("UPDATE volunteers SET valid_until = DATE_ADD(valid_until, INTERVAL 1 YEAR), status='Active' WHERE id=?")->execute([$id]);
        setFlash('success', 'Validity Extended by 1 Year.');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("SELECT photo FROM volunteers WHERE id = ?");
            $stmt->execute([$id]);
            $photo = $stmt->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM volunteers WHERE id = ?");
            $stmt->execute([$id]);

            if ($photo && file_exists('../../' . $photo)) {
                @unlink('../../' . $photo);
            }

            setFlash('success', 'Volunteer deleted successfully.');
        } catch (PDOException $e) {
            setFlash('error', 'Database Error: ' . $e->getMessage());
        }
    }

    header('Location: ../volunteers.php');
    exit;
}
