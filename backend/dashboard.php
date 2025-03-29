<?php
session_start();
// include 'dbconfig/db_config.php';
include './dbconfig/dbconfig.php';


// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get logged-in user ID

// Fetch Total Usage of Logged-in User
$stmt = $conn->prepare("SELECT SUM(total_usage) AS total_usage FROM appliances WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalUsage = $row['total_usage'] ?? 0;
$stmt->close();

// Fetch Active Devices of Logged-in User
$stmt = $conn->prepare("SELECT COUNT(*) AS active_devices FROM appliances WHERE user_id = ? AND status = 'ON'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$activeDevices = $row['active_devices'] ?? 0;
$stmt->close();

// Fetch Pending Notifications of Logged-in User
$stmt = $conn->prepare("SELECT COUNT(*) AS pending_notifications FROM notifications WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$pendingNotifications = $row['pending_notifications'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Home Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-xl font-bold">Smart Home Dashboard</h1>
            <a href="javascript:void(0);" onclick="confirmLogout()" class="bg-red-500 px-4 py-2 rounded">Logout</a>
        </nav>
        
        <!-- Main Content -->
        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md p-5">
                <ul>
                    <li class="mb-4"><a href="dashboard.php" class="text-blue-600 font-bold">🏠 Dashboard</a></li>
                    <li class="mb-4"><a href="../frontend/appliances/control_appliance.html" class="text-blue-600">⚙️ Appliance Control</a></li>
                    <li class="mb-4"><a href="./appliances/usage.php" class="text-blue-600">📊 Usage Analytics</a></li>
                    <li class="mb-4"><a href="notifications.php" class="text-blue-600">🔔 Notifications</a></li>
                    <li class="mb-4"><a href="settings.php" class="text-blue-600">⚙️ Settings</a></li>
                </ul>
            </aside>
            
            <!-- Dashboard Content -->
            <main class="flex-1 p-6">
                <h2 class="text-2xl font-semibold mb-4">Welcome to Smart Home</h2>
                <div class="grid grid-cols-3 gap-6">
                    <!-- Cards -->
                    <div class="bg-white p-5 shadow rounded">
                        <h3 class="text-lg font-bold">Total Energy Usage</h3>
                        <p class="text-2xl text-blue-600"><?php echo $totalUsage; ?> kWh</p>
                    </div>
                    <div class="bg-white p-5 shadow rounded">
                        <h3 class="text-lg font-bold">Active Appliances</h3>
                        <p class="text-2xl text-green-600"><?php echo $activeDevices; ?></p>
                    </div>
                    <div class="bg-white p-5 shadow rounded">
                        <h3 class="text-lg font-bold">Pending Alerts</h3>
                        <p class="text-2xl text-red-600"><?php echo $pendingNotifications; ?></p>
                    </div>
                </div>
            </main>
        </div>
    </div>



    <script>
        function confirmLogout() {
            let confirmation = confirm("Are you sure you want to logout?");
            if (confirmation) {
                window.location.href = "./auth/logout.php"; // Redirect to logout page
            }
        }
    </script>
</body>
</html>
