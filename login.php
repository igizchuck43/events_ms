<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Initialize rate limiting
$max_attempts = 5;
$lockout_time = 900; // 15 minutes in seconds
$ip_address = $_SERVER['REMOTE_ADDR'];

// Check for previous failed attempts
$query = "SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = :ip";
$stmt = $db->prepare($query);
$stmt->bindParam(':ip', $ip_address);
$stmt->execute();
$attempt_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($attempt_data) {
    if ($attempt_data['attempts'] >= $max_attempts && 
        time() - strtotime($attempt_data['last_attempt']) < $lockout_time) {
        die('Too many failed login attempts. Please try again later.');
    } elseif (time() - strtotime($attempt_data['last_attempt']) > $lockout_time) {
        // Reset attempts after lockout period
        $query = "UPDATE login_attempts SET attempts = 0 WHERE ip_address = :ip";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ip', $ip_address);
        $stmt->execute();
    }
}

$error = '';

if($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request';
    } else {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        
        try {
            if($auth->login($email, $password)) {
                // Reset failed attempts on successful login
                $query = "DELETE FROM login_attempts WHERE ip_address = :ip";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ip', $ip_address);
                $stmt->execute();
                
                header('Location: index.php');
                exit();
            } else {
                // Record failed attempt
                if ($attempt_data) {
                    $query = "UPDATE login_attempts SET attempts = attempts + 1, last_attempt = NOW() WHERE ip_address = :ip";
                } else {
                    $query = "INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (:ip, 1, NOW())";
                }
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ip', $ip_address);
                $stmt->execute();
                
                $error = 'Invalid email or password';
                error_log("Failed login attempt from IP: {$ip_address} with email: {$email}");
            }
        } catch(Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        create a new account
                    </a>
                </p>
            </div>
            
            <?php if($error): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800"><?php echo htmlspecialchars($error); ?></h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <form class="mt-8 space-y-6" action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Email address">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sign in
                    </button>
                </div>
                
                <div class="text-sm text-center">
                    <a href="reset_password.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Forgot your password?
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>