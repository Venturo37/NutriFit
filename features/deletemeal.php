<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: deletemeal.php
// DESCRIPTION OF PROGRAM: deletes a meal from meal_t by its ID when a valid POST request is received. It also logs the deletion 
//     action in meal_management_t for tracking purposes and redirects to the admin meal table with a success flag.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php');

$acting_adm_id = $_SESSION['adm_id'];

// Check if the request is a POST request and if meal_id is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'])) {
    
    $meal_id = intval($_POST['meal_id']);

    // Use a prepared statement to prevent SQL injection
    $stmt = $connection->prepare("DELETE FROM meal_t WHERE meal_id = ?");
    $stmt->bind_param("i", $meal_id);

    // Execute the deletion
    if ($stmt->execute()) {
        // Log the deletion action
        $logStmt = $connection->prepare("INSERT INTO meal_management_t (adm_id, meal_id, meal_mana_action, meal_mana_timestamp) 
            VALUES (?, ?, 'Deleted', NOW())");
        $logStmt->bind_param("ii", $acting_adm_id, $meal_id);
        $logStmt->execute();
        $logStmt->close();    
        
        $stmt->close();
        $connection->close();

        // On success, redirect back to the main meal table
        header("Location: ../interfaces/adminmealtable.php?deleted=1");
        exit();
    } else {
        // On failure, show an error
        die("Error deleting record: " . $stmt->error);
    }



} else {
    // If accessed directly, redirect away
    header("Location: ../interfaces/adminmealtable.php");
    exit();
}
?>