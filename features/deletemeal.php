<?php
include('connection.php');
$acting_adm_id = $_SESSION['adm_id'];

// Check if the request is a POST request and if meal_id is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'])) {
    
    // Connect to the database
    // $connection = new mysqli("localhost", "root", "", "nutrifit");
    // if ($connection->connect_error) {
    //     die("Connection failed: " . $connection->connect_error);
    // }

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