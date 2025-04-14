<?php
session_start();
include '../dbconfig/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Get each appliance's today's usage and compare with threshold
$query = "
    SELECT 
        a.name, 
        a.max_threshold,
        COALESCE(SUM(au.usage_kwh), 0) AS today_usage
    FROM appliances a
    LEFT JOIN appliance_usage au 
        ON a.id = au.appliance_id 
        AND DATE(au.end_time) = CURDATE()
    WHERE a.user_id = ?
    GROUP BY a.id
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$alerts = [];

// Check per-appliance threshold
while ($row = $result->fetch_assoc()) {
    if (!is_null($row['max_threshold']) && $row['today_usage'] > $row['max_threshold']) {
        $alerts[] = [
            'type' => 'appliance',
            'name' => $row['name'],
            'usage' => round($row['today_usage'], 2),
            'threshold' => $row['max_threshold']
        ];
    }
}

// 2. Get user’s energy_budget
$query_budget = "SELECT energy_budget FROM users WHERE id = ?";
$stmt_budget = $conn->prepare($query_budget);
$stmt_budget->bind_param("i", $user_id);
$stmt_budget->execute();
$result_budget = $stmt_budget->get_result();
$user_data = $result_budget->fetch_assoc();
$budget = $user_data['energy_budget'] ?? null;

// 3. Get user’s total monthly usage
$query_month = "
    SELECT SUM(usage_kwh) AS total_monthly 
    FROM appliance_usage 
    WHERE user_id = ? 
    AND MONTH(end_time) = MONTH(CURDATE()) 
    AND YEAR(end_time) = YEAR(CURDATE())
";
$stmt_month = $conn->prepare($query_month);
$stmt_month->bind_param("i", $user_id);
$stmt_month->execute();
$result_month = $stmt_month->get_result();
$row_month = $result_month->fetch_assoc();
$monthly_usage = round($row_month['total_monthly'] ?? 0, 2);

// Compare monthly usage with budget
if (!is_null($budget) && $monthly_usage > $budget) {
    $alerts[] = [
        'type' => 'budget',
        'usage' => $monthly_usage,
        'budget' => $budget
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-2xl font-bold">Notifications</h1>
            <a href="../auth/logout.php" class="bg-red-500 px-4 py-2 rounded" onclick="return confirm('Are you sure you want to logout?');>Logout</a>
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
                        <a href="./usage_analytics.php" class="flex items-center p-2 hover:bg-gray-100 rounded">
                            <span class="mr-2"><i class="fa-solid fa-chart-line"></i></span> Usage Analytics
                        </a>
                    </li>
                    <li>
                        <a href="./notification2.php" class="flex items-center p-2 bg-blue-100 text-blue-700 rounded font-medium">
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
                <h2 class="text-2xl font-semibold mb-4">Alerts</h2>

                <?php if (empty($alerts)): ?>
                    <div class="bg-white p-4 rounded shadow">
                        <p class="text-green-600">✅ No energy alerts. Everything looks good!</p>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-4 rounded shadow space-y-4">
                        <?php foreach ($alerts as $alert): ?>
                            <?php if ($alert['type'] === 'appliance'): ?>
                                <div class="border-l-4 border-red-500 pl-4">
                                    <p class="text-red-600 font-semibold">
                                        ⚠️ <?php echo htmlspecialchars($alert['name']); ?> exceeded daily threshold!
                                    </p>
                                    <p>
                                        Used: <strong><?php echo $alert['usage']; ?> kWh</strong> |
                                        Threshold: <strong><?php echo $alert['threshold']; ?> kWh</strong>
                                    </p>
                                </div>
                            <?php elseif ($alert['type'] === 'budget'): ?>
                                <div class="border-l-4 border-yellow-500 pl-4">
                                    <p class="text-yellow-600 font-semibold">
                                        ⚠️ Monthly energy budget exceeded!
                                    </p>
                                    <p>
                                        Used this month: <strong><?php echo $alert['usage']; ?> kWh</strong> |
                                        Budget: <strong><?php echo $alert['budget']; ?> kWh</strong>
                                    </p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
