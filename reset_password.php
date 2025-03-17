<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$token = isset($_GET['token']) ? $_GET['token'] : '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        // Request password reset
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if ($auth->initiatePasswordReset($email)) {
            $message = 'If an account exists with this email, you will receive password reset instructions.';
            $messageType = 'success';
        } else {
            $message = 'An error occurred. Please try again later.';
            $messageType = 'error';
        }
    } else if (isset($_POST['password']) && isset($_POST['token'])) {
        // Reset password
        $password = $_POST['password'];
        $token = $_POST['token'];
        
        if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $messageType = 'error';
        } else if ($auth->resetPassword($token, $password)) {
            $message = 'Your password has been reset successfully. You can now login with your new password.';
            $messageType = 'success';
        } else {
            $message = 'Invalid or expired reset token.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <?php echo empty($token) ? 'Reset Password' : 'Set New Password'; ?>
                </h2>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="rounded-md <?php echo $messageType === 'success' ? 'bg-green-50' : 'bg-red-50'; ?> p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($messageType === 'success'): ?>
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
                            <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                                <?php echo htmlspecialchars($message); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="bg-white p-8 rounded-lg shadow">
                <?php if (empty($token)): ?>
                    <!-- Request Password Reset Form -->
                    <form class="space-y-6" action="reset_password.php" method="POST">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                            <div class="mt-1">
                                <input id="email" name="email" type="email" required 
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Send Reset Link
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Set New Password Form -->
                    <form class="space-y-6" action="reset_password.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <div class="mt-1">
                                <input id="password" name="password" type="password" required minlength="8"
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Reset Password
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="mt-4 text-center">
                    <a href="login.php" class="text-sm text-indigo-600 hover:text-indigo-500">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>