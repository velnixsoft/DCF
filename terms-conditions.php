<?php
require 'includes/header.php';

$content = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'terms_conditions_content'")->fetchColumn();
?>

<style>
    .prose h1,
    .prose h2,
    .prose h3 {
        font-weight: 700;
        margin-top: 1.5em;
        margin-bottom: 0.5em;
    }

    .prose h1 {
        font-size: 2.25rem;
    }

    .prose h2 {
        font-size: 1.875rem;
    }

    .prose h3 {
        font-size: 1.5rem;
    }

    .prose p {
        line-height: 1.75;
        margin-bottom: 1.25em;
    }

    .prose ul {
        list-style-type: disc;
        padding-left: 1.5em;
        margin-bottom: 1.25em;
    }

    .prose li {
        margin-bottom: 0.5em;
    }

    .prose a {
        color: #2563EB;
        text-decoration: underline;
    }
</style>

<div class="bg-white min-h-screen">

    <div class="bg-blue-50 py-16 text-center border-b border-blue-200">
        <div class="container mx-auto px-6">
            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-800">Terms & Conditions</h1>
            <p class="mt-3 max-w-2xl mx-auto text-lg text-gray-600">Please read these terms and conditions carefully before using our website.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-lg shadow-md border border-gray-100">

            <div class="prose max-w-none text-gray-700">
                <?php
                if (!empty($content)) {
                    echo $content;
                } else {
                    echo '<h1>Terms & Conditions</h1><p>This page is currently being updated. Please check back later.</p>';
                }
                ?>
            </div>

        </div>
    </div>
</div>

<?php require 'includes/footer.php'; ?>