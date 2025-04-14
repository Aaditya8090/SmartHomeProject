<?php
session_start();
include '../dbconfig/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark as read logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read_id'])) {
    $mark_id = $_POST['mark_read_id'];
    $stmt_update = $conn->prepare("UPDATE notifications SET status = 1 WHERE id = ? AND user_id = ?");
    $stmt_update->bind_param("ii", $mark_id, $user_id);
    $stmt_update->execute();
}

// 1. Get each appliance's today's usage and compare with threshold
$query = "
    SELECT 
        a.id,
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
        $message = $row['name'] . " exceeded daily threshold. Used: " . round($row['today_usage'], 2) . " kWh / Threshold: " . $row['max_threshold'] . " kWh";

        // Check if similar notification already exists for today
        $stmt_check = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND message = ? AND DATE(created_at) = CURDATE()");
        $stmt_check->bind_param("is", $user_id, $message);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows === 0) {
            // Insert new alert if not already added today
            $stmt_insert = $conn->prepare("INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, 0, NOW())");
            $stmt_insert->bind_param("is", $user_id, $message);
            $stmt_insert->execute();
            $alerts[] = ['message' => $message, 'id' => $conn->insert_id];
        }
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

if (!is_null($budget) && $monthly_usage > $budget) {
    $message = "Monthly energy budget exceeded. Used: $monthly_usage kWh / Budget: $budget kWh";

    // Check if message already exists for today
    $stmt_check = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND message = ? AND DATE(created_at) = CURDATE()");
    $stmt_check->bind_param("is", $user_id, $message);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        $stmt_insert = $conn->prepare("INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, 0, NOW())");
        $stmt_insert->bind_param("is", $user_id, $message);
        $stmt_insert->execute();
        $alerts[] = ['message' => $message, 'id' => $conn->insert_id];
    }
}


// Fetch notifications
$query_alerts = "SELECT id, message, status, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt_alerts = $conn->prepare($query_alerts);
$stmt_alerts->bind_param("i", $user_id);
$stmt_alerts->execute();
$result_alerts = $stmt_alerts->get_result();
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
        <!-- <a href="#" class="bg-red-500 px-4 py-2 rounded" onclick="confirmLogout()">Logout</a> -->
        <button onclick="confirmLogout()" class="bg-red-500 px-4 py-2 rounded text-white">Logout</button>

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
    <h2 class="text-3xl font-bold text-gray-800 mb-6">📨 Your Notifications</h2>

    <?php if ($result_alerts->num_rows === 0): ?>
        <div class="bg-white p-6 rounded-lg shadow text-center">
            <p class="text-green-600 text-lg font-medium">✅ No energy alerts. Everything looks good!</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php while ($row = $result_alerts->fetch_assoc()): ?>
                <div class="bg-white p-5 rounded-lg shadow hover:shadow-md transition border-l-4 <?php echo $row['status'] == 0 ? 'border-red-500' : 'border-green-400'; ?>">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="text-lg font-semibold text-gray-800 mb-2">
                                🔔 <?php echo htmlspecialchars($row['message']); ?>
                            </p>
                            <p class="text-sm text-gray-500">📅 <?php echo date('F j, Y, g:i A', strtotime($row['created_at'])); ?></p>
                        </div>
                        <div class="ml-4 text-right">
                            <?php if ($row['status'] == 0): ?>
                                <form method="POST">
                                    <input type="hidden" name="mark_read_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-full transition">
                                        Mark as Read
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="inline-block px-3 py-1 text-sm bg-green-100 text-green-700 rounded-full">
                                    ✅ Read
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

    </div>
</div>


<script>
    function confirmLogout(event) {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "../auth/logout.php";
        }
    }

</script>

</body>
</html>
