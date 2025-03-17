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

// Handle event actions (create, update, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['event_id'])) {
            $event->deleteEvent($_POST['event_id']);
        } elseif (in_array($_POST['action'], ['create', 'update'])) {
            $eventData = [
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'event_date' => $_POST['event_date'],
                'location' => $_POST['location'],
                'max_participants' => $_POST['max_participants'],
                'status' => $_POST['status'] ?? 'upcoming'
            ];
            
            if ($_POST['action'] === 'create') {
                $eventData['created_by'] = $_SESSION['user_id'];
                $event->createEvent($eventData);
            } else {
                $event->updateEvent($_POST['event_id'], $eventData);
            }
        }
        
        header('Location: events.php');
        exit();
    }
}

// Get all events
$query = "SELECT * FROM events ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Events Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white p-4">
            <div class="text-2xl font-bold mb-8">Admin Panel</div>
            <nav>
                <a href="dashboard.php" class="block py-2 px-4 hover:bg-gray-700 rounded mb-2">Dashboard</a>
                <a href="events.php" class="block py-2 px-4 bg-gray-700 rounded mb-2">Manage Events</a>
                <a href="users.php" class="block py-2 px-4 hover:bg-gray-700 rounded mb-2">Manage Users</a>
                <a href="../logout.php" class="block py-2 px-4 hover:bg-gray-700 rounded mt-8 text-red-400">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Manage Events</h1>
                <button onclick="document.getElementById('createEventModal').classList.remove('hidden')"
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Create New Event
                </button>
            </div>

            <!-- Events Table -->
            <div class="bg-white rounded-lg shadow p-6">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b-2 border-gray-200">
                            <th class="pb-3">Title</th>
                            <th class="pb-3">Date</th>
                            <th class="pb-3">Location</th>
                            <th class="pb-3">Status</th>
                            <th class="pb-3">Participants</th>
                            <th class="pb-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $evt): ?>
                        <tr class="border-b border-gray-100">
                            <td class="py-3"><?php echo htmlspecialchars($evt['title']); ?></td>
                            <td class="py-3"><?php echo date('M d, Y', strtotime($evt['event_date'])); ?></td>
                            <td class="py-3"><?php echo htmlspecialchars($evt['location']); ?></td>
                            <td class="py-3">
                                <span class="px-2 py-1 rounded text-sm <?php 
                                    echo match($evt['status']) {
                                        'upcoming' => 'bg-blue-100 text-blue-800',
                                        'ongoing' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-gray-100 text-gray-800'
                                    };
                                ?>">
                                    <?php echo ucfirst($evt['status']); ?>
                                </span>
                            </td>
                            <td class="py-3"><?php echo $evt['max_participants']; ?></td>
                            <td class="py-3">
                                <button onclick="editEvent(<?php echo htmlspecialchars(json_encode($evt)); ?>)"
                                        class="text-blue-600 hover:text-blue-800 mr-3">Edit</button>
                                <form method="POST" class="inline-block">
                                    <input type="hidden" name="event_id" value="<?php echo $evt['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" 
                                            class="text-red-600 hover:text-red-800"
                                            onclick="return confirm('Are you sure you want to delete this event?')">
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

    <!-- Create Event Modal -->
    <div id="createEventModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4">Create New Event</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                        <input type="text" name="title" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" required
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Date</label>
                        <input type="datetime-local" name="event_date" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Location</label>
                        <input type="text" name="location" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Max Participants</label>
                        <input type="number" name="max_participants" required min="1"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="document.getElementById('createEventModal').classList.add('hidden')"
                                class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">Cancel</button>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editEventModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4">Edit Event</h3>
                <form method="POST" id="editEventForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                        <input type="text" name="title" id="edit_title" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                        <textarea name="description" id="edit_description" required
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Date</label>
                        <input type="datetime-local" name="event_date" id="edit_event_date" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Location</label>
                        <input type="text" name="location" id="edit_location" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Max Participants</label>
                        <input type="number" name="max_participants" id="edit_max_participants" required min="1"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select name="status" id="edit_status" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="document.getElementById('editEventModal').classList.add('hidden')"
                                class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">Cancel</button>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function editEvent(event) {
        document.getElementById('edit_event_id').value = event.id;
        document.getElementById('edit_title').value = event.title;
        document.getElementById('edit_description').value = event.description;
        document.getElementById('edit_event_date').value = event.event_date.slice(0, 16);
        document.getElementById('edit_location').value = event.location;
        document.getElementById('edit_max_participants').value = event.max_participants;
        document.getElementById('edit_status').value = event.status;
        document.getElementById('editEventModal').classList.remove('hidden');
    }
    </script>
</body>
</html>