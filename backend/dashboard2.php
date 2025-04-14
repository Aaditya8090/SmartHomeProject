<?php
session_start();
include './dbconfig/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Total Usage
$stmt = $conn->prepare("SELECT SUM(total_usage) AS total_usage FROM appliances WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$totalUsage = $row['total_usage'] ?? 0;
$stmt->close();

// Fetch Active Devices
$stmt = $conn->prepare("SELECT COUNT(*) AS active_devices FROM appliances WHERE user_id = ? AND status = 'ON'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$activeDevices = $row['active_devices'] ?? 0;
$stmt->close();

// Fetch Pending Notifications
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
        <nav class="bg-blue-600 text-white p-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">Smart Home</h1>
            <button onclick="confirmLogout()" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded">
                Logout
            </button>
        </nav>
        
        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-56 bg-white p-4 shadow">
                <h3 class="font-bold text-gray-500 mb-4">Menu</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-2 bg-blue-100 text-blue-700 rounded font-medium">
                            <span class="mr-2">📊</span> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/control_appliance.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2">🔌</span> Appliance Control
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/usage_analytics.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2">📈</span> Usage Analytics
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/notification2.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2">🔔</span> Notifications
                            <?php if($pendingNotifications > 0): ?>
                            <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                <?php echo $pendingNotifications; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/setting.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2">⚙️</span> Settings
                        </a>
                    </li>
                </ul>
            </aside>
            
            <!-- Main Content -->
            <main class="flex-1 p-6">
                <h2 class="text-2xl font-bold mb-6">Dashboard Overview</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Energy Usage -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-600">Total Energy Usage</p>
                                <h3 class="text-2xl font-bold"><?php echo $totalUsage; ?> kWh</h3>
                            </div>
                            <span class="text-3xl">⚡</span>
                        </div>
                    </div>
                    
                    <!-- Active Appliances -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-600">Active Appliances</p>
                                <h3 class="text-2xl font-bold"><?php echo $activeDevices; ?></h3>
                            </div>
                            <span class="text-3xl">🏠</span>
                        </div>
                    </div>
                    
                    <!-- Pending Alerts -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-gray-600">Pending Alerts</p>
                                <h3 class="text-2xl font-bold"><?php echo $pendingNotifications; ?></h3>
                            </div>
                            <span class="text-3xl">⚠️</span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="font-bold mb-4">Recent Activity</h3>
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                            <span class="mr-3">📊</span>
                            <div>
                                <p class="font-medium">Energy usage updated</p>
                                <p class="text-sm text-gray-600">Total: <?php echo $totalUsage; ?> kWh</p>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-green-50 rounded-lg">
                            <span class="mr-3">✅</span>
                            <div>
                                <p class="font-medium">System check completed</p>
                                <p class="text-sm text-gray-600">All systems normal</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to logout?")) {
                window.location.href = "./auth/logout.php";
            }
        }
    </script>
</body>
</html>