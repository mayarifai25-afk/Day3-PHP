<?php
// auth/login_otp.php
require_once __DIR__ . '/../db/DB.php';
require_once __DIR__ . '/../includes/otp.php';

session_start();

if (empty($_SESSION['pre_auth_user_id'])) {
    header("Location: /auth/login.php"); 
    exit;
}

$db = new DB(); 
$pdo = $db->connect();
$userId = (int)$_SESSION['pre_auth_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpIn = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare("SELECT user_id, full_name, role, otp_hash, otp_expiry, otp_attempts, otp_purpose
                           FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || $user['otp_purpose'] !== 'login') {
        die("Invalid request!");
    }

    if (!$user['otp_expiry'] || time() > strtotime($user['otp_expiry'])) {
        die("OTP code has expired.");
    }

    if ($user['otp_attempts'] >= 5) {
        die("Too many attempts. Please login again.");
    }

    if (!password_verify($otpIn, $user['otp_hash'])) {
        increment_attempts($pdo, $userId);
        die("Invalid login code.");
    }

    $pdo->beginTransaction();
    try {
        reset_otp_fields($pdo, $userId);
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :id")
            ->execute([':id' => $userId]);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role']    = $user['role'];
    unset($_SESSION['pre_auth_user_id']);

    echo "<h2 style='color: green;'>Login successful! Welcome " . htmlspecialchars($user['full_name']) . "</h2>";
    exit;
}
?>

<!-- فورم إدخال رمز الدخول -->
<form method="POST">
    <h2>Enter Login OTP (2FA)</h2>
    <input type="text" name="otp" placeholder="Enter 6-digit OTP" required><br><br>
    <button type="submit">Verify & Login</button>
</form>
