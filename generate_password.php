<?php
// Generate proper password hash for password123
$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "<br>";
echo "Hash: " . $hash . "<br><br>";

// Test verification
$isValid = password_verify($password, $hash);
echo "Verification test: " . ($isValid ? "✅ Valid" : "❌ Invalid") . "<br>";
?>
