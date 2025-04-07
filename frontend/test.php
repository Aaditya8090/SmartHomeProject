<?php
// header("Location: https://www.google.com");
// exit();


// Set the timezone to your desired timezone
date_default_timezone_set('Asia/Kolkata');

// Get the current date and time in 'Y-m-d H:i:s' format
$current_time = date('Y-m-d H:i:s');

// Print the current date and time
echo "Current Date and Time: " . $current_time;



if ($stmt->execute()) {
    echo "<script>alert('Saved successfully!'); window.location.href='./setting.php'; </script>";
} else {
    echo "<script>alert('Error'); </script>";
}



?>
