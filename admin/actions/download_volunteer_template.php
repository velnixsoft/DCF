<?php
session_start();
require '../../config/db.php';
require '../../includes/functions.php';

if (!isset($_SESSION['logged_in'])) {
    header('Location: ../index.php');
    exit;
}
if (!canAccessModule($pdo, 'coordinator', 'page.volunteers')) {
    header('Location: ../dashboard.php');
    exit;
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="volunteer_bulk_upload_template.csv"');

$headers = [
    'name',
    'email',
    'phone',
    'qualification',
    'profession',
    'marital_status',
    'blood_group',
    'address',
    'district',
    'state',
    'local_body_type',
    'local_body_name',
    'ward_no',
    'ward_name',
    'kudumbha_samithi'
];

$output = fopen('php://output', 'w');
fputcsv($output, $headers);

// Sample row
fputcsv($output, [
    'John Doe',
    'johndoe@example.com',
    '9876543210',
    'B.Tech',
    'Engineer',
    'Single',
    'O+',
    '123 Main Road',
    'Ernakulam',
    'Kerala',
    'Municipality',
    'Aluva',
    '15',
    'Aluva East',
    'Unit A'
]);

fclose($output);
exit;
