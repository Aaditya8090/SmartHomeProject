<?php
session_start();
include '../dbconfig/dbconfig.php';
date_default_timezone_set('Asia/Kolkata');




// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];



if ($_SERVER["REQUEST_METHOD"] == "POST" ) {
    $appliance_id = $_POST['appliance_id'];
    $action = $_POST['action']; // "ON" or "OFF"

    // Get appliance details
    $query = "SELECT status, last_on, power_rating FROM appliances WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $appliance_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appliance = $result->fetch_assoc();

    if (!$appliance) {
        echo "Appliance not found!";
        exit();
    }

    $current_time = date('Y-m-d H:i:s');

    if ($action == "ON") {
        // Update appliance status and last_on time
        $update_query = "UPDATE appliances SET status = ?, last_on = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssii", $action, $current_time, $appliance_id, $user_id);
        $stmt->execute();

        // Insert into appliance_usage table
        $insert_query = "INSERT INTO appliance_usage (user_id, appliance_id, start_time) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iis", $user_id, $appliance_id, $current_time);
        $stmt->execute();

    } elseif ($action == "OFF") {
        // Fetch last ON time
        $query = "SELECT start_time FROM appliance_usage WHERE appliance_id = ? AND user_id = ? AND end_time IS NULL";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $appliance_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usage_record = $result->fetch_assoc();

        if ($usage_record) {
            $last_on_time = strtotime($usage_record['start_time']);
            $current_time_unix = strtotime($current_time);
            $usage_seconds = $current_time_unix - $last_on_time;

            if ($usage_seconds > 0) {
                // Convert seconds to hours and multiply by power rating
                $usage_hours = $usage_seconds / 3600;
                $power_rating = $appliance['power_rating'];
                $usage_kwh = $usage_hours * $power_rating;

                // Update appliance status, last_off time, and total usage
                $update_query = "UPDATE appliances SET status = ?, last_off = ?, total_usage = total_usage + ? WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssdii", $action, $current_time, $usage_kwh, $appliance_id, $user_id);
                $stmt->execute();

                // Update appliance_usage record with end time and usage_kwh
                $update_usage_query = "UPDATE appliance_usage SET end_time = ?, usage_kwh = ? WHERE appliance_id = ? AND user_id = ? AND end_time IS NULL";
                $stmt = $conn->prepare($update_usage_query);
                $stmt->bind_param("sdii", $current_time, $usage_kwh, $appliance_id, $user_id);
                $stmt->execute();
            }
        }
    }

    // Redirect to prevent form resubmission
    header("Location: ./control_appliance.php");
    exit();
}

// Fetch user's appliances with correct today's usage
$query = "
    SELECT a.id, a.name, a.status, a.total_usage, 
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appliance Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-xl font-bold">Appliance Control</h1>
            <a href="javascript:void(0);" class="bg-red-500 px-4 py-2 rounded" onclick="confirmLogout()">Logout</a>
        </nav>
        
        <!-- Main Content -->
        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md p-5">
                <ul>
                    <li class="mb-4"><a href="../dashboard.php" class="text-blue-600">🏠 Dashboard</a></li>
                    <li class="mb-4"><a href="control_appliance.php" class="text-blue-600 font-bold">⚙️ Appliance Control</a></li>
                    <li class="mb-4"><a href="appliance_usage.php" class="text-blue-600">📊 Usage Analytics</a></li>
                    <li class="mb-4"><a href="notifications.php" class="text-blue-600">🔔 Notifications</a></li>
                    <li class="mb-4"><a href="settings.php" class="text-blue-600">⚙️ Settings</a></li>
                </ul>
            </aside>
            
            <!-- Appliance Control Content -->
            <main class="flex-1 p-6">
                <h2 class="text-2xl font-semibold mb-4">Manage Your Appliances</h2>
                <div class="grid grid-cols-3 gap-6">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="bg-white p-5 shadow rounded">
                            <h3 class="text-lg font-bold"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p class="text-gray-600">Today's Usage: <?php echo number_format($row['today_usage'], 2);  ?> kWh</p>
                            <p class="<?php echo ($row['status'] == 'ON') ? 'text-green-600' : 'text-red-600'; ?>">
                                Status: <?php echo $row['status']; ?>
                            </p>
                            <!-- Toggle Button Form -->
                            <form method="POST" action="control_appliance.php">
                                <input type="hidden" name="appliance_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="action" value="<?php echo ($row['status'] == 'ON') ? 'OFF' : 'ON'; ?>">
                                <button type="submit" class="mt-3 px-4 py-2 text-white rounded 
                                    <?php echo ($row['status'] == 'ON') ? 'bg-red-500' : 'bg-green-500'; ?>">
                                    <?php echo ($row['status'] == 'ON') ? 'Turn OFF' : 'Turn ON'; ?>
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
                        <br><br>
                        <!-- for adding new appliances -->
                <div>
                    <h2 class="text-2xl font-semibold mb-4">Add Appliances</h2>
                    <div class="p-2 flex justify-center items-center rounded-xl w-[10%] h[10%]        bg-blue-600 text-white font-semibold">
                        <a href="../../frontend/appliances/add_appliance.html">Add Appliance</a>
                    </div>
                </div>

            </main>
            
        </div>

        
    </div>


    <script>
        function confirmLogout() {
            let confirmation = confirm("Are you sure you want to logout?");
            if (confirmation) {
                window.location.href = "../auth/logout.php"; // Redirect to logout page
            }
        }
    </script>

</body>
</html>










