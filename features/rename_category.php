<?php
// rename_category.php
include('connection.php');
$acting_adm_id = $_SESSION['adm_id']; // Assuming you have the admin ID in session

// if ($connection->connect_error) {
//     die("Connection failed: " . $connection->connect_error);
// }

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_category']) && isset($_POST['new_category_name'])) {
    $old_cate_id = intval($_POST['old_category']);
    $new_category_name = trim($_POST['new_category_name']);

    if (empty($new_category_name)) {
        $message = "New category name cannot be empty.";
        $message_type = "error";
    } else {
        // Check if the new category name already exists (excluding the current category itself)
        $check_stmt = $connection->prepare("SELECT COUNT(*) FROM category_t WHERE cate_name = ? AND cate_id != ?");
        $check_stmt->bind_param("si", $new_category_name, $old_cate_id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $message = "Category '" . htmlspecialchars($new_category_name) . "' already exists.";
            $message_type = "error";
        } else {
            $stmt = $connection->prepare("UPDATE category_t SET cate_name = ? WHERE cate_id = ?");
            $stmt->bind_param("si", $new_category_name, $old_cate_id);
            if ($stmt->execute()) {
                // Log the renaming action
                $logStmt = $connection->prepare("INSERT INTO category_management_t (adm_id, cate_id, cate_mana_action, cate_mana_timestamp) 
                    VALUES (?, ?, 'Updated', NOW())");
                $logStmt->bind_param("ii", $acting_adm_id, $old_cate_id);
                $logStmt->execute();
                $logStmt->close();

                $message = "Category renamed successfully!";
                $message_type = "success";
            } else {
                $message = "Error renaming category: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
} else {
    $message = "Invalid request for renaming category.";
    $message_type = "error";
}

$connection->close();
// Redirect back to managecategory.php with a message
header("Location: managecategory.php?message=" . urlencode($message) . "&type=" . $message_type);
exit();
?>