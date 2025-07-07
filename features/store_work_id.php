<?php
session_start();
include('connection.php');

header('Content-Type: application/json');

if (isset($_POST['work_id'])) {
    $_SESSION['work_id'] = (int)$_POST['work_id'];
    echo json_encode(['status' => 'success']);
    exit;
}
echo json_encode(['status' => 'error']);
?>
