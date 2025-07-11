<?php
// NAME: Mr. CHEAH XUE XIAN
// Project name: managecategory.php
// DESCRIPTION OF PROGRAM: displays and manages workout categories. It connects to the database, fetches all categories, and shows them in a list 
//   with options to add, rename, or delete a category. It uses modals for renaming and deletion confirmation and handles messages for user feedback. 
//   JavaScript manages the selection and form submissions dynamically.

// FIRST WRITTEN: 1/6/2025
// LAST MODIFIED: 2/7/2025
include('connection.php'); 
$message = '';
$message_type = ''; // 'success' or 'error'

// Fetch all categories from the database to display
$categories = [];
// Assuming your connection variable is $connection as per your provided code
$sql = "SELECT cate_id, cate_name FROM category_t ORDER BY cate_id";
$result = $connection->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Check for messages from previous requests (from add_category.php, rename_category.php, delete_category.php)
if (isset($_GET['message']) && isset($_GET['type'])) {
    $message = htmlspecialchars($_GET['message']);
    $message_type = htmlspecialchars($_GET['type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Category - NutriFit</title>
  <link rel="stylesheet" href="../styles/managecategory.css" />
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
</head>
<body>
  <?php include ("header.php") ?>
  <div class="header-bar">
    <a href="../interfaces/adminfitnesstable.php" class="header-back-link"><i class="fa-solid fa-arrow-left back-arrow"></i></a>
    <span class="header-title">MANAGING: Category</span>
  </div>
  <main>
    <h2 class="workout-title">Workout Category</h2>

    <?php if (!empty($message)): ?>
        <div class="message-container message-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="category-box">
      <div class="category-list" id="categoryList">
        <?php foreach($categories as $cat): // Use $categories instead of $result ?>
          <button class="category-btn"
            data-cateid="<?= htmlspecialchars($cat['cate_id']) ?>"
            data-catename="<?= htmlspecialchars($cat['cate_name']) ?>"
            onclick="selectCategory(this, <?= htmlspecialchars($cat['cate_id']) ?>, '<?= htmlspecialchars($cat['cate_name']) ?>')">
            <?= htmlspecialchars($cat['cate_name']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
    <input type="hidden" id="selectedCategoryId" value="">
    <input type="hidden" id="selectedCategoryName" value=""> <div class="category-actions-centered">
      <button class="action-btn" type="button" onclick="openRenameModal()">Rename Category</button>
      <button class="action-btn" type="button" onclick="showDeleteModal()">Delete Category</button>
    </div>
    <div class="add-save-row">
      <div class="add-category-left">
        <label for="new-category">Add New Category:</label>
        <input type="text" id="new-category" name="new_category" />
      </div>
      <div class="save-btn-right">
        <button class="save-btn" type="button" onclick="addNewCategory()">Save Changes</button>
      </div>
    </div>
    <div id="renameModal" class="modal-overlay" style="display:none;">
      <div class="modal-content">
        <button class="close-modal" onclick="closeRenameModal()">&#10006;</button>
        <h2 class="modal-title">Rename Category</h2>
        <form id="renameForm" method="POST" action="rename_category.php" onsubmit="return validateRenameForm();">
          <div class="modal-row">
            <label for="old_category"><b>Category Name:<span style='color:red;'>*</span></b></label>
            <select id="old_category" name="old_category" required>
              <option value="">Category</option>
              <?php foreach($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['cate_id']) ?>"><?= htmlspecialchars($cat['cate_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="modal-row" style="text-align:center; color:#888; margin:10px 0;">---change to---</div>
          <div class="modal-row">
            <label for="new_category_name_input"><b>New Category Name:<span style='color:red;'>*</span></b></label>
            <input type="text" id="new_category_name_input" name="new_category_name" required />
          </div>
          <div class="modal-row" style="justify-content:center;">
            <button type="submit" class="save-btn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </main>
  <div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-content delete-modal-content">
      <div class="delete-modal-message">
        Are you sure you want to remove the **<span id="delete_category_name_display"></span>** category?
      </div>
      <div class="delete-modal-actions">
        <button class="delete-no-btn" onclick="closeDeleteModal()">No</button>
        <button class="delete-yes-btn" onclick="proceedDeleteCategory()">Yes</button>
      </div>
    </div>
  </div>
  <script>
    let selectedCategoryId = null; // Renamed for clarity, matches hidden input ID
    let selectedCategoryName = null; // New variable to store name for delete modal

    function selectCategory(btn, cateId, cateName) {
        // Remove 'selected' from all buttons
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('selected'));
        // Add 'selected' to clicked button
        btn.classList.add('selected');
        // Store selected category id and name
        selectedCategoryId = cateId;
        selectedCategoryName = cateName;
        document.getElementById('selectedCategoryId').value = cateId;
        document.getElementById('selectedCategoryName').value = cateName; // Store name in hidden input too
    }

    function openRenameModal() {
        if (!selectedCategoryId) {
            alert('Please select a category to rename.');
            return;
        }
        // Prefill the select box with the selected category
        document.getElementById('old_category').value = selectedCategoryId;
        document.getElementById('renameModal').style.display = 'flex';
    }

    function closeRenameModal() {
        document.getElementById('renameModal').style.display = 'none';
    }

    function validateRenameForm() {
        var oldCat = document.getElementById('old_category').value;
        var newCat = document.getElementById('new_category_name_input').value.trim(); // Corrected ID
        if (!oldCat || !newCat) {
            alert('Please select a category and enter a new name.');
            return false;
        }
        return true;
    }

    function showDeleteModal() {
        if (!selectedCategoryId) {
            alert('Please select a category to delete.');
            return;
        }
        // Display the selected category name in the confirmation message
        document.getElementById('delete_category_name_display').textContent = selectedCategoryName;
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        // No need to clear selectedCategoryId here, it should remain until another is selected
    }

    function proceedDeleteCategory() {
        if (selectedCategoryId) {
            window.location.href = 'delete_category.php?cate_id=' + selectedCategoryId;
        }
    }

    function addNewCategory() {
        const newCategoryName = document.getElementById('new-category').value.trim();
        if (!newCategoryName) {
            alert('Please enter a new category name.');
            return;
        }

        fetch('add_category.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `new_category_name=${encodeURIComponent(newCategoryName)}`
        })
        .then(response => response.json()) // Expect JSON response
        .then(data => {
            if (data.success) {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?message=' + encodeURIComponent(data.message) + '&type=success';
            } else {
                alert('Error adding category: ' + data.error);
                // Optionally redirect with error message
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?message=' + encodeURIComponent(data.error) + '&type=error';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the category.');
        });
    }
  </script>
<?php include ("footer.php") ?>
</body>
</html>
<?php $connection->close(); ?>