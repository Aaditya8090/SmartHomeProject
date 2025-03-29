<?php
include '../dbconfig/dbconfig.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appliance_id = $_POST['appliance_id'];
    $action = $_POST['action']; // 'ON' or 'OFF'

    if ($action === "ON") {
        $sql = "UPDATE appliances SET status='ON', last_on=NOW() WHERE id=?";
    } else {
        // Calculate usage
        $sql = "UPDATE appliances SET status='OFF', last_off=NOW(), total_usage = total_usage + TIMESTAMPDIFF(SECOND, last_on, NOW())/3600 WHERE id=?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appliance_id);

    if ($stmt->execute()) {
        echo "Appliance turned $action successfully!";
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt->close();
}
?>
