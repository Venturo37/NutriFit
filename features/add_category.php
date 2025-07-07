<?php
// add_category.php
include('connection.php'); // Ensure this path is correct
$acting_adm_id = $_SESSION['adm_id'];

header('Content-Type: application/json'); // Indicate JSON response

$response = ['success' => false, 'message' => '', 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_category_name'])) {
    $newCategoryName = trim($_POST['new_category_name']);

    if (empty($newCategoryName)) {
        $response['error'] = "Category name cannot be empty.";
    } else {
        // Check if category already exists
        $check_stmt = $connection->prepare("SELECT COUNT(*) FROM category_t WHERE cate_name = ?");
        $check_stmt->bind_param("s", $newCategoryName);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();

        if ($count > 0) {
            $response['error'] = "Category '" . htmlspecialchars($newCategoryName) . "' already exists.";
        } else {
            $stmt = $connection->prepare("INSERT INTO category_t (cate_name) VALUES (?)");
            $stmt->bind_param("s", $newCategoryName);
            if ($stmt->execute()) {
                $newCateId = $stmt->insert_id; // Get the new category ID
                $stmt->close();

                $logStmt = $connection->prepare("INSERT INTO category_management_t (adm_id, cate_id, cate_mana_action, cate_mana_timestamp)
                    VALUES (?, ?, 'Added', NOW())");
                if (!$logStmt) {
                    $response['error'] = "Failed to prepare log statement: " . $connection->error;
                } else {
                    $logStmt->bind_param("ii", $acting_adm_id, $newCateId);
                    if (!$logStmt->execute()) {
                        $response['error'] = "Failed to log category addition: " . $logStmt->error;
                    } else {
                        $response['success'] = true;
                        // *** IMPORTANT: Ensure this 'message' field is present and correctly populated ***
                        $response['message'] = "Category '" . htmlspecialchars($newCategoryName) . "' added successfully!";
                    }
                    $logStmt->close();
                }

            } else {
                $response['error'] = "Database error: " . $stmt->error;
                $stmt->close();
            }
        }
    }
} else {
    $response['error'] = "Invalid request.";
}

$connection->close();
echo json_encode($response);
?>