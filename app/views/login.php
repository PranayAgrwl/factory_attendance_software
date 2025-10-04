<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KP Tex Login</title>
    <link href="public/bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="public/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 100px; }
        .captcha-box { 
            text-align: center;
            border: 0px solid #ccc;
            padding: 5px;
            margin-bottom: 10px;
            background-color: #fff;
        }
        .captcha-box img {
            max-width: 100%;
            height: auto;
            border: 1px solid #000;
        }
        /* Style for the countdown text */
        #otp-countdown {
            font-weight: bold;
            color: #d9534f; /* Red color for visibility */
            margin-top: 5px;
        }
        
        /* --- New CSS for Sliding Effect --- */
        #hidden-fields-container {
            max-height: 0; /* Starts hidden */
            opacity: 0; /* Starts invisible */
            overflow: hidden; /* Hides content when collapsed */
            transition: max-height 0.5s ease-in-out, opacity 0.4s ease-in;
        }

        #hidden-fields-container.visible {
            /* Max height must be large enough to contain all content */
            max-height: 500px; 
            opacity: 1;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 login-container">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">System Login</h4>
                </div>
                <div class="card-body">
                    
                    <?php 
                    // Display error message if set by the controller
                    if (!empty($error_message)) {
                        echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error_message) . '</div>';
                    }
                    ?>
                    
                    <form method="POST">
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">User ID</label>
                            <!-- Added oninput handler to check the field in real-time -->
                            <input type="text" class="form-control" id="username" name="username" oninput="toggleHiddenFields()" required>
                        </div>
                        
                        <!-- NEW CONTAINER START: This wraps all fields that should be hidden -->
                        <div id="hidden-fields-container">
                        
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>


                            <?php
                                // Generate a random number between 1 and 10
                                $random_image_id = rand(1, 10);
                                // Construct the full path to the image using the random ID
                                $image_path = "public/resources/captcha_images/{$random_image_id}.jpg";
                            ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Captcha Image</label>
                                <div class="captcha-box">
                                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Captcha Image">
                                </div>
                                
                                <label for="captcha" class="form-label">Enter Captcha Code</label>
                                <input type="text" class="form-control" id="captcha" name="captcha" required>
                                <div class="form-text">Enter the value shown in the image above.</div>
                            </div>

                            <div class="mb-3">
                                <label for="otp" class="form-label">Enter OTP Code Sent to your Email ID</label>
                                <input type="text" class="form-control" id="otp" name="otp" required>
                                <div class="form-text" id="otp-countdown"></div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" name="submit_login" class="btn btn-success btn-lg">LOGIN</button>
                            </div>
                        
                        </div>
                        <!-- NEW CONTAINER END -->
                        
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- OTP Countdown Logic ---
    const countdownElement = document.getElementById('otp-countdown');
    let timeRemaining = 29; 

    function updateCountdown() {
        if (!countdownElement) return;

        const seconds = timeRemaining % 60;
        const formattedTime = `${seconds.toString().padStart(2, '0')}s`;
        countdownElement.textContent = `OTP expires in: ${formattedTime}`;

        if (timeRemaining <= 0) {
            clearInterval(timerInterval);
            countdownElement.textContent = 'OTP has expired! Please request a new one.';
        } else {
            timeRemaining--;
        }
    }
    // Start countdown immediately on load (assuming OTP is sent immediately)
    updateCountdown();
    const timerInterval = setInterval(updateCountdown, 1000);
    
    
    // --- Dynamic Field Toggling Logic ---
    const userIdInput = document.getElementById('username');
    const hiddenContainer = document.getElementById('hidden-fields-container');

    function toggleHiddenFields() {
        // Trim removes leading/trailing spaces
        if (userIdInput.value.trim().length > 0) {
            // Show the fields by adding the 'visible' class
            hiddenContainer.classList.add('visible');
            // Remove the 'required' attribute from the hidden fields' labels/inputs initially
            // to prevent the browser from trying to validate them before they are visible.
        } else {
            // Hide the fields
            hiddenContainer.classList.remove('visible');
        }
    }
    
    // Run once on load to ensure fields are hidden if the browser remembered a user ID
    toggleHiddenFields(); 
</script>

</body>
</html>
