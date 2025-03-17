<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/event.php';

// Ensure only admin users can access
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../index.php');
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$event = new Event($db);

// Get dashboard statistics
$stats = [
    'total_events' => $event->getTotalEvents(),
    'upcoming_events' => $event->getEventsByStatus('upcoming'),
    'ongoing_events' => $event->getEventsByStatus('ongoing'),
    'completed_events' => $event->getEventsByStatus('completed')
];

// Get latest events
$latest_events = $event->getLatestEvents(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white p-4">
            <div class="text-2xl font-bold mb-8">Admin Panel</div>
            <nav>
                <a href="dashboard.php" class="block py-2 px-4 bg-gray-700 rounded mb-2">Dashboard</a>
                <a href="events.php" class="block py-2 px-4 hover:bg-gray-700 rounded mb-2">Manage Events</a>
                <a href="users.php" class="block py-2 px-4 hover:bg-gray-700 rounded mb-2">Manage Users</a>
                <a href="../logout.php" class="block py-2 px-4 hover:bg-gray-700 rounded mt-8 text-red-400">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <h1 class="text-3xl font-bold mb-8">Dashboard Overview</h1>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Total Events</h3>
                    <p class="text-3xl font-bold"><?php echo $stats['total_events']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Upcoming Events</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $stats['upcoming_events']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Ongoing Events</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo $stats['ongoing_events']; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-gray-500 text-sm">Completed Events</h3>
                    <p class="text-3xl font-bold text-gray-600"><?php echo $stats['completed_events']; ?></p>
                </div>
            </div>

            <!-- Latest Events Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Latest Events</h2>
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b-2 border-gray-200">
                            <th class="pb-3">Title</th>
                            <th class="pb-3">Date</th>
                            <th class="pb-3">Location</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest_events as $event): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-3"><?php echo htmlspecialchars($event['title']); ?></td>
                            <td class="py-3"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                            <td class="py-3"><?php echo htmlspecialchars($event['location']); ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded text-sm <?php 
                                    echo match($event['status']) {
                                        'upcoming' => 'bg-blue-100 text-blue-800',
                                        'ongoing' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <a href="edit_event.php?id=<?php echo $event['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                                <a href="view_event.php?id=<?php echo $event['id']; ?>" 
                                   class="text-green-600 hover:text-green-800">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mt-4 text-right">
                    <a href="events.php" class="text-blue-600 hover:text-blue-800">View All Events →</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>