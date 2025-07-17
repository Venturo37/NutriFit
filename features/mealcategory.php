<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: mealcategory.php
// DESCRIPTION OF PROGRAM: allows an admin to manage meal times for a nutrition/meal planning system. It supports adding a new meal time, 
//     renaming an existing meal time, and deleting a meal time (with a foreign key check to prevent deletion if it’s in use). All admin actions are logged. 
//     The page displays current meal times, shows success/error messages, and uses modals for rename and delete confirmations.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php');
$acting_adm_id = $_SESSION['adm_id']; // Assuming you have the admin ID in session

// Initialize a variable for messages
$message = '';
$message_type = ''; // 'success' or 'error'

// Handle Add New Meal Time
if (isset($_POST['add_meal_time']) && !empty(trim($_POST['new_meal_time']))) {
    $new_meal_time = trim($_POST['new_meal_time']);
    $stmt = $connection->prepare("INSERT INTO meal_time_t (mltm_period) VALUES (?)");
    $stmt->bind_param("s", $new_meal_time);
    if ($stmt->execute()) {
        $logStmt = $connection->prepare("INSERT INTO meal_time_management_t (adm_id, mltm_id, mltm_mana_action, mltm_mana_timestamp) 
            VALUES (?, ?, 'Added', NOW())");
        $mltm_id = $stmt->insert_id; // Get the last inserted ID
        $logStmt->bind_param("ii", $acting_adm_id, $mltm_id);
        $logStmt->execute();
        $logStmt->close();

        $message = "Meal time added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding meal time: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    // Redirect to prevent form resubmission, passing message via GET for simplicity
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Handle Rename Meal Time from the modal
if (isset($_POST['rename_meal_time']) && !empty($_POST['selected_meal_time_id']) && !empty(trim($_POST['rename_to']))) {
    $rename_to = trim($_POST['rename_to']);
    $mltm_id = intval($_POST['selected_meal_time_id']);
    $stmt = $connection->prepare("UPDATE meal_time_t SET mltm_period = ? WHERE mltm_id = ?");
    $stmt->bind_param("si", $rename_to, $mltm_id);
    if ($stmt->execute()) {
        $logStmt = $connection->prepare("INSERT INTO meal_time_management_t (adm_id, mltm_id, mltm_mana_action, mltm_mana_timestamp) 
            VALUES (?, ?, 'Updated', NOW())");
        $logStmt->bind_param("ii", $acting_adm_id, $mltm_id);
        $logStmt->execute();
        $logStmt->close();

        $message = "Meal time renamed successfully!";
        $message_type = "success";
    } else {
        $message = "Error renaming meal time: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Handle Delete Meal Time
if (isset($_POST['delete_meal_time']) && !empty($_POST['selected_meal_time_id'])) {
    $mltm_id = intval($_POST['selected_meal_time_id']);

    // --- Start of Foreign Key Check ---
    $check_stmt = $connection->prepare("SELECT COUNT(*) FROM meal_t WHERE mltm_id = ?");
    $check_stmt->bind_param("i", $mltm_id);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        // If there are associated meals, prevent deletion and set an error message
        $message = "Cannot delete this meal time category because there are meals associated with it. Please reassign or delete the associated meals first.";
        $message_type = "error";
    } else {
        // No associated meals, proceed with deletion
        $stmt = $connection->prepare("DELETE FROM meal_time_t WHERE mltm_id = ?");
        $stmt->bind_param("i", $mltm_id);
        if ($stmt->execute()) {
            $logStmt = $connection->prepare("INSERT INTO meal_time_management_t (adm_id, mltm_id, mltm_mana_action, mltm_mana_timestamp) 
                VALUES (?, ?, 'Deleted', NOW())");
            $logStmt->bind_param("ii", $acting_adm_id, $mltm_id);
            $logStmt->execute();
            $logStmt->close();

            $message = "Meal time deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting meal time: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
    // --- End of Foreign Key Check ---

    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message) . "&type=" . $message_type);
    exit;
}

// Fetch all meal times from the database to display
$meal_times = [];
$result = $connection->query("SELECT mltm_id, mltm_period FROM meal_time_t ORDER BY mltm_id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $meal_times[] = $row;
    }
}
$connection->close();

// Check for messages from previous requests
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Managing: Meal Time</title>
    <link rel="stylesheet" href="../styles/mealcategory2.css">
    <script>
        let selectedMeal = { id: null, name: null };

        function selectMealTime(id, name) {
            selectedMeal.id = id;
            selectedMeal.name = name;
            
            document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('selected'));
            document.getElementById('btn-' + id).classList.add('selected');
        }

        function openRenameModal() {
            if (!selectedMeal.id) {
                alert("Please select a meal time to rename.");
                return;
            }
            document.getElementById('rename_select_id').value = selectedMeal.id;
            document.getElementById('renameModal').style.display = 'flex';
        }

        function closeRenameModal() {
            document.getElementById('renameModal').style.display = 'none';
        }

        function openDeleteModal() {
            if (!selectedMeal.id) {
                alert("Please select a meal time to delete.");
                return;
            }
            // You might want to display the selected meal name in the delete confirmation modal
            document.getElementById('delete_selected_id').value = selectedMeal.id;
            document.getElementById('delete_meal_name_display').textContent = selectedMeal.name; // Set text for confirmation
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
    </script>
</head>
<body>
    <?php include ("header.php") ?>
    <div class="header-bar">
        <a href="../interfaces/adminmealtable.php" class="header-back-link">
            <i class="fa-solid fa-arrow-left back-arrow"></i>
        </a>
        <span class="header-title">Managing: Meal Time</span>
    </div>

    <main>
        <h2 class="workout-title"><u>Meal Time</u></h2>

        <?php if (!empty($message)): ?>
            <div class="message-container message-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="category-box">
            <div class="category-list">
                <?php foreach ($meal_times as $mt): ?>
                    <button type="button" class="category-btn" id="btn-<?= $mt['mltm_id'] ?>"
                    onclick="selectMealTime('<?= $mt['mltm_id'] ?>', '<?= htmlspecialchars($mt['mltm_period']) ?>')">
                    <?= htmlspecialchars($mt['mltm_period']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="category-actions">
            <button type="button" class="action-btn" onclick="openRenameModal()">Rename Meal Time</button>
            <button type="button" class="action-btn" onclick="openDeleteModal()">Delete Meal Time</button>
        </div>

        <form method="POST" action="">
            <div class="add-save-row">
                <div class="add-category-left">
                    <label for="new_meal_time"><b>Add New Meal Time:</b></label>
                    <input type="text" id="new_meal_time" name="new_meal_time" required>
                </div>
                <div class="save-btn-right">
                    <button type="submit" name="add_meal_time" class="save-btn">Save Changes</button>
                </div>
            </div>
        </form>
    </main>

    <div id="renameModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <button type="button" class="close-modal" onclick="closeRenameModal()">×</button>
            <h2 class="modal-title"><u>Rename Meal Time</u></h2>
            <form method="POST" action="">
                <div class="modal-row">
                    <label>Meal Time Name:<span class="required">*</span></label>
                    <select name="selected_meal_time_id" id="rename_select_id" required>
                        <option value="" disabled>Category</option>
                        <?php foreach ($meal_times as $mt): ?>
                        <option value="<?= $mt['mltm_id'] ?>"><?= htmlspecialchars($mt['mltm_period']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="separator">---change to---</div>
                <div class="modal-row">
                    <label>New Meal Time Name:<span class="required">*</span></label>
                    <input type="text" name="rename_to" required />
                </div>
                <button type="submit" name="rename_meal_time" class="submit-btn">Save Changes</button>
            </form>
        </div>
    </div>
    <div id="deleteModal" class="modal-overlay" style="display:none;">
        <div class="delete-modal-content">
            <form method="POST" action="">
                <input type="hidden" name="selected_meal_time_id" id="delete_selected_id">
                <p class="modal-question">Are you sure you want to remove the **<span id="delete_meal_name_display"></span>** meal time category?</p>
                <div class="modal-actions">
                    <button type="button" onclick="closeDeleteModal()" class="btn no">No</button>
                    <button type="submit" name="delete_meal_time" class="btn yes">Yes</button>
                </div>
            </form>
        </div>
    </div>
    <?php include ("footer.php") ?>
</body>
</html>