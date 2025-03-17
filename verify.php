<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';

if (!empty($token)) {
    if ($auth->verifyEmail($token)) {
        $message = 'Your email has been verified successfully. You can now login.';
    } else {
        $message = 'Invalid or expired verification token.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Email Verification
                </h2>
            </div>
            <div class="bg-white p-8 rounded-lg shadow">
                <?php if (!empty($message)): ?>
                    <div class="rounded-md <?php echo strpos($message, 'successfully') !== false ? 'bg-green-50' : 'bg-red-50'; ?> p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <?php if (strpos($message, 'successfully') !== false): ?>
                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium <?php echo strpos($message, 'successfully') !== false ? 'text-green-800' : 'text-red-800'; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="text-center">
                    <a href="login.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        Go to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>