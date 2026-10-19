<?php
// includes/otp.php

function generate_numeric_otp(int $digits = 6): string {
    return (string) random_int(100000, 999999);
}

function hash_otp(string $otp): string {
    return password_hash($otp, PASSWORD_DEFAULT);
}

function otp_expiry(int $minutes = 10): string {
    return date('Y-m-d H:i:s', time() + $minutes * 60);
}

function can_resend_otp(?string $lastSentAt, int $cooldownSeconds = 60): bool {
    if (!$lastSentAt) return true;
    return (time() - strtotime($lastSentAt)) >= $cooldownSeconds;
}

function increment_attempts(PDO $pdo, int $userId): void {
    $pdo->prepare("UPDATE users SET otp_attempts = otp_attempts + 1 WHERE user_id = :id")
        ->execute([':id' => $userId]);
}

function reset_otp_fields(PDO $pdo, int $userId): void {
    $pdo->prepare("UPDATE users SET otp_hash = NULL, otp_expiry = NULL, otp_purpose = NULL, otp_attempts = 0 WHERE user_id = :id")
        ->execute([':id' => $userId]);
}
?>
