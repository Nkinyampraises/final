<?php
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Donation</title>
    <link rel="stylesheet" href="css/Donate.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h2>Thank You for Your Generous Donation!</h2>
        <p>Your contribution will make a significant difference in the lives of these children.</p>
        <p>You will receive a confirmation email shortly with the details of your donation.</p>
        <a href="Donate.php" class="return-btn">Return to Orphanages List</a>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>