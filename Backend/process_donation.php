<?php
require_once 'config/db.php';
$conn = getDBConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $profileId = intval($_POST['profile_id']);
    $amount = floatval($_POST['amount']);
    $donorName = htmlspecialchars(trim($_POST['donor_name']));
    $donorEmail = filter_var(trim($_POST['donor_email']), FILTER_SANITIZE_EMAIL);

    $stmt = $conn->prepare("INSERT INTO donations (profile_id, amount, donor_name, donor_email) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $profileId, $amount, $donorName, $donorEmail);
    
    if ($stmt->execute()) {
        header("Location: Thanks.php");
        exit();
    } else {
        $error = "Error processing donation: " . $stmt->error;
        header("Location: donate_process.php?profile_id=$profileId&error=" . urlencode($error));
        exit();
    }
    
    $stmt->close();
}

$conn->close();
?>