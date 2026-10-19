<?php
// auth/register.php
require_once __DIR__ . '/../db/DB.php';
require_once __DIR__ . '/../includes/otp.php';

$db = new DB(); 
$pdo = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email    = trim($_POST['email']);
    $pass     = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($pass !== $confirm) {
        die("Passwords do not match!");
    }

  
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, status, email_verified)
                           VALUES (:n, :e, :h, 'user', 'pending', 0)");
    $stmt->execute([':n' => $fullName, ':e' => $email, ':h' => $hash]);
    $userId = (int)$pdo->lastInsertId();

    $otp     = generate_numeric_otp(6);
    $otpHash = hash_otp($otp);
    $expiry  = otp_expiry(10);
    $now     = date('Y-m-d H:i:s');

    $stmtOtp = $pdo->prepare("UPDATE users 
                              SET otp_hash = :oh, otp_expiry = :ex, otp_purpose = 'signup', otp_attempts = 0, otp_last_sent_at = :ls
                              WHERE user_id = :id");
    $stmtOtp->execute([':oh' => $otpHash, ':ex' => $expiry, ':ls' => $now, ':id' => $userId]);

    $subject = "Verify your email - Trading POS";
    $body    = "Hi $fullName,\n\nYour verification code is: $otp\nThis code expires in 10 minutes.";
    $headers = "From: no-reply@tradingpos.com";
    @mail($email, $subject, $body, $headers);

    echo "<div style='font-family: Arial; padding: 20px; background: #e8f4fd; border: 1px solid #b6e0fe; margin: 20px;'>";
    echo "<h3>Test Mode - OTP Generated Successfully!</h3>";
    echo "<p>Your OTP Code for <b>$email</b> is: <span style='font-size: 24px; color: #d9534f;'><b>$otp</b></span></p>";
    echo "<a href='/auth/verify-otp.php?email=" . urlencode($email) . "' style='padding: 10px 15px; background: #0275d8; color: white; text-decoration: none; border-radius: 5px;'>Go to Verify Page</a>";
    echo "</div>";
    exit;
}
?>

<form method="POST">
    <h2>Register</h2>
    <input type="text" name="full_name" placeholder="Full Name" required><br><br>
    <input type="email" name="email" placeholder="Email Address" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <input type="password" name="confirm_password" placeholder="Confirm Password" required><br><br>
    <button type="submit">Register & Send OTP</button>
</form>
