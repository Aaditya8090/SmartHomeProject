<?php
session_start();
include '../dbconfig/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../frontend/auth/login.html");
    exit();
}

if (isset($_SESSION['budget_success'])) {
    echo "<script>alert('Energy budget saved successfully!');</script>";
    unset($_SESSION['budget_success']);
}

if (isset($_SESSION['threshold_success'])) {
    echo "<script>alert('Appliance thresholds updated successfully!');</script>";
    unset($_SESSION['threshold_success']);
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$query_user = "SELECT name, email FROM users WHERE id = ?";
$stmt_user = $conn->prepare($query_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user = $result_user->fetch_assoc();

// Fetch total number of devices
$query_devices = "SELECT COUNT(*) AS total_devices FROM appliances WHERE user_id = ?";
$stmt_devices = $conn->prepare($query_devices);
$stmt_devices->bind_param("i", $user_id);
$stmt_devices->execute();
$result_devices = $stmt_devices->get_result();
$devices = $result_devices->fetch_assoc();

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);

    $stmt->execute();
    
    session_destroy();
    header("Location: ../../frontend/auth/register.html");
    exit();
}

// Handle energy budget update
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['energy_budget'])) {
//     $budget = floatval($_POST['energy_budget']);
//     $update_budget = "UPDATE users SET energy_budget = ? WHERE id = ?";
//     $stmt = $conn->prepare($update_budget);
//     $stmt->bind_param("di", $budget, $user_id);
//     $stmt->execute();
//     $_SESSION['budget_success'] = true;

//     // Handle threshold updates
//     if (isset($_POST['update_thresholds']) && isset($_POST['thresholds'])) {
//         foreach ($_POST['thresholds'] as $appliance_id => $threshold) {
//             $threshold = floatval($threshold);
//             $stmt = $conn->prepare("UPDATE appliances SET max_threshold = ? WHERE id = ? AND user_id = ?");
//             $stmt->bind_param("dii", $threshold, $appliance_id, $user_id);
//             $stmt->execute();
//         }
//         $_SESSION['threshold_success'] = true;
//     }
// }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Handle energy budget update
    if (isset($_POST['energy_budget'])) {
        $budget = floatval($_POST['energy_budget']);
        $update_budget = "UPDATE users SET energy_budget = ? WHERE id = ?";
        $stmt = $conn->prepare($update_budget);
        $stmt->bind_param("di", $budget, $user_id);
        $stmt->execute();

        $_SESSION['budget_success'] = true;
    }

    // Handle threshold updates
    if (isset($_POST['update_thresholds']) && isset($_POST['thresholds'])) {
        foreach ($_POST['thresholds'] as $appliance_id => $threshold) {
            $threshold = floatval($threshold);
            $stmt = $conn->prepare("UPDATE appliances SET max_threshold = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("dii", $threshold, $appliance_id, $user_id);
            $stmt->execute();
        }

        $_SESSION['threshold_success'] = true;
    }

    header("Location: setting.php");
    exit();
}



// Fetch energy budget
$query_budget = "SELECT energy_budget FROM users WHERE id = ?";
$stmt_budget = $conn->prepare($query_budget);
$stmt_budget->bind_param("i", $user_id);
$stmt_budget->execute();
$result_budget = $stmt_budget->get_result();
$budget_row = $result_budget->fetch_assoc();
$energy_budget = $budget_row['energy_budget'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-blue-600 text-white p-4 flex justify-between">
            <h1 class="text-xl font-bold">Settings</h1>
            <a href="../auth/logout.php" class="bg-red-500 px-4 py-2 rounded" onclick="return confirm('Are you sure you want to logout?');">Logout</a>
        </nav>

        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside class="w-64 bg-white shadow-md p-5">
                <ul>
                    <li class="mb-4"><a href="../dashboard.php" class="text-blue-600">🏠 Dashboard</a></li>
                    <li class="mb-4"><a href="./control_appliance.php" class="text-blue-600">⚙️ Appliance Control</a></li>
                    <li class="mb-4"><a href="./usage_analytics.php" class="text-blue-600">📊 Usage Analytics</a></li>
                    <li class="mb-4"><a href="./notification2.php" class="text-blue-600">🔔 Notifications</a></li>
                    <li class="mb-4"><a href="setting.php" class="text-blue-600 font-bold">⚙️ Settings</a></li>
                </ul>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 p-6">
                <h2 class="text-2xl font-semibold mb-4">User Details</h2>
                <div class="bg-white p-4 rounded shadow mb-6">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Total Devices:</strong> <?php echo $devices['total_devices']; ?></p>
                </div>

                <!-- Energy Budget -->
                <h2 class="text-xl font-semibold mb-2">Set Energy Budget (kWh)</h2>
                <form method="POST" class="bg-white p-4 rounded shadow mb-6">
                    <input type="number" name="energy_budget" step="0.01" value="<?php echo $energy_budget; ?>" class="border p-2 rounded w-48">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded ml-2">Save</button>
                </form>

                <div class="bg-white p-6 rounded shadow">
                    <h2 class="text-2xl font-semibold mb-4">Set Max Threshold per Appliance</h2>
                    <form method="POST" class="space-y-4">
                        <?php
                        $stmt_appliances = $conn->prepare("SELECT id, name, max_threshold FROM appliances WHERE user_id = ?");
                        $stmt_appliances->bind_param("i", $user_id);
                        $stmt_appliances->execute();
                        $appliance_result = $stmt_appliances->get_result();

                        while ($row = $appliance_result->fetch_assoc()):
                        ?>
                            <div>
                                <label class="block font-medium"><?php echo htmlspecialchars($row['name']); ?> (kWh):</label>
                                <input type="number" step="0.01" name="thresholds[<?php echo $row['id']; ?>]" value="<?php echo htmlspecialchars($row['max_threshold']); ?>" class="mt-1 w-full p-2 border rounded">
                            </div>
                        <?php endwhile; ?>
                        <button type="submit" name="update_thresholds" class="bg-blue-600 text-white px-4 py-2 rounded">Update Thresholds</button>
                    </form>
                </div>

                <br>

                <!-- Account Deletion -->
                <h2 class="text-xl font-semibold mb-2 text-red-600">Danger Zone</h2>
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded">Delete Account</button>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
