<?php
$password = 'admin123';
// Generate the hash using the PHP algorithm
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Copy the hash below (including the \$): <br>";
echo $hash;
?>