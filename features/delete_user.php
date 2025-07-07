<?php
include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $type = $_POST['type'];

    if (!ctype_digit($id)) {
        echo "Invalid ID";
        exit;
    }

    $connection->begin_transaction();
    

    try {
        if ($type === 'user') {
            // Step 1: Delete from user_meal_intake_t where manual_id belongs to user
            $query_manual_ids = $connection->prepare("SELECT manual_id FROM manual_input_t WHERE usr_id = ?");
            if (!$query_manual_ids) throw new Exception("Prepare failed: " . $connection->error);
            $query_manual_ids->bind_param("i", $id);
            $query_manual_ids->execute();
            $result = $query_manual_ids->get_result();

            while ($row = $result->fetch_assoc()) {
                $manual_id = $row['manual_id'];
                $del_meal = $connection->prepare("DELETE FROM user_meal_intake_t WHERE manual_id = ?");
                if (!$del_meal) throw new Exception("Prepare failed: " . $connection->error);
                $del_meal->bind_param("i", $manual_id);
                $del_meal->execute();
                $del_meal->close();
            }
            $query_manual_ids->close();

            // Step 2: Now delete from manual_input_t
            $del_manual = $connection->prepare("DELETE FROM manual_input_t WHERE usr_id = ?");
            if (!$del_manual) throw new Exception("Prepare failed: " . $connection->error);
            $del_manual->bind_param("i", $id);
            $del_manual->execute();
            
            $del_meal = $connection->prepare("DELETE FROM user_meal_intake_t WHERE usr_id = ?");
            if (!$del_meal) throw new Exception("Prepare failed: " . $connection->error);
            $del_meal->bind_param("i", $id);
            $del_meal->execute();

            // Step 3: Other dependencies
            $del_weight = $connection->prepare("DELETE FROM user_weight_log_t WHERE usr_id = ?");
            if (!$del_weight) throw new Exception("Prepare failed: " . $connection->error);
            $del_weight->bind_param("i", $id);
            $del_weight->execute();

            $del_workout = $connection->prepare("DELETE FROM user_workout_session_t WHERE usr_id = ?");
            if (!$del_workout) throw new Exception("Prepare failed: " . $connection->error);
            $del_workout->bind_param("i", $id);
            $del_workout->execute();

            $del_feedback = $connection->prepare("DELETE FROM feedback_t WHERE usr_id = ?");
            if (!$del_feedback) throw new Exception("Prepare failed: " . $connection->error);
            $del_feedback->bind_param("i", $id);
            $del_feedback->execute();
            $del_feedback->close();

            // Step 4: Finally delete from user_t
            $stmt = $connection->prepare("DELETE FROM user_t WHERE usr_id = ?");
            if (!$stmt) throw new Exception("Prepare failed: " . $connection->error);
        } elseif ($type === 'admin') {

            $stmt = $connection->prepare("DELETE FROM admin_t WHERE adm_id = ?");
            if (!$stmt) throw new Exception("Prepare failed: " . $connection->error);
        } else {
            echo "Invalid type";
            exit;
        }

        $stmt->bind_param("i", $id);


        if ($stmt->execute()) {
            if ($type === 'user') {
                // Log user deletion
                $logStmt = $connection->prepare("INSERT INTO user_management_t (adm_id, usr_id, usr_mana_action, usr_mana_timestamp) 
                    VALUES (?, ?, 'Deleted', NOW())");
                if (!$logStmt) throw new Exception("Prepare failed: " . $connection->error);
                $logStmt->bind_param("ii", $_SESSION['adm_id'], $id);
                
                $logStmt->execute();
                $logStmt->close();
            } elseif ($type === 'admin') {
                // Log admin deletion
                $logStmt = $connection->prepare("INSERT INTO admin_management_t (adm_id, managed_adm_id, adm_mana_action, adm_mana_timestamp) 
                    VALUES (?, ?, 'Deleted', NOW())");
                if (!$logStmt) throw new Exception("Prepare failed: " . $connection->error);
                $logStmt->bind_param("ii", $_SESSION['adm_id'], $id);
                
                $logStmt->execute();
                $logStmt->close();
            }
        }
        $stmt->close();

        $connection->commit();
        echo "deleted";
    } catch (Exception $e) {
        $connection->rollback();
        echo "Delete failed: " . $e->getMessage();
    }

    $connection->close();
}
?>
