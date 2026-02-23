<?php
// File: hash_generator.php

$admin_pass = 'admin123';
$user_pass = 'user1';

$admin_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
$user_hash = password_hash($user_pass, PASSWORD_DEFAULT);

echo "Admin Password Hash (admin123): " . $admin_hash . "<br>";
echo "Arif User Hash (user1): " . $user_hash . "<br>";

// You must copy the output from the browser!
?>