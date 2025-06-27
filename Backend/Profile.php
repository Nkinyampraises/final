<?php
// Database connection function with error reporting
function getDBConnection()
{
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli('127.0.0.1', 'root', '', 'Orphanage_db', '3306');
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $conn;
}

// Input sanitization function
function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// ==================== PROFILE HANDLING ====================
$conn = getDBConnection();
$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_profile'])) {
    try {
        // Handle file upload
        $targetDir = __DIR__ . "/uploads/";
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
            }
            $fileName = basename($_FILES["profile_picture"]["name"]);
            $targetFilePath = $targetDir . uniqid() . "_" . $fileName;
        }

        $profilePicture = "";
        if (isset($_FILES["profile-picture"]) && $_FILES["profile-picture"]["error"] == UPLOAD_ERR_OK) {
            // Enhanced file upload security
            $fileName = preg_replace("/uploads/", "", $_FILES["profile-picture"]["name"]);
            $fileSize = $_FILES["profile-picture"]["size"];
            $fileTmpName = $_FILES["profile-picture"]["tmp_name"];
            $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            // Validate file
            $allowTypes = ['jpg', 'png', 'jpeg', 'gif'];
            if (!in_array($fileType, $allowTypes)) {
                throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed");
            }
            if ($fileSize > 2 * 1024 * 1024) { // 2MB limit
                throw new Exception("File size must be less than 2MB");
            }

            $targetFilePath = $targetDir . uniqid() . "_" . $fileName;
            if (!move_uploaded_file($fileTmpName, $targetFilePath)) {
                throw new Exception("Error uploading file");
            }
            $profilePicture = $targetFilePath;
        }

        // Validate and sanitize all inputs
        $required = [
            'orphanageName',
            'email',
            'password',
            'phone',
            'location',
            'mobile_money_number',
            'mobile_money_name',
            'num_children'
        ];
        $postData = [];

        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required");
            }
            $postData[$field] = sanitizeInput($_POST[$field]);
        }

        // Additional field processing
        $email = filter_var($postData['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $numChildren = intval($postData['num_children']);
        if ($numChildren < 1) {
            throw new Exception("Number of children must be at least 1");
        }

        $established = !empty($_POST['established']) ? $_POST['established'] : null;
        $area = !empty($_POST['area']) ? sanitizeInput($_POST['area']) : null;
        $project = !empty($_POST['project']) ? sanitizeInput($_POST['project']) : null;

        // Check for duplicate email
        $checkEmail = $conn->prepare("SELECT id FROM orphanage_profiles WHERE email = ?");
        $checkEmail->bind_param("s", $email);
        $checkEmail->execute();
        if ($checkEmail->get_result()->num_rows > 0) {
            throw new Exception("Email already registered");
        }

        // Prepare and execute SQL
        $stmt = $conn->prepare("INSERT INTO orphanage_profiles (
            profile_picture, orphanage_name, email, password, phone, location, 
            area, established, mobile_money_number, mobile_money_name, 
            num_children, project
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $hashedPassword = password_hash($postData['password'], PASSWORD_DEFAULT);

        $stmt->bind_param(
            "ssssssssisis",
            $profilePicture,
            $postData['orphanageName'],
            $email,
            $hashedPassword,
            $postData['phone'],
            $postData['location'],
            $area,
            $established,
            $postData['mobile_money_number'],
            $postData['mobile_money_name'],
            $numChildren,
            $project
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Success - redirect
        header("Location: Donate.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Your existing meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ORPHANAGE CONNECT</title>

    <!-- Add Google Maps API with your key -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDFz_MEEh1G0b1-r6HVxy76hCTQtj-6Q4A&libraries=places&region=CM&callback=initAutocomplete" async defer></script>

    <!-- Your existing stylesheets -->
    <link rel="stylesheet" href="../Frontend/CSS/Profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>PROFILE SETUP</h1>
        </header>
        <main>

            <form action="../Backend/Profile.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="submit_profile" value="1">

                <section class="profile">
                    <div class="profile-icon-container">
                        <i class="fas fa-user-circle profile-icon" id="profile-icon"></i>
                        <img id="profile-preview" alt="Profile Preview">
                    </div>
                    <div class="upload-btn-container">
                        <button type="button" class="upload-btn" onclick="document.getElementById('profile-upload').click()">
                            Upload Profile Picture
                        </button>
                        <label for="profile-upload" class="visually-hidden"></label>
                        <input type="file" id="profile-upload" name="profile-picture" accept="image/*" title="Upload your profile photo">
                    </div>
                </section>
                <p>
                    <label for="orphanageName">Orphanage Name</label>
                    <input type="text" id="orphanageName" name="orphanageName" placeholder="Enter orphanage name" required>
                </p>

                <p>
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                </p>

                <p>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </p>

                <p>
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="contact" required>
                </p>

                <p>
                    <label for="location">Location (Cameroon)</label>
                    <input type="text" id="location" name="location" placeholder="Start typing a Cameroonian city or region" required>
                </p>

                <p>
                    <label for="area">Area</label>
                    <input type="text" id="area" name="area" placeholder="Property area">
                </p>

                <p>
                    <label for="established">Established Date</label>
                    <input type="date" id="established" name="established" required>
                </p>

                <p>
                    <label for="mobile_money_number">Mobile Money Number</label>
                    <input type="tel" id="mobile_money_number" name="mobile_money_number" placeholder="Mobile Money Number" required>
                </p>

                <p>
                    <label for="mobile_money_name">Mobile Money Account Name</label>
                    <input type="text" id="mobile_money_name" name="mobile_money_name" placeholder="Mobile Money Account Name" required>
                </p>

                <p>
                    <label for="num_children">Number of Children</label>
                    <input type="number" id="num_children" name="num_children" placeholder="Number of Children" min="1" required>
                </p>

                <p>
                    <label for="project">Enter Project</label>
                    <textarea id="project" name="project" placeholder="Describe your project, needs, and goals..."></textarea>
                </p>

                <button class="submit-btn" type="submit">
                    SUBMIT PROFILE
                </button>
            </form>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileUpload = document.getElementById('profile-upload');
            const profileIcon = document.getElementById('profile-icon');
            const profilePreview = document.getElementById('profile-preview');

            // Handle profile image upload preview
            profileUpload.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();

                    reader.onload = function(event) {
                        profilePreview.src = event.target.result;
                        profilePreview.style.display = 'block';
                        profileIcon.style.display = 'none';
                    }

                    reader.readAsDataURL(e.target.files[0]);
                }
            });

            // Make the profile icon clickable
            profileIcon.addEventListener('click', function() {
                profileUpload.click();
            });

            // Make the profile preview clickable
            profilePreview.addEventListener('click', function() {
                profileUpload.click();
            });

            // Form validation for number of children
            const childrenInput = document.getElementById('num_children');
            childrenInput.addEventListener('input', function() {
                if (this.value < 1) {
                    this.setCustomValidity('Number of children must be at least 1');
                } else {
                    this.setCustomValidity('');
                }
            });

            // Set minimum date for established date to 50 years ago
            const establishedInput = document.getElementById('established');
            const today = new Date();
            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 50);
            const maxDate = new Date();
            maxDate.setDate(today.getDate() - 1);

            establishedInput.min = minDate.toISOString().split('T')[0];
            establishedInput.max = maxDate.toISOString().split('T')[0];
            establishedInput.value = '2010-01-01';
        });
        document.addEventListener('DOMContentLoaded', function() {
            // ... your existing DOMContentLoaded code ...

            // Google Maps Autocomplete Functionality
            function initAutocomplete() {
                const locationInput = document.getElementById('location');

                // Create autocomplete instance restricted to Cameroon
                const autocomplete = new google.maps.places.Autocomplete(locationInput, {
                    componentRestrictions: {
                        country: 'cm'
                    }, // Cameroon-only results
                    types: ['(regions)'], // Cities and regions only
                    fields: ['formatted_address', 'geometry']
                });

                // When a place is selected
                autocomplete.addListener('place_changed', function() {
                    const place = autocomplete.getPlace();
                    if (!place.geometry) {
                        // Invalid selection, clear the input
                        locationInput.value = '';
                        return;
                    }

                    // Optional: Store coordinates (uncomment if needed)
                    // document.getElementById('latitude').value = place.geometry.location.lat();
                    // document.getElementById('longitude').value = place.geometry.location.lng();
                });
            }

            // Fallback in case Google Maps doesn't load
            window.initAutocomplete = initAutocomplete;

            // ... rest of your existing JavaScript ...
        });
    </script>
</body>

</html>