<?php
// ============================================================
// admin/actions/item_donation_category_logic.php
// Handles Add, Edit, Delete, Toggle for Item Donation Categories
// Follows existing designation CRUD pattern.
// ============================================================

require '../../config/db.php';
require '../../includes/functions.php';

// Auth check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

if (!canAccessModule($pdo, 'manager', 'page.item_categories') && !canAccessModule($pdo, 'manager', 'page.donations')) {
    setFlash('error', 'Access denied.');
    header('Location: ../dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../item_donation_categories.php');
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    setFlash('error', 'Invalid security token.');
    header('Location: ../item_donation_categories.php');
    exit;
}

$action = cleanInput($_POST['action'] ?? '');

// ── 1. ADD CATEGORY ──────────────────────────────────────────
if ($action === 'add_category') {
    $name = cleanInput($_POST['category_name'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', cleanInput($_POST['category_slug'] ?? $name)), '-'));
    $icon = cleanInput($_POST['category_icon'] ?? 'fa-box');
    $description = cleanInput($_POST['description'] ?? '');
    $units = cleanInput($_POST['unit_suggestions'] ?? 'pcs, kg, boxes, sets');
    $displayOrder = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT) ?: 0;
    $isActive = isset($_POST['is_active']) ? 1 : 1;

    if (empty($name)) {
        setFlash('error', 'Category name is required.');
        header('Location: ../item_donation_categories.php');
        exit;
    }

    if (empty($slug)) {
        $slug = 'cat-' . time();
    }

    try {
        // Check uniqueness
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM item_donation_categories WHERE category_name = ? OR category_slug = ?");
        $checkStmt->execute([$name, $slug]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            setFlash('error', 'A category with this name or slug already exists.');
            header('Location: ../item_donation_categories.php');
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO item_donation_categories 
                (category_name, category_slug, category_icon, description, unit_suggestions, is_active, display_order) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $icon, $description, $units, $isActive, $displayOrder]);

        setFlash('success', 'Item donation category "' . htmlspecialchars($name) . '" created successfully.');
    } catch (PDOException $e) {
        error_log("add_category error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: ../item_donation_categories.php');
    exit;
}

// ── 2. UPDATE CATEGORY ───────────────────────────────────────
if ($action === 'update_category') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = cleanInput($_POST['category_name'] ?? '');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', cleanInput($_POST['category_slug'] ?? $name)), '-'));
    $icon = cleanInput($_POST['category_icon'] ?? 'fa-box');
    $description = cleanInput($_POST['description'] ?? '');
    $units = cleanInput($_POST['unit_suggestions'] ?? 'pcs, kg, boxes, sets');
    $displayOrder = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT) ?: 0;
    $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (!$id || empty($name)) {
        setFlash('error', 'Valid category ID and name are required.');
        header('Location: ../item_donation_categories.php');
        exit;
    }

    try {
        // Check duplicate name on other rows
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM item_donation_categories WHERE (category_name = ? OR category_slug = ?) AND id != ?");
        $checkStmt->execute([$name, $slug, $id]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            setFlash('error', 'Another category with this name or slug already exists.');
            header('Location: ../item_donation_categories.php');
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE item_donation_categories 
            SET category_name = ?, category_slug = ?, category_icon = ?, description = ?, 
                unit_suggestions = ?, is_active = ?, display_order = ? 
            WHERE id = ?
        ");
        $stmt->execute([$name, $slug, $icon, $description, $units, $isActive, $displayOrder, $id]);

        setFlash('success', 'Category "' . htmlspecialchars($name) . '" updated successfully.');
    } catch (PDOException $e) {
        error_log("update_category error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: ../item_donation_categories.php');
    exit;
}

// ── 3. TOGGLE ACTIVE STATUS ──────────────────────────────────
if ($action === 'toggle_category') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $newStatus = filter_input(INPUT_POST, 'is_active', FILTER_VALIDATE_INT);

    if (!$id) {
        setFlash('error', 'Invalid category ID.');
        header('Location: ../item_donation_categories.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE item_donation_categories SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus ? 1 : 0, $id]);
        setFlash('success', 'Category status updated to ' . ($newStatus ? 'Active' : 'Inactive') . '.');
    } catch (PDOException $e) {
        error_log("toggle_category error: " . $e->getMessage());
        setFlash('error', 'Database error: ' . $e->getMessage());
    }

    header('Location: ../item_donation_categories.php');
    exit;
}

// ── 4. DELETE CATEGORY ───────────────────────────────────────
if ($action === 'delete_category') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$id) {
        setFlash('error', 'Invalid category ID.');
        header('Location: ../item_donation_categories.php');
        exit;
    }

    try {
        // Check if items exist under this category
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM item_donations WHERE category_id = ?");
        $countStmt->execute([$id]);
        $linkedCount = (int)$countStmt->fetchColumn();

        if ($linkedCount > 0) {
            setFlash('error', 'Cannot delete this category because it has ' . $linkedCount . ' item donation(s) linked to it. You can deactivate it instead.');
            header('Location: ../item_donation_categories.php');
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM item_donation_categories WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', 'Category deleted successfully.');
    } catch (PDOException $e) {
        error_log("delete_category error: " . $e->getMessage());
        setFlash('error', 'Cannot delete category: ' . $e->getMessage());
    }

    header('Location: ../item_donation_categories.php');
    exit;
}

setFlash('error', 'Unknown action requested.');
header('Location: ../item_donation_categories.php');
exit;
