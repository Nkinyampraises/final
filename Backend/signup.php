<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Orphanage_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirmpwd']);

    // Basic validation
    $errors = [];

    if (empty($username)) {
        $errors[] = "Username is required";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        $errors[] = "Email already exists";
    }

    // If no errors, insert user and create account
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql = "INSERT INTO users (Username, Email, Password) VALUES ('$username', '$email', '$hashed_password')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Account created successfully!'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Error! Couldn't create account: " . $conn->error . "');</script>";
        }
    } else {
        // Handle and display errors
        foreach ($errors as $error) {
            echo "<script>alert('$error');</script>";
        }
        echo "<script>window.history.back();</script>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Frontend/CSS/signup.css"> <!--link to signup.css file-->
    <title>SignUp Page</title>
</head>


<body>
    <form action="signup.php" method="post"> <!--link to signup.php file-->
        <h1>Sign Up</h1>
        <p>
            <input type="text" id="username" name="username" placeholder="Username" required><br><br>
            <input type="email" id="email" name="email" placeholder="example@gmail.com" required><br><br>
            <input type="password" id="password" name="password" placeholder="Password" required><br><br>
            <input type="password" id="confirmpwd" name="confirmpwd" placeholder="Confirm Password" required><br><br>
            <button type="submit">Submit</button>
        </p>
    </form>
</body>

</html>