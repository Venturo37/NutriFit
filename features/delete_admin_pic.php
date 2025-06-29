<?php
include 'connection.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adm_id = $_POST['adm_id'] ?? '';
    $default_pic_id = 2; 

    if (!empty($adm_id)) {
        $stmt = $connection->prepare("UPDATE admin_t SET pic_id = ? WHERE adm_id = ?");
        $stmt->bind_param("ii", $default_pic_id, $adm_id);

        if ($stmt->execute()) {
            echo "Profile picture reset to default.";
        } else {
            echo "Failed to reset profile picture.";
        }

        $stmt->close();
    } else {
        echo "Admin ID missing.";
    }

    $connection->close();
}
?>
