<?php 
	include_once('header.php');
	include_once('navbar.php');
?>
    <div>
        <div class="container-fluid mt-3">
            <div class="row justify-content-between align-items-center mb-4">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold">Welcome to KP TEX Web Portal!</h1>
                    <p>This is the home page.</p>
                </div>
	        </div>
        </div>
    </div>
<?php
	include_once('footer.php');
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Only attempt location capture if a history ID exists
    // The history ID is automatically removed from the session after this runs successfully
    const historyId = <?php echo json_encode($_SESSION['history_id'] ?? null); ?>;

    if (!historyId) {
        return; // Already captured or not set
    }

    // "Not strict" GPS capture: Ask once and don't worry if it fails (user denies, etc.)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                // Send data back to the server using a background request
                fetch('update_location', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `lat=${lat}&lon=${lon}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Clear the history ID from the session so the script doesn't run again
                        // This requires a separate AJAX call to clear the session variable.
                        // For simplicity, we'll let it clear after the user views another page, or simply rely on the historyId check above.
                        
                        // To be totally clean, you could call another AJAX endpoint:
                        // fetch('clear_history_session', { method: 'POST' }); 
                        console.log('Location updated successfully.');
                    }
                })
                .catch(error => {
                    console.error('Error sending location:', error);
                    // Location capture failed, but the login remains valid. "Not strict" fulfilled.
                });
            },
            function(error) {
                // User denied geolocation permission or GPS failed. "Not strict" fulfilled.
                console.warn('Geolocation failed:', error.message);
            }
        );
    }
});
</script>
<?php
    // Clear the history ID immediately after it's passed to JS to prevent repeated attempts
    unset($_SESSION['history_id']);
?>