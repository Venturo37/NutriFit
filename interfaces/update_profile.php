<!-- 
Name: Mr. Chung Yhung Yie
Project Name: update_profile.php
Description: handles both the display and processing of a user/admin profile update feature for a web application. 
    It supports updating:
        - Profile picture (both)
        - Username (both user and admin)
        - Password (link to separate flow)
        - And for users only: birthdate, gender, weight, and height.
        - It also handles logging the profile update and conditionally recording the user’s weight history.

First Written: 1/6/2025
Last Modified: 8/7/2025 
-->

<?php
    include('../features/connection.php');

    include('../features/restriction.php');

    // $message_status_code = null;
    $response = ['success' => false, 'message' => ''];

    // Start output buffering to catch accidental output
    ob_start();
    // Turn off display of PHP errors to the browser
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);

    $is_user = isset($_SESSION['usr_id']);
    $is_admin = isset($_SESSION['adm_id']) && !$is_user;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
        header('Content-Type: application/json');
        
        // $selected_pic_id = $_POST['selected_pic_id'] ?? null;
        $selected_pic_id = isset($_POST['selected_pic_id']) ? intval($_POST['selected_pic_id']) : null;

        $new_username = trim($_POST['new_username'] ?? '');
        if (empty($new_username)) {
            $response['message'] = 'Username cannot be empty.';
            echo json_encode($response);
            exit();
        }
        $new_username = htmlspecialchars($new_username, ENT_QUOTES, 'UTF-8');
        // This flag tells htmlspecialchars() to convert both single (') and double (") quotes into their HTML entity equivalents:
        // ' becomes &#039;
        // " becomes &quot;

        if (empty($new_username)) {
            $response['message'] = 'Username cannot be empty.';
            echo json_encode($response);
            exit();
        }

        if ($is_user) {
            $usr_id = $_SESSION['usr_id'];
            $new_birthdate = trim($_POST['new_birthdate']) ?? null;
            $new_gender = $_POST['new_gender'] ?? null;
            $new_weight = $_POST['new_weight'] ?? null;
            $new_height = $_POST['new_height'] ?? null;

            if (empty($new_birthdate) || !strtotime($new_birthdate)) {
                $response['message'] = 'Invalid or empty birthdate.';
                echo json_encode($response);
                exit();
            }
            if ($new_birthdate > date('Y-m-d')) {
                $response['message'] = 'Birthdate cannot be in the future.';
                echo json_encode($response);
                exit();
            }

            if ($new_gender !== 'M' && $new_gender !== 'F') {
                $response['message'] = 'Gender must be either "M" or "F".';
                echo json_encode($response);
                exit();
            }
            
            if ($new_weight <= 0 || !is_numeric($new_weight)) {
                $response['message'] = 'Weight must be a positive number.';
                echo json_encode($response);
                exit();
            }
            
            if ($new_height <= 0 || !is_numeric($new_height)) {
                $response['message'] = 'Height must be a positive number.';
                echo json_encode($response);
                exit();
            }

            $new_height = (float)$new_height; // Ensure height is a float for database storage
            $new_weight = (float)$new_weight; // Ensure weight is a float for database storage
            
            $stmt = $connection->prepare("UPDATE user_t SET usr_name = ?, usr_birthdate = ?, usr_gender = ?, usr_height = ?, pic_id = ? WHERE usr_id = ?");
            $stmt->bind_param("sssdii", $new_username, $new_birthdate, $new_gender, $new_height, $selected_pic_id, $usr_id);
            if ($stmt->execute()) {
                $logStmt = $connection->prepare(
                    "INSERT INTO account_management_t (usr_id, acc_mana_action, acc_mana_timestamp) VALUES (?, 'Updated', NOW())");
                $logStmt->bind_param("i", $usr_id);
                $logStmt->execute();
                $logStmt->close();

                $last_logged_weight = null;
                $stmtLastWeight = $connection->prepare("SELECT weight_log_weight FROM user_weight_log_t WHERE usr_id = ? 
                    ORDER BY weight_log_date DESC LIMIT 1");
                $stmtLastWeight->bind_param("i", $usr_id);
                $stmtLastWeight->execute();
                $result_last_weight = $stmtLastWeight->get_result();
                if ($result_last_weight->num_rows > 0) {
                    $last_logged_weight = (float) $result_last_weight->fetch_assoc()['weight_log_weight'];
                }
                $stmtLastWeight->close();

                if ($last_logged_weight === null || abs($last_logged_weight - (float)$new_weight) > 0.0001) {
                    $stmt_insert_weight_log = $connection->prepare("INSERT INTO user_weight_log_t (usr_id, weight_log_weight, weight_log_date)
                        VALUES (?, ?, NOW())");
                    $stmt_insert_weight_log->bind_param("id",$usr_id, $new_weight);
                    $stmt_insert_weight_log->execute();
                    $stmt_insert_weight_log->close();
                } 

                $response['success'] = true;
                $response['message'] = 'Profile updated!';                
                $response['new_username'] = $new_username;
            } else {
                $response['message'] = 'Update failed: '. $stmt->error;
            }
            $stmt->close();
        } elseif ($is_admin) {
            $adm_id = $_SESSION['adm_id'];

            $stmt = $connection->prepare("UPDATE admin_t SET adm_name = ?, pic_id = ?
                WHERE adm_id = ?");
            $stmt->bind_param("sii",$new_username, $selected_pic_id, $adm_id);

            if ($stmt->execute()) {
                $logStmt = $connection->prepare("INSERT INTO account_management_t (adm_id, acc_mana_action, acc_mana_timestamp) 
                    VALUES (?, 'Updated', NOW())");
                $logStmt->bind_param("i", $adm_id);
                $logStmt->execute();
                $logStmt->close();

                $response["success"] = true;
                $response["message"] = "Admin profile updated succesfully!";
                $response['new_username'] = $new_username;
            } else {
                $response["message"] = "Admin update failed: ". $stmt->error;
            }
            $stmt->close();
        }

        if ($selected_pic_id) {
            $stmtPic = $connection->prepare("SELECT pic_picture FROM profile_picture_t WHERE pic_id = ?");
            $stmtPic->bind_param("i", $selected_pic_id);
            $stmtPic->execute();
            $resultPic = $stmtPic->get_result();
            if ($resultPic->num_rows > 0) {
                $pic_blob = $resultPic->fetch_assoc()['pic_picture'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->buffer($pic_blob) ?: 'image/jpeg'; // Default to jpeg if blob empty
                $response['new_profile_pic_src'] = 'data:' . $mime_type . ';base64,' . base64_encode($pic_blob);
            } 
            $stmtPic->close();
        }
        
        ob_clean();
        echo json_encode($response);
        exit();
    }

// if not POST, render the actual HTML profile form as fallback
  
    $current_user_data = [];
    $current_pic_id = null;
    $profile_pictures = [];

    $finfo = new finfo(FILEINFO_MIME_TYPE);// Identify the Multipurpose Internet Mail Extensions(MIME) type of the workout image

    $stmt_pics = $connection->prepare("SELECT pic_id, pic_picture FROM profile_picture_t ");
    $stmt_pics->execute();
    $result_pics = $stmt_pics->get_result();

    while ($row = $result_pics->fetch_assoc()) {
        $pic_id = $row["pic_id"];
        $pic_blob = $row["pic_picture"];
        $mime_type = $finfo->buffer($pic_blob);
        $base64 = base64_encode($pic_blob);
        $img_src = 'data:' . $mime_type . ';base64,' . $base64;

        $profile_pictures[] = [
            'pic_id'=> $pic_id,
            'pic_picture_src'=> $img_src
        ];
    }
    $stmt_pics->close();
    
    if ($is_user) {
        $usr_id = $_SESSION['usr_id'];
        $stmt_user = $connection->prepare("SELECT usr_name, usr_birthdate, usr_gender, usr_height, pic_id
            FROM user_t WHERE usr_id = ?");
        $stmt_user->bind_param("i", $usr_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($result_user->num_rows > 0) {
            $current_user_data = $result_user->fetch_assoc();
            $current_pic_id = $current_user_data['pic_id'];
        }
        $stmt_user->close();

        $stmt_weight = $connection->prepare("SELECT weight_log_weight FROM user_weight_log_t WHERE usr_id = ?
            ORDER BY weight_log_date DESC LIMIT 1");
        $stmt_weight->bind_param("i", $usr_id);
        $stmt_weight->execute();
        $result_weight = $stmt_weight->get_result();
        if ($result_weight->num_rows > 0) {
            $current_user_data['weight'] = $result_weight->fetch_assoc()['weight_log_weight'];
        } else {
            $current_user_data['weight'] = '';
        }
        $stmt_weight->close();
    } elseif ($is_admin) {
        $adm_id = $_SESSION['adm_id'];
        $stmt_admin = $connection->prepare(
            "SELECT adm_name, pic_id FROM admin_t WHERE adm_id = ?"
        );
        $stmt_admin->bind_param("i", $adm_id);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();
        if ($result_admin->num_rows > 0) {
            $current_user_data = $result_admin->fetch_assoc();
            $current_pic_id = $current_user_data["pic_id"];
        }
        $stmt_admin->close();
    }
    
    include('../features/embed.php');
?>

<div id="update_profile" class="update_profile_modal_content">
    <div class="update_profile_directory">
        <div class="icon">
            <i class="fa-solid fa-arrow-left-long"></i>
        </div>
        <h1>Update Profile Page</h1>
    </div>
    <div class="update_content">
        <div class="edit_profile">
            <h2>Edit Profile Picture</h2>
            <div class="picture_selection">
                <?php foreach ($profile_pictures as $pic) { ?>
                    <img src="<?php echo htmlspecialchars($pic['pic_picture_src']); ?>" alt="Profile Picture Option"
                        class="profile_pic_option <?php echo ($pic['pic_id'] == $current_pic_id) ? 'selected' : ''; ?>"
                        data-pic-id="<?php echo htmlspecialchars($pic['pic_id']) ?>">
                <?php } ?>
            </div>
        </div>
        <div class="edit_details">
            <form id="update_profile_form">
                
                <input type="hidden" name="selected_pic_id" id="selected_pic_id" value="<?php echo htmlspecialchars($current_pic_id ?? ''); ?>">

                <div class="edit_username">
                    <label for="update_username"><h2>Username: </h2>
                        <input type="text" id="update_username" name="new_username" required
                            value="<?php echo htmlspecialchars($current_user_data['usr_name'] ?? $current_user_data['adm_name'] ?? '') ?>">
                    </label>
                </div>

                <div class="edit_container">    
                    <div class="edit_sub_container1">
                        <?php if ($is_user) { ?>
                            <div class="edit_birthdate">
                                <label for="update_birthdate"><h2>Birthdate: </h2>
                                    <input type="date" id="update_birthdate" name="new_birthdate"
                                        value="<?php echo htmlspecialchars($current_user_data['usr_birthdate'] ?? '') ?>">
                                </label>
                            </div>

                            <div class="edit_weight">
                                <label for="update_weight"><h2>Weight (kg): </h2>
                                    <input type="number" id="update_weight" name="new_weight" step="0.1"
                                        value="<?php echo htmlspecialchars($current_user_data['weight'] ?? ''); ?>">
                                </label>
                            </div>

                            <div class="edit_height">
                                <label for="update_height"><h2>Height (cm): </h2>
                                    <input type="number" id="update_height" name="new_height" step="0.1"
                                        value="<?php echo htmlspecialchars($current_user_data['usr_height'] ?? '') ?>">
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="edit_sub_container2">
                        <button type="button" onclick="location.href='authentication.php#verify'">
                            Change Password
                        </button>
                        <?php if ($is_user) {?>
                            <div class="edit_gender">
                                <label>
                                    <input type="radio" name="new_gender" value="M" <?php echo (isset($current_user_data['usr_gender']) && $current_user_data['usr_gender'] == 'M') ? 'checked' : ''; ?>>
                                    <div class="gender male_select">
                                        <i class="fa-solid fa-person"></i>                                        
                                        <i class="check fa-solid fa-circle-check"></i>
                                    </div>
                                    <h2>Male</h2>
                                </label>
                                <label>
                                    <input type="radio" name="new_gender" value="F" <?php echo (isset($current_user_data['usr_gender']) && $current_user_data['usr_gender'] == 'F') ? 'checked' : ''; ?>>
                                    <div class="gender female_select">
                                        <i class="fa-solid fa-person-dress"></i>
                                        <i class="check fa-solid fa-circle-check"></i>
                                    </div>  
                                    <h2>Female</h2>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div id="change">
                    <div id="messageArea" class="message_area" style="display:none"></div>
                    <button type="submit">
                        Apply Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

