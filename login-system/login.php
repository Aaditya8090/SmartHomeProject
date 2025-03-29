<?php
session_start();

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$dbname = "users_db";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            header("Location: dashboard.php"); // Redirect to dashboard
            exit();
        } else {
            echo "<script>alert('Invalid email or password'); window.location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid email or password'); window.location.href='index.html';</script>";
    }
    
    $stmt->close();
}

$conn->close();
?>
