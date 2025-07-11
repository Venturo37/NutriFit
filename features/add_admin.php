<?php
// NAME: Ms. CHOW YAN PING
// Project name: Nutrifit
// DESCRIPTION OF PROGRAM: Handles the creation of a new admin account. 
//                         Validates that the email is unique across both admin_t and user_t tables before inserting into admin_t.
// FIRST WRITTEN: 2/6/2025
// LAST MODIFIED: 9/7/2025

include('connection.php');
include('restriction.php');

$acting_adm_id = $_SESSION['adm_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'check_email' && isset($_POST['email'])) {
        $email = trim($_POST['email']);

        $stmt1 = $connection->prepare("SELECT 1 FROM admin_t WHERE adm_email = ?");
        $stmt1->bind_param("s", $email);
        $stmt1->execute();
        $result1 = $stmt1->get_result();

        $stmt2 = $connection->prepare("SELECT 1 FROM user_t WHERE usr_email = ?");
        $stmt2->bind_param("s", $email);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result1->num_rows > 0 || $result2->num_rows > 0) {
            echo "exists";
        } else {
            echo "available";
        }
        exit;
    }

    // Proceed to insert new admin
    $name = trim($_POST['adm_name']);
    $email = trim($_POST['adm_email']);
    $password = $_POST['adm_password'];

    // Recheck email before insert (for safety)
    $checkAdmin = $connection->prepare("SELECT 1 FROM admin_t WHERE adm_email = ?");
    $checkAdmin->bind_param("s", $email);
    $checkAdmin->execute();
    $adminResult = $checkAdmin->get_result();

    $checkUser = $connection->prepare("SELECT 1 FROM user_t WHERE usr_email = ?");
    $checkUser->bind_param("s", $email);
    $checkUser->execute();
    $userResult = $checkUser->get_result();

    if ($adminResult->num_rows > 0 || $userResult->num_rows > 0) {
        echo "duplicate";
        exit;
    }

    $pic_id = 2; // default profile picture
    $stmt = $connection->prepare("INSERT INTO admin_t (adm_name, adm_email, adm_password, pic_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $email, $password, $pic_id);

    if ($stmt->execute()) {
        $new_adm_id = $connection->insert_id;
        $logStmt = $connection->prepare("INSERT INTO admin_management_t (adm_id, managed_adm_id, adm_mana_action, adm_mana_timestamp) VALUES (?, ?, 'Added', NOW())");
        $logStmt->bind_param("ii", $acting_adm_id, $new_adm_id);
        $logStmt->execute();
        echo "success";
    } else {
        echo "error";
    }
}
?>
