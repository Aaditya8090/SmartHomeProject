<?php

include '../dbconfig/dbconfig.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Secure hashing
    $phone_no = $_POST['phone_no'];
    $dob = $_POST['dob'];

    // Insert into users table
    $sql = "INSERT INTO users (name, email, password, dob, phone_no) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $password, $dob, $phone_no);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Please login.'); window.location.href='../../frontend/auth/login.html';</script>";
    } else {
        echo "<script>alert('Error: Email already exists!'); window.location.href='../../frontend/auth/register.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
