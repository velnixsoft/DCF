<?php
require_once 'c:/Users/dell/Downloads/Ngo Ai (2)/Ngo Ai/config/db.php';

try {
    $sql = file_get_contents('c:/Users/dell/Downloads/Ngo Ai (2)/Ngo Ai/database/create_career_guidance_cms.sql');
    $pdo->exec($sql);
    echo "Career Guidance CMS & Skill Courses Migration Executed Successfully!\n";
} catch (Throwable $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
