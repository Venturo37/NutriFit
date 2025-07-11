<?php
// NAME: Ms. CHOW YAN PING
// Project name: Nutrifit
// DESCRIPTION OF PROGRAM: Deletes a selected profile picture from the profile_picture_t table based on the provided pic_id. 
//                         Ensures the image is not currently assigned to any admin or user before deletion.
// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025
include 'connection.php';
$acting_adm_id = $_SESSION['adm_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pic_id = $_POST['pic_id'] ?? '';

    if (!empty($pic_id)) {
        // Check if any user/admin is still using this pic_id
        $checkAdmin = $connection->prepare("SELECT COUNT(*) FROM admin_t WHERE pic_id = ?");
        $checkAdmin->bind_param("i", $pic_id);
        $checkAdmin->execute();
        $checkAdmin->bind_result($admin_count);
        $checkAdmin->fetch();
        $checkAdmin->close();

        $checkUser = $connection->prepare("SELECT COUNT(*) FROM user_t WHERE pic_id = ?");
        $checkUser->bind_param("i", $pic_id);
        $checkUser->execute();
        $checkUser->bind_result($user_count);
        $checkUser->fetch();
        $checkUser->close();

        if ($admin_count > 0 || $user_count > 0) {
            echo "This profile picture is still in use by an admin or user.";
        } else {
            $logStmt = $connection->prepare("INSERT INTO picture_management_t (adm_id, pic_id, pic_mana_action, pic_mana_timestamp) 
                VALUES (?, ?, 'Deleted', NOW())");
            $logStmt->bind_param("ii", $acting_adm_id, $pic_id);
            $logStmt->execute();
            $logStmt->close();

            $delete = $connection->prepare("DELETE FROM profile_picture_t WHERE pic_id = ?");
            $delete->bind_param("i", $pic_id);

            if ($delete->execute()) {
                echo "Profile picture deleted successfully.";
            } else {
                echo "Failed to delete profile picture.";
            }

            $delete->close();
        }
    } else {
        echo "Missing pic_id.";
    }

    $connection->close();
}
?>
