<?php
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pic_id = isset($_POST['pic_id']) ? intval($_POST['pic_id']) : 0;
    $adm_id = isset($_POST['adm_id']) ? intval($_POST['adm_id']) : 0;

    if ($pic_id > 0 && $adm_id > 0) {
        $stmt = $connection->prepare("UPDATE admin_t SET pic_id = ? WHERE adm_id = ?");
        $stmt->bind_param("ii", $pic_id, $adm_id);

        if ($stmt->execute()) {
            echo "Profile picture updated successfully!";
        } else {
            echo "Database error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Invalid input received.";
    }
} else {
    echo "Invalid request method.";
}
?>
