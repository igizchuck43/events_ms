<?php
require_once '../includes/auth.php';
require_once '../config/database.php';

// Ensure only admin users can access
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle user actions (delete, toggle admin status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $user_id = $_POST['user_id'] ?? null;
        
        if ($user_id) {
            if ($_POST['action'] === 'delete') {
                $query = "DELETE FROM users WHERE id = :id AND id != :current_user";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $user_id);
                $stmt->bindParam(':current_user', $_SESSION['user_id']);
                $stmt->execute();
            } elseif ($_POST['action'] === 'toggle_admin') {
                $query = "UPDATE users SET is_admin = NOT is_admin WHERE id = :id AND id != :current_user";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $user_id);
                $stmt->bindParam(':current_user', $_SESSION['user_id']);
                $stmt->execute();
            }
        }
        
        header('Location: users.php');
        exit();
    }
}

// Get all users except current user
$query = "SELECT * FROM users WHERE id != :current_user ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':current_user', $_SESSION['user_id']);
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white p-4">
            <div class="text-2xl font-bold mb-8">Admin Panel</div>
            <nav>
                <a href="dashboard.php" class="block py-2 px-4 hover:bg-gray-700 rounded mb-2">Dashboard</a>
                <a href="events.php" class="block py-2 px-4 hover:bg-gray-700 rounded mb-2">Manage Events</a>
                <a href="users.php" class="block py-2 px-4 bg-gray-700 rounded mb-2">Manage Users</a>
                <a href="../logout.php" class="block py-2 px-4 hover:bg-gray-700 rounded mt-8 text-red-400">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <h1 class="text-3xl font-bold mb-8">Manage Users</h1>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b-2 border-gray-200">
                            <th class="pb-3">Username</th>
                            <th class="pb-3">Email</th>
                            <th class="pb-3">Role</th>
                            <th class="pb-3">Joined Date</th>
                            <th class="pb-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-3"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="py-3"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded text-sm <?php echo $user['is_admin'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                </span>
                            </td>
                            <td class="py-3"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td class="py-3">
                                <form method="POST" class="inline-block mr-2">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <button type="submit" 
                                            class="text-purple-600 hover:text-purple-800"
                                            onclick="return confirm('Are you sure you want to <?php echo $user['is_admin'] ? 'remove' : 'grant'; ?> admin privileges?')">
                                        <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                    </button>
                                </form>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800"
                                            onclick="return confirm('Are you sure you want to delete this user?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>