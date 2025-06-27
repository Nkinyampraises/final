<?php
// Start session
session_start();

// Database connection parameters
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

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username and password are set
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Prepare and bind
        $stmt = $conn->prepare("SELECT password FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);

        // Execute statement
        $stmt->execute();
        $stmt->store_result();

        // Check if user exists
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashed_password)) {
                // Authentication successful
                $_SESSION['username'] = $username; // Store username in session
                header("Location: home.php");
                exit();
            } else {
                // Invalid password
                $error_message = "Invalid username or password.";
            }
        } else {
            // User not found
            $error_message = "Invalid username or password.";
        }

        // Close statement and connection
        $stmt->close();
        $conn->close();
    } else {
        // Handle the case where fields are not set
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="../Frontend/CSS/login.css">
</head>

<body>

    <div class="login-container">
        <h2>LOGIN PAGE</h2>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">USERNAME:</label> <!-- Changed 'name' to 'username' -->
                <input type="text" id="username" name="username" placeholder="full name" required>
            </div>

            <div class="form-group">
                <label for="password">PASSWORD:</label>
                <input type="password" id="password" name="password" placeholder="pwd" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
                <a href="signup.php">Signup?</a>
            </div>
            <button type="submit">LOGIN</button>
        </form>
    </div>

</body>

</html>