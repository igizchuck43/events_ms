<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/event.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$event = new Event($db);

if(!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$event_data = $event->getById($_GET['id']);
if(!$event_data) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

if($auth->isLoggedIn() && isset($_POST['join_event'])) {
    if($event->registerUser($event_data['id'], $_SESSION['user_id'])) {
        $success_message = 'Successfully joined the event!';
        $event_data = $event->getById($_GET['id']); // Refresh event data
    } else {
        $error_message = 'Failed to join event. It might be full.';
    }
}

$participants = $event->getParticipants($event_data['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event_data['title']); ?> - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-xl font-bold text-indigo-600">EventsMS</a>
                    </div>
                </div>
                <div class="flex items-center">
                    <?php if($auth->isLoggedIn()): ?>
                        <?php if($auth->isAdmin()): ?>
                            <a href="admin/dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="dashboard.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">My Events</a>
                        <form action="logout.php" method="POST" class="inline">
                            <button type="submit" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Logout</button>
                        </form>
                    <?php else: ?>
                        <a href="login.php" class="text-gray-700 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="register.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if($success_message): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if($error_message): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    <?php echo htmlspecialchars($event_data['title']); ?>
                </h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">
                    Created by <?php echo htmlspecialchars($event_data['creator_name']); ?>
                </p>
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo nl2br(htmlspecialchars($event_data['description'])); ?>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Date & Time</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo date('F j, Y g:i A', strtotime($event_data['event_date'])); ?>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo htmlspecialchars($event_data['location']); ?>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $event_data['status'] === 'upcoming' ? 'bg-green-100 text-green-800' : ($event_data['status'] === 'ongoing' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo ucfirst($event_data['status']); ?>
                            </span>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Available Slots</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <?php echo $event_data['max_participants'] - $event_data['current_participants']; ?> of <?php echo $event_data['max_participants']; ?>
                        </dd>
                    </div>
                </dl>
            </div>
            <?php if($auth->isLoggedIn() && $event_data['status'] === 'upcoming'): ?>
                <div class="px-4 py-5 sm:px-6">
                    <form method="POST">
                        <button type="submit" name="join_event" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700" <?php echo $event_data['current_participants'] >= $event_data['max_participants'] ? 'disabled' : ''; ?>>
                            Join Event
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <?php if($auth->isAdmin()): ?>
            <div class="mt-8">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Participants</h2>
                <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach($participants as $participant): ?>
                            <li class="px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($participant['username']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($participant['email']); ?></p>
                                    </div>
                                    <p class="text-sm text-gray-500">
                                        Joined: <?php echo date('M j, Y', strtotime($participant['registration_date'])); ?>
                                    </p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>