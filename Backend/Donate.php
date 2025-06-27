 <?php
    // Database connection and data fetching
    require_once 'config/db.php'; // Update this path
    // Get the document root and normalize paths
    $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));

    // Initialize variables
    $profiles = [];
    $error = null;
    $search = $_GET['search'] ?? '';
    $selectedTown = $_GET['town'] ?? '';

    $towns = [
        'Douala',
        'Yaoundé',
        'Bamenda',
        'Limbe',
        'Buea',
        'Maroua',
        'Kumba',
        'Bafoussam',
        'Foumban',
        'Nkongsamba',
        'Garoua',
        'Ebolowa',
        'Kribi',
        'Bafang',
        'Dschang',
        'Loum',
        'Nkambe',
        'Obala',
        'Mala',
        'Tiko'
    ];

    try {
        $conn = getDBConnection();

        // Base query
        $query = "SELECT * FROM orphanage_profiles WHERE 1=1";
        $params = [];
        $types = '';

        // Add search filter
        if (!empty($search)) {
            $query .= " AND (orphanage_name LIKE ? OR location LIKE ? OR project LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            $types .= 'sss';
        }

        // Add town filter
        if (!empty($selectedTown)) {
            $query .= " AND location LIKE ?";
            $params[] = "%$selectedTown%";
            $types .= 's';
        }

        // Prepare and execute query
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $profiles = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    ?>

 <!DOCTYPE html>
 <html lang="en">

 <head>
     <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <title>ORPHANAGE CONNECT</title>
     <link rel="stylesheet" href="../Frontend/CSS/Donate.CSS">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
 </head>

 <body>
     <header>
         <h1><img src="../Frontend/Images/donationimage.jpeg" alt="Make a donation" class="donation-image"><br>SUPPORT ORPHANAGES</h1>
         <p><button class="nav-btn"><a href="home.php"><i class="fas fa-home"></i> Home</a></button></p>
         <p><button class="nav-btn"><a href="Donation.php"><i class="fas fa-donate"></i> Donation</a></button></p>
         <p><button class="nav-btn"><a href="welcome.php"><i class="fas fa-sign-out-alt"></i> Logout</a></button></p>
         <p><button class="nav-btn"><a href="#Contact-Us"><i class="fas fa-phone"></i> Contact Us</a></button></p>
     </header>

     <div class="container">
         <h2>Donate Now</h2>
     </div>

     <!-- Search and Filter Section -->
     <div class="filters-container">
         <div class="dropdown">
             <button class="dropbtn">
                 Select Town
                 <span class="arrow">▼</span>
             </button>
             <div class="dropdown-content">
                 <?php foreach ($towns as $town): ?>
                     <a href="#" onclick="selectTown('<?php echo $town; ?>')"><?php echo $town; ?></a>
                 <?php endforeach; ?>
             </div>
         </div>
     </div>
     <div class="search-bar">
         <form action="../Backend/Donate.php" method="GET">
             <input type="text<" placeholder="Search by name or location..." name="search" value="<?php echo htmlspecialchars($search); ?>">
             <input type="hidden" name="town" id="townInput" value="<?php echo htmlspecialchars($selectedTown); ?>">
             <button type="submit"><i class="fas fa-search"></i></button>
         </form>
     </div>

     <!-- Error Display -->
     <?php if ($error): ?>
         <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
     <?php endif; ?>

     <!-- Profiles Display -->
     <div class="content">
         <?php if (!empty($profiles)): ?>
             <div class="profiles-grid">
                 <?php foreach ($profiles as $profile): ?>
                     <div class="profile-card">
                         <?php if (!empty($profile['profile_picture'])): ?>
                             <?php
                                // Convert backslashes to forward slashes
                                $imagePath = str_replace('\\', '/', $profile['profile_picture']);

                                // Convert absolute path to web URL
                                if (strpos($imagePath, $docRoot) === 0) {
                                    $src = str_replace($docRoot, '', $imagePath);
                                    // Ensure path starts with a slash
                                    if ($src[0] !== '/') {
                                        $src = '/' . $src;
                                    }
                                } else {
                                    $src = $imagePath;
                                }
                                ?>
                             <img src="<?= htmlspecialchars($src) ?>" alt="Profile Picture" class="profile-img">
                         <?php else: ?>
                             <div class="default-profile-icon">
                                 <i class="fas fa-user-circle profile-icon"></i>
                             </div>
                         <?php endif; ?>

                         <h3><?php echo htmlspecialchars($profile['orphanage_name']); ?></h3>

                         <!-- Enhanced Location with Map Link -->
                         <div class="location-section">
                             <span class="location-link" onclick="showMap(<?php echo $profile['id']; ?>, '<?php echo addslashes($profile['location']); ?>')">
                                 <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($profile['location']); ?>
                             </span>
                             <div id="map-<?php echo $profile['id']; ?>" class="map-container" style="display:none;"></div>
                         </div>

                         <p><strong>Children:</strong> <?php echo htmlspecialchars($profile['num_children'] ?? 'N/A'); ?></p>

                         <?php
                            $project = !empty($profile['project'])
                                ? (strlen($profile['project']) > 100
                                    ? substr($profile['project'], 0, 100) . '...'
                                    : $profile['project'])
                                : 'No project description available';
                            ?>
                         <p><strong>Project:</strong> <?php echo htmlspecialchars($project); ?></p>

                         <button class="donate-btn" onclick="donateTo(<?php echo $profile['id']; ?>)">
                             <i class="fas fa-hand-holding-heart"></i> Donate Now
                         </button>
                     </div>
                 <?php endforeach; ?>
             </div>
         <?php else: ?>
             <div class="no-profiles">
                 <p>No orphanage profiles found matching your criteria.</p>
                 <a href="profile.php" class="register-btn">Register an Orphanage</a>
             </div>
         <?php endif; ?>
     </div>

     <div class="Contact-Us" id="Contact-Us">
         <h3>Contact us at:</h3>
         <ul>
            <p style="color: blue;">click on the buttons bellow to chat</p>
             <li>
                 <i class="fas fa-envelope"></i>
                 <a href="https://mail.google.com/mail/?view=cm&fs=1&to=njobelovelinenkeni@gmail.com" target="_blank">njobelovelinenkeni@gmail.com</a>
             </li>
             <li>
                 <i class="fab fa-whatsapp"></i>
                 <a href="https://wa.me/237678950512" target="_blank">Chat on WhatsApp</a>
             </li>
         </ul>
     </div>

     <footer>
         <p>© 2025 Supports Projects. All Fulfilled and Blessed.</p>
     </footer>

     <script>
         // Initialize maps when needed
         const maps = {};
         let googleMapsLoaded = false;
         let googleMapsLoading = null;

         // Function to load Google Maps API asynchronously with proper callback
         function loadGoogleMaps() {
             if (googleMapsLoaded) return Promise.resolve();
             if (googleMapsLoading) return googleMapsLoading;

             googleMapsLoading = new Promise((resolve, reject) => {
                 const callbackName = 'googleMapsInit_' + Date.now();
                 window[callbackName] = () => {
                     delete window[callbackName];
                     resolve();
                 };

                 const script = document.createElement('script');
                 const apiKey = 'AIzaSyDFz_MEEh1G0b1-r6HVxy76hCTQtj-6Q4A'; // Replace with your valid API key
                 script.src = `https://maps.googleapis.com/maps/api/js?key=${apiKey}&callback=${callbackName}`;
                 script.async = true;
                 script.defer = true;
                 script.onerror = reject;
                 document.head.appendChild(script);
             }).then(() => {
                 googleMapsLoaded = true;
             }).catch(error => {
                 console.error('Google Maps API loading error:', error);
                 googleMapsLoading = null; // Reset on error to allow retry
                 throw error;
             });

             return googleMapsLoading;
         }

         function showMap(profileId, location) {
             const mapContainer = document.getElementById(`map-${profileId}`);

             if (mapContainer.style.display === 'none') {
                 mapContainer.style.display = 'block';

                 if (!maps[profileId]) {
                     loadGoogleMaps()
                         .then(() => {
                             try {
                                 const geocoder = new google.maps.Geocoder();
                                 const map = new google.maps.Map(mapContainer, {
                                     zoom: 14,
                                     center: {
                                         lat: 4.0511,
                                         lng: 9.7679
                                     } // Default coordinates for Cameroon
                                 });

                                 // Geocode the orphanage's location
                                 geocoder.geocode({
                                     address: location
                                 }, (results, status) => {
                                     if (status === 'OK' && results[0]) {
                                         map.setCenter(results[0].geometry.location);
                                         new google.maps.Marker({
                                             map: map,
                                             position: results[0].geometry.location,
                                             title: location
                                         });
                                     } else {
                                         mapContainer.innerHTML = `<p>Could not load map for: ${location}</p>`;
                                         mapContainer.innerHTML += `<a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(location)}" target="_blank">Open in Google Maps</a>`;
                                     }
                                 });

                                 maps[profileId] = map;
                             } catch (error) {
                                 console.error("Map initialization error:", error);
                                 mapContainer.innerHTML = `<p>Error loading map. Please try again later.</p>`;
                             }
                         })
                         .catch(error => {
                             console.error('Google Maps API loading error:', error);
                             mapContainer.innerHTML = `<p>Failed to load maps. Please check your connection and API key.</p>`;
                         });
                 }
             } else {
                 mapContainer.style.display = 'none';
             }
         }

         function selectTown(town) {
             document.getElementById('townInput').value = town;
             document.querySelector('form').submit();
         }

         function donateTo(profileId) {
             window.location.href = `donate_process.php?profile_id=${profileId}`;
         }

         document.addEventListener('DOMContentLoaded', function() {
             const dropdown = document.querySelector('.dropdown');
             const dropbtn = document.querySelector('.dropbtn');

             // Toggle dropdown
             dropbtn.addEventListener('click', function(e) {
                 dropdown.classList.toggle('active');
                 e.stopPropagation();
             });

             // Close dropdown when clicking outside
             document.addEventListener('click', function() {
                 dropdown.classList.remove('active');
             });
         });
     </script>
 </body>

 </html>