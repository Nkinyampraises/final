<?php
// Start session and include database connection
session_start();
require_once 'config/db.php'; // Update path if needed

// Check if profile_id is provided
if (!isset($_GET['profile_id'])) {
    header("Location: Donate.php");
    exit();
}

// Fetch orphanage details
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM orphanage_profiles WHERE id = ?");
    $stmt->bind_param("i", $_GET['profile_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $orphanage = $result->fetch_assoc();

    if (!$orphanage) {
        throw new Exception("Orphanage not found");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Process donation if form is submitted
$transactionStatus = "";
$transactionId = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = (int)$_POST['amount'];
    $sender = $_POST['sender'];
    $orphanageId = $_POST['orphanage_id'];
    
    // Basic validation
    if ($amount < 500) {
        $transactionStatus = "error";
        $statusMessage = "Amount must be at least 500 FCFA";
    } elseif (!preg_match('/^6[0-9]{8}$/', $sender)) {
        $transactionStatus = "error";
        $statusMessage = "Invalid MTN number format";
    } else {
        // Simulate MTN API integration
        $transactionStatus = "success";
        $statusMessage = "Donation of " . number_format($amount) . " FCFA processed successfully!";
        $transactionId = "MTN" . time() . rand(100, 999);
        
        // In a real implementation, this would record to the database
        // $stmt = $conn->prepare("INSERT INTO donations (...) VALUES (...)");
        // $stmt->bind_param(...);
        // $stmt->execute();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Money Transfer to <?php echo htmlspecialchars($orphanage['orphanage_name']); ?></title>
    <link rel="stylesheet" href="../Frontend/CSS/payment.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
</head>
<body>
    <header>
        <nav>
            <a href="Donate.php"><i class="fas fa-arrow-left"></i> Back to List</a>
        </nav>
    </header>

    <div class="container">
        <div class="orphanage-info">
            <h2><?php echo htmlspecialchars($orphanage['orphanage_name']); ?></h2>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($orphanage['location']); ?></p>
            <p><strong>Mobile Money:</strong> <?php echo htmlspecialchars($orphanage['mobile_money_number']); ?> (<?php echo htmlspecialchars($orphanage['mobile_money_name']); ?>)</p>
            <p><strong>Project:</strong> <?php echo htmlspecialchars($orphanage['project'] ?? 'No project description available'); ?></p>
        </div>
        
        <div class="mtn-logo">
            <div>MTN Mobile Money Payment</div>
        </div>
        
        <?php if (!empty($transactionStatus)): ?>
            <div class="status-message <?php echo $transactionStatus; ?>">
                <?php echo $statusMessage; ?>
                <?php if ($transactionStatus === 'success'): ?>
                    <div class="transaction-details">
                        <h3>Transaction Details</h3>
                        <p><strong>Transaction ID:</strong> <?php echo $transactionId; ?></p>
                        <p><strong>Amount:</strong> <?php echo number_format($amount); ?> FCFA</p>
                        <p><strong>Recipient:</strong> <?php echo htmlspecialchars($orphanage['mobile_money_name']); ?> (<?php echo htmlspecialchars($orphanage['mobile_money_number']); ?>)</p>
                        <p><strong>Date:</strong> <?php echo date('d M Y, H:i'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form action="" method="post">
            <input type="hidden" name="orphanage_id" value="<?php echo htmlspecialchars($_GET['profile_id']); ?>">
            
            <label for="amount">Amount (Franc CFA)</label>
            <input type="number" id="amount" name="amount" placeholder="Enter amount to send" min="500" required>
            
            <div class="info-box">
                <h3>Payment Information</h3>
                <ul>
                    <li>Minimum donation: <span class="highlight">500 FCFA</span></li>
                    <li>Cashout fees: <span class="highlight">0 FCFA</span> (covered by the recipient)</li>
                    <li>Transactions are processed securely via MTN Mobile Money</li>
                </ul>
            </div>
            
            <div class="checkbox-label">
                <input type="checkbox" id="cashout" checked disabled />
                <label for="cashout">Recipient pays cashout fee (0 frs)</label>
            </div>
            
            <label for="sender">Your MTN Mobile Money Number</label>
            <input type="tel" id="sender" name="sender" placeholder="677XXXXXX" pattern="[0-9]{9}" title="Enter a 9-digit MTN number" required>
            
            <button type="submit" class="dial-button">
                <i class="fas fa-mobile-alt"></i> Process Donation via MTN
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const dialButton = document.querySelector('.dial-button');
            
            form.addEventListener('submit', function(e) {
                const amountInput = document.getElementById('amount');
                const senderInput = document.getElementById('sender');
                
                // Basic validation
                if (!amountInput.value || parseInt(amountInput.value) < 500) {
                    alert('Amount must be at least 500 FCFA');
                    e.preventDefault();
                    return;
                }
                
                if (!senderInput.value || !senderInput.value.match(/^6[0-9]{8}$/)) {
                    alert('Please enter a valid MTN number (9 digits starting with 6)');
                    e.preventDefault();
                    return;
                }
                
                // Simulate processing
                dialButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                dialButton.classList.add('processing');
                dialButton.disabled = true;
                
                // In a real implementation, this would call the MTN API
                // For demo, we'll just proceed with form submission
            });
        });
    </script>
</body>
</html>