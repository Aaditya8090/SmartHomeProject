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
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-xl font-bold">Usage Analytics</h1>
            <a href="../auth/logout.php" class="bg-red-500 px-4 py-2 rounded" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </nav>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md p-5">
                <ul>
                    <li class="mb-4"><a href="../dashboard.php" class="text-blue-600">🏠 Dashboard</a></li>
                    <li class="mb-4"><a href="./control_appliance.php" class="text-blue-600">⚙️ Appliance Control</a></li>
                    <li class="mb-4"><a href="usage_analytics.php" class="text-blue-600 font-bold">📊 Usage Analytics</a></li>
                    <li class="mb-4"><a href="./notification2.php" class="text-blue-600">🔔 Notifications</a></li>
                    <li class="mb-4"><a href="./setting.php" class="text-blue-600">⚙️ Settings</a></li>
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
