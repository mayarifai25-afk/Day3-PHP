<?php
// includes/otp.php

// 1. توليد رمز عشوائي آمن من 6 خانات
function generate_numeric_otp(int $digits = 6): string {
    return (string) random_int(100000, 999999);
}

// 2. تشفير الرمز أمنياً
function hash_otp(string $otp): string {
    return password_hash($otp, PASSWORD_DEFAULT);
}

// 3. تحديد وقت انتهاء الصلاحية (10 دقائق)
function otp_expiry(int $minutes = 10): string {
    return date('Y-m-d H:i:s', time() + $minutes * 60);
}

// 4. فحص فترة الانتظار لإعادة إرسال الرمز (60 ثانية)
function can_resend_otp(?string $lastSentAt, int $cooldownSeconds = 60): bool {
    if (!$lastSentAt) return true;
    return (time() - strtotime($lastSentAt)) >= $cooldownSeconds;
}

// 5. زيادة عدد المحاولات الخاطئة
function increment_attempts(PDO $pdo, int $userId): void {
    $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE user_id = :id")
        ->execute([':id' => $userId]);
}

// 6. تصفير بيانات الرمز بعد النجاح
function reset_otp_fields(PDO $pdo, int $userId): void {
    $pdo->prepare("UPDATE users SET otp_hash = NULL, otp_expiry = NULL, otp_purpose = NULL, otp_attempts = 0 WHERE user_id = :id")
        ->execute([':id' => $userId]);
}
?>