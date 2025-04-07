<?php
session_start();
include '../dbconfig/dbconfig.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user from database
    $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Login successful - store session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: ../dashboard.php"); // Redirect to dashboard
    } else {
        echo "<script>alert('Invalid Email or Password'); window.location.href='../../frontend/auth/login.html';</script>";
    }


    $stmt->close();
    $conn->close();
}

?>

