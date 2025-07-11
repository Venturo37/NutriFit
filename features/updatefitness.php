<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: updatefitness.php
// DESCRIPTION OF PROGRAM: handles updating an existing workout in the workout_t table. It retrieves form data (workout details and optional image), 
//     updates the record with or without the new image depending on whether an image file is uploaded, and redirects back to the edit page with a success 
//     flag if the update is successful.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $work_id = intval($_POST['work_id']);
    $work_name = $connection->real_escape_string($_POST['work_name']);
    $work_description = $connection->real_escape_string($_POST['work_description']);
    $cate_id = intval($_POST['cate_id']);
    $work_beginner = floatval($_POST['work_beginner']);
    $work_intermediate = floatval($_POST['work_intermediate']);
    $work_intense = floatval($_POST['work_intense']);
    $work_MET = floatval($_POST['work_MET']);

    // Handle image upload
    if (isset($_FILES['work_image']) && $_FILES['work_image']['size'] > 0) {
        $imgData = file_get_contents($_FILES['work_image']['tmp_name']);
        $stmt = $connection->prepare("UPDATE workout_t SET work_name=?, work_description=?, cate_id=?, work_beginner=?, work_intermediate=?, work_intense=?, work_MET=?, work_image=? WHERE work_id=?");
        $stmt->bind_param("ssiddddsi", $work_name, $work_description, $cate_id, $work_beginner, $work_intermediate, $work_intense, $work_MET, $imgData, $work_id);
    } else {
        $stmt = $connection->prepare("UPDATE workout_t SET work_name=?, work_description=?, cate_id=?, work_beginner=?, work_intermediate=?, work_intense=?, work_MET=? WHERE work_id=?");
        $stmt->bind_param("ssiddddi", $work_name, $work_description, $cate_id, $work_beginner, $work_intermediate, $work_intense, $work_MET, $work_id);
    }

    if ($stmt->execute()) {
        // Redirect back to edit page or admin table
        header("Location: editfitness.php?work_id=$work_id&success=1");
        exit();
    } else {
        echo "Error updating record: " . $connection->error;
    }
    $stmt->close();
} else {
    echo "Invalid request.";
}
$connection->close();
