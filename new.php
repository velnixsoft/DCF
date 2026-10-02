<?php
// Plain text password from a registration form
$userPassword = "anuj1365"; 

// Generate a secure hash using the default algorithm
$hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

echo $hashedPassword;
?>