<?php
session_start();
include '../dbconfig/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.html");
    exit();
}


$user_id = $_SESSION['user_id'];

// 1. Fetch total usage per appliance from appliances table
$query_total = "SELECT name, total_usage FROM appliances WHERE user_id = ?";
$stmt_total = $conn->prepare($query_total);
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();

// 2. Fetch today's usage data from appliance_usage table
$query_today = "
    SELECT 
        a.name, 
        COALESCE(SUM(au.usage_kwh), 0) AS today_kwh 
    FROM appliances a
    LEFT JOIN appliance_usage au ON a.id = au.appliance_id AND DATE(au.end_time) = CURDATE()
    WHERE a.user_id = ?
    GROUP BY a.id
";
$stmt_today = $conn->prepare($query_today);
$stmt_today->bind_param("i", $user_id);
$stmt_today->execute();
$result_today = $stmt_today->get_result();

$todays_usage = [];
while ($row = $result_today->fetch_assoc()) {
    $todays_usage[$row['name']] = round($row['today_kwh'], 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Usage Analytics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-2xl font-bold">Usage Analytics</h1>
            <a href="../auth/logout.php" class="bg-red-500 px-4 py-2 rounded" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </nav>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-56 bg-white p-4 shadow">
                <h3 class="font-bold text-gray-500 mb-4">NAVIGATION</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="../dashboard.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-chart-simple"></i></span> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="./control_appliance.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-plug"></i></span> Appliance Control
                        </a>
                    </li>
                    <li>
                        <a href="./usage_analytics.php" class="flex items-center p-2 bg-blue-100 text-blue-700 rounded font-medium">
                            <span class="mr-2"><i class="fa-solid fa-chart-line"></i></span> Usage Analytics
                        </a>
                    </li>
                    <li>
                        <a href="./notification2.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-bell"></i></span> Notifications
                        </a>
                    </li>
                    <li>
                        <a href="./setting.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-gear"></i></span> Settings
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <h2 class="text-2xl font-semibold mb-4">Energy Usage Summary</h2>

                <!-- Usage Table -->
                <div class="bg-white rounded shadow overflow-hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="py-2 px-4 text-left">Appliance Name</th>
                                <th class="py-2 px-4 text-left">Total Usage (kWh) </th>
                                <th class="py-2 px-4 text-left">Today's Usage (kWh) </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result_total->num_rows === 0): ?>
                                <tr><td colspan="3" class="text-center py-4">No appliances found.</td></tr>
                            <?php else: ?>
                                <?php while ($row = $result_total->fetch_assoc()): ?>
                                    <tr class="border-b">
                                        <td class="py-2 px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td class="py-2 px-4"><?php echo number_format($row['total_usage'], 2); ?> kWh</td>
                                        <td class="py-2 px-4"><?php echo $todays_usage[$row['name']] ?? '0.00'; ?> kWh</td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
