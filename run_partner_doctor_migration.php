<?php
require_once __DIR__ . '/config/db.php';

try {
    $sql = file_get_contents(__DIR__ . '/database/create_partner_office_doctor_management.sql');
    $pdo->exec($sql);
    echo "Partner Office & Doctor Management Migration applied successfully!\n";

    $docCount = $pdo->query("SELECT COUNT(*) FROM doctor_agreements")->fetchColumn();
    $letterCount = $pdo->query("SELECT COUNT(*) FROM staff_letters")->fetchColumn();

    echo "Total Doctor Agreements: $docCount\n";
    echo "Total Staff/Volunteer Letters: $letterCount\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
