<?php
require_once 'auth.php';

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateJWT($user_id, $secret_key) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $user_id,
        'exp' => time() + (60 * 60 * 24) // 24 hours expiration
    ]);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret_key, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

function verifyJWT($token, $secret_key) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
    $signature = str_replace(['-', '_'], ['+', '/'], $parts[2]);
    
    $expectedSignature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $secret_key, true);
    $base64ExpectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
    
    if ($signature !== $base64ExpectedSignature) {
        return false;
    }
    
    $payloadData = json_decode($payload, true);
    if ($payloadData['exp'] < time()) {
        return false;
    }
    
    return $payloadData;
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sendVerificationEmail($email, $token) {
    $to = $email;
    $subject = 'Verify Your Email Address';
    $verification_link = 'http://' . $_SERVER['HTTP_HOST'] . '/verify.php?token=' . $token;
    
    $message = "Hello,\n\n";
    $message .= "Please click the following link to verify your email address:\n";
    $message .= $verification_link . "\n\n";
    $message .= "If you didn't create an account, please ignore this email.\n";
    
    $headers = 'From: noreply@eventsms.com' . "\r\n" .
        'Reply-To: noreply@eventsms.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, $headers);
}

function sendPasswordResetEmail($email, $token) {
    $to = $email;
    $subject = 'Reset Your Password';
    $reset_link = 'http://' . $_SERVER['HTTP_HOST'] . '/reset_password.php?token=' . $token;
    
    $message = "Hello,\n\n";
    $message .= "You have requested to reset your password. Click the following link to proceed:\n";
    $message .= $reset_link . "\n\n";
    $message .= "If you didn't request a password reset, please ignore this email.\n";
    
    $headers = 'From: noreply@eventsms.com' . "\r\n" .
        'Reply-To: noreply@eventsms.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    
    return mail($to, $subject, $message, $headers);
}