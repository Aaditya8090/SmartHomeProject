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

// Fetch Username of Logged-in User
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$userName = $row['name'] ?? 'User';
$firstName = explode(' ', trim($userName))[0]; // Get only the first word (first name)
$stmt->close();

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-2xl font-bold">Smart Home Dashboard</h1>
            <a href="javascript:void(0);" onclick="confirmLogout()" class="bg-red-500 px-4 py-2 rounded">Logout</a>
        </nav>
        
        <!-- Main Content -->
        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-56 bg-white p-4 shadow">
                <h3 class="font-bold text-gray-500 mb-4">NAVIGATION</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-2 bg-blue-100 text-blue-700 rounded font-medium">
                            <span class="mr-2"><i class="fa-solid fa-chart-simple"></i></span> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/control_appliance.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-plug"></i></span> Appliance Control
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/usage_analytics.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-chart-line"></i></span> Usage Analytics
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/notification2.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-bell"></i></span> Notifications
                            <?php if($pendingNotifications > 0): ?>
                            <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                <?php echo $pendingNotifications; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="./appliances/setting.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-gear"></i></span> Settings
                        </a>
                    </li>
                </ul>
            </aside>
            
            <!-- Dashboard Content -->
            <main class="flex-1 p-6">
                <h2 class="text-2xl mb-4">Welcome <?php echo $firstName; ?> to your Smart Home Dashboard!!</h2>
                <div class="grid grid-cols-3 gap-6">
                    <!-- Total Energy Usage -->
                    <div class="bg-white p-5 shadow rounded">
                        <h3 class="text-lg font-bold">Total Energy Usage</h3>
                        <p class="text-2xl text-blue-600"><?php echo $totalUsage; ?> kWh</p>
                    </div>

                    <!-- Active Appliances -->
                    <div class="bg-white p-5 shadow rounded">
                        <h3 class="text-lg font-bold">Active Appliances</h3>
                        <p class="text-2xl text-green-600"><?php echo $activeDevices; ?></p>
                    </div>

                    <!-- Pending Alerts -->
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
