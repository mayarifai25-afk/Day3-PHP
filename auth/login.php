<?php
// auth/login.php
require_once __DIR__ . '/../db/DB.php';
require_once __DIR__ . '/../includes/otp.php';

session_start();
$db = new DB(); 
$pdo = $db->connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    // 1. فحص الإيميل وكلمة السر وتأكيد إن الحساب active
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, status
                           FROM users WHERE email = :e LIMIT 1");
    $stmt->execute([':e' => $email]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active' || !password_verify($pass, $user['password_hash'])) {
        die("Invalid credentials or account not active yet!");
    }

    // 2. توليد رمز OTP جديد للدخول (2FA)
    $otp     = generate_numeric_otp(6);
    $otpHash = hash_otp($otp);
    $expiry  = otp_expiry(10);
    $now     = date('Y-m-d H:i:s');

    $stmtOtp = $pdo->prepare("UPDATE users 
                              SET otp_hash = :oh, otp_expiry = :ex, otp_purpose = 'login', otp_attempts = 0, otp_last_sent_at = :ls
                              WHERE user_id = :id");
    $stmtOtp->execute([':oh' => $otpHash, ':ex' => $expiry, ':ls' => $now, ':id' => $user['user_id']]);

    // 3. حفظ الـ user_id بـ Session مؤقتة
    $_SESSION['pre_auth_user_id'] = (int)$user['user_id'];

    // 4. طباعة الرمز للتجربة
    echo "<div style='font-family: Arial; padding: 20px; background: #e8f4fd; border: 1px solid #b6e0fe; margin: 20px;'>";
    echo "<h3>Login 2FA - OTP Code Generated!</h3>";
    echo "<p>Your Login Code for <b>$email</b> is: <span style='font-size: 24px; color: #d9534f;'><b>$otp</b></span></p>";
    echo "<a href='/auth/login_otp.php' style='padding: 10px 15px; background: #0275d8; color: white; text-decoration: none; border-radius: 5px;'>Go to Login OTP Verification</a>";
    echo "</div>";
    exit;
}
?>

<!-- فورم تسجيل الدخول -->
<form method="POST">
    <h2>Login</h2>
    <input type="email" name="email" placeholder="Email Address" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login & Get OTP</button>
</form>