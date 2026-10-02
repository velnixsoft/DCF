<?php
session_start();
if (!empty($_SESSION['logged_in'])) {
    header('Location: admin/' );
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NGO Staff Login Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">NGO Staff Access</h1>
        <p class="text-gray-600 mb-8">Single secure login for Super Admin, State Admin, Area Manager and Field Agent.</p>
        <div class="grid md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white border rounded-xl p-4"><h2 class="font-bold">Super Admin</h2><p class="text-sm text-gray-500 mt-1">Full control and reports</p></div>
            <div class="bg-white border rounded-xl p-4"><h2 class="font-bold">State Admin</h2><p class="text-sm text-gray-500 mt-1">State-level supervision</p></div>
            <div class="bg-white border rounded-xl p-4"><h2 class="font-bold">Area Manager</h2><p class="text-sm text-gray-500 mt-1">Team + compliance tracking</p></div>
            <div class="bg-white border rounded-xl p-4"><h2 class="font-bold">Field Agent</h2><p class="text-sm text-gray-500 mt-1">Attendance + collection + payroll</p></div>
        </div>
        <a href="admin/" class="inline-flex items-center px-5 py-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">
            Go to Secure Login
        </a>
    </div>
</body>
</html>

