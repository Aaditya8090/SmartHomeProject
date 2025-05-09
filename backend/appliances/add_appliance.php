<?php
session_start();

include '../dbconfig/dbconfig.php';

//Check if the user is logged in 
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
        echo "<script>alert('Appliance added successfully!'); window.location.href='./control_appliance.php'; </script>";
    } else {
        echo "<script>alert('Error adding in appliance'); </script>";
    }

    

    $stmt->close();
}
?>
