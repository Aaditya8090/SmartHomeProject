<?php
session_start();

include '../dbconfig/dbconfig.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: User not logged in");
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $power_rating = $_POST['power_rating'];

    $sql = "INSERT INTO appliances (user_id, name, power_rating, status, last_on, last_off, total_usage) VALUES (?, ?, ?, 'OFF', NULL, NULL, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isd", $user_id, $name, $power_rating);

    if ($stmt->execute()) {
        echo "<script>alert('Appliance added successfully!'); window.location.href='../../frontend/appliances/add_appliance.html'; </script>";
    } else {
        echo "<script>alert('Error adding in appliance'); </script>";
    }

    // <br><a href='../../frontend/appliances/control.html'>Go Back</a>";

    $stmt->close();
}
?>
