<?php
// auth/verify_otp.php
require_once __DIR__ . '/../db/DB.php';
require_once __DIR__ . '/../includes/otp.php';

$db = new DB(); 
$pdo = $db->connect();

$email = $_GET['email'] ?? ($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otpIn = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare("SELECT user_id, full_name, otp_hash, otp_expiry, otp_attempts, otp_purpose
                           FROM users WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $user = $stmt->fetch();

    if (!$user || $user['otp_purpose'] !== 'signup') {
        die("Invalid request!");
    }

    if (!$user['otp_expiry'] || time() > strtotime($user['otp_expiry'])) {
        die("Verification code has expired.");
    }

    if ($user['otp_attempts'] >= 5) {
        die("Too many incorrect attempts.");
    }

    if (!password_verify($otpIn, $user['otp_hash'])) {
        increment_attempts($pdo, (int)$user['user_id']);
        die("Invalid verification code.");
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE users SET email_verified = 1, status = 'active' WHERE user_id = :id")
            ->execute([':id' => $user['user_id']]);
        reset_otp_fields($pdo, (int)$user['user_id']);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }

    echo "Account verified successfully! You can now login.";
    exit;
}
?>

<form method="POST">
    <h2>Verify OTP</h2>
    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <input type="text" name="otp" placeholder="Enter 6-digit OTP" required><br><br>
    <button type="submit">Verify Code</button>
</form>
