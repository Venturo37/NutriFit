<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: delete_category.php
// DESCRIPTION OF PROGRAM: deletes a workout category by first checking if any workouts are linked to it. If linked workouts exist, deletion is blocked. 
//     If not, it logs the deletion action in category_management_t, deletes the category from category_t, and redirects back with a success or error message.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
// delete_category.php
include('connection.php'); // Ensure this path is correct
$acting_adm_id = $_SESSION['adm_id'];


// Assuming your connection variable is $connection
// if ($connection->connect_error) {
//     die("Connection failed: " . $connection->connect_error);
// }

$message = '';
$message_type = '';

if (isset($_GET['cate_id'])) {
    $cate_id = intval($_GET['cate_id']);

    // --- Start of Foreign Key Check ---
    // Check if any workouts are associated with this category
    $check_stmt = $connection->prepare("SELECT COUNT(*) FROM workout_t WHERE cate_id = ?");
    $check_stmt->bind_param("i", $cate_id);
    $check_stmt->execute(); // <--- THIS IS LINE 7 WHERE YOUR ERROR OCCURS IF OLD CODE IS RUNNING
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        // If there are associated workouts, prevent deletion
        $message = "Cannot delete this category because there are workouts associated with it. Please reassign or delete the associated workouts first.";
        $message_type = "error";
    } else {
        $logStmt = $connection->prepare("INSERT INTO category_management_t (adm_id, cate_id, cate_mana_action, cate_mana_timestamp) 
            VALUES (?, ?, 'Deleted', NOW())");
        $logStmt->bind_param("ii", $acting_adm_id, $cate_id);
        $logStmt->execute();
        $logStmt->close();

        // No associated workouts, proceed with deletion
        $stmt = $connection->prepare("DELETE FROM category_t WHERE cate_id = ?");
        $stmt->bind_param("i", $cate_id);
        if ($stmt->execute()) {
            $message = "Category deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting category: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
    // --- End of Foreign Key Check ---
} else {
    $message = "No category ID provided for deletion.";
    $message_type = "error";
}

$connection->close();
// Redirect back to managecategory.php with a message
header("Location: managecategory.php?message=" . urlencode($message) . "&type=" . $message_type);
exit();
?>