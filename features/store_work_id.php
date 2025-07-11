<?php
// NAME: Ms. JOAN CHUA YONG XIN

// Project name: Store Work ID

// DESCRIPTION OF PROGRAM:
// - This script handles an AJAX request to store the selected workout ID into a session variable.
// - When a POST request containing 'work_id' is received, the script casts the ID to an integer and assigns it to the `$_SESSION['work_id']` variable for later use (in fitness_session.php).
// - It responds with a JSON message indicating either success or error, allowing the frontend to determine whether to proceed with redirection to the workout session page.
// - The script is used to temporarily track which workout card the user selected for training.

// FIRST WRITTEN: 06-07-2025
// LAST MODIFIED: 06-07-2025 
include('connection.php');

header('Content-Type: application/json');

if (isset($_POST['work_id'])) {
    $_SESSION['work_id'] = (int)$_POST['work_id'];
    echo json_encode(['status' => 'success']);
    exit;
}
echo json_encode(['status' => 'error']);
?>
