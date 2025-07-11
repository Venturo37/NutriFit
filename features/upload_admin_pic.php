<?php
// NAME: Ms. CHOW YAN PING
// Project name: Nutrifit
// DESCRIPTION OF PROGRAM: Handles the upload of a new profile picture into the database. 
//                         Inserts the image into the profile_picture_t table as a BLOB and returns the new pic_id.
// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025
include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_profile_upload'])) {
    $imageData = file_get_contents($_FILES['new_profile_upload']['tmp_name']);

    // Insert into profile_picture_t
    $insert = $connection->prepare("INSERT INTO profile_picture_t (pic_picture) VALUES (?)");
    $insert->bind_param("b", $imageData);
    $insert->send_long_data(0, $imageData);

    if ($insert->execute()) {
        $new_pic_id = $insert->insert_id;

        // Log the insertion
        $logStmt = $connection->prepare("INSERT INTO picture_management_t (adm_id, pic_id, pic_mana_action, pic_mana_timestamp) 
            VALUES (?, ?, 'Added', NOW())");
        $acting_adm_id = $_SESSION['adm_id']; // Assuming you have the admin ID in session
        $logStmt->bind_param("ii", $acting_adm_id, $new_pic_id);
        $logStmt->execute();
        $logStmt->close();

        // Fetch the inserted image to return as base64
        $stmt = $connection->prepare("SELECT pic_picture FROM profile_picture_t WHERE pic_id = ?");
        $stmt->bind_param("i", $new_pic_id);
        $stmt->execute();
        $stmt->bind_result($picPicture);

        if ($stmt->fetch()) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($picPicture) ?: 'image/jpeg';
            $base64 = base64_encode($picPicture);

            // Return image HTML block
            echo "<img src='data:$mimeType;base64,$base64' class='profile_option selected' data-id='{$new_pic_id}'>";
        } else {
            echo "Image inserted but failed to fetch.";
        }

        $stmt->close();
    } else {
        echo "Failed to upload image.";
    }

    $insert->close();
} else {
    echo "No image received.";
}
?>
