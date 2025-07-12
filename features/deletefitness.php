<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: deletefitness.php
// DESCRIPTION OF PROGRAM: deletes a workout by ID when a valid POST request is received. It removes the workout from workout_t, 
//     logs the deletion action in workout_management_t for auditing, and redirects to the admin fitness table with a status flag.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php');

$acting_adm_id = $_SESSION['adm_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['work_id'])) {
    $work_id = intval($_POST['work_id']);

    // IMPORTANT CHANGE HERE: Use $connection instead of $connect
    // since your connection.php defines the connection variable as $connection.
    $stmt = $connection->prepare("DELETE FROM workout_t WHERE work_id = ?");

    // Check if the prepare statement was successful
    if ($stmt === false) {
        die("Prepare failed: (" . $connection->errno . ") " . $connection->error);
    }

    $stmt->bind_param("i", $work_id);

    if ($stmt->execute()) {
        // Log the deletion action
        $logStmt = $connection->prepare("INSERT INTO workout_management_t (adm_id, work_id, work_mana_action, work_mana_timestamp) 
            VALUES (?, ?, 'Deleted', NOW())");
        $logStmt->bind_param("ii", $acting_adm_id, $work_id);
        $logStmt->execute();
        $logStmt->close();

        // Redirect to admin table or another page
        header("Location: ../interfaces/adminfitnesstable.php?deleted=1");
        exit();
    } else {
        echo "Error deleting record: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Invalid request.";
}

// IMPORTANT CHANGE HERE: Use $connection instead of $connect to close the connection.
// Also, only close if the connection was actually established.
if (isset($connection) && $connection instanceof mysqli) {
    $connection->close();
}

?>