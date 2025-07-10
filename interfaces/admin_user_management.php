<!-- 
NAME: CHOW YAN PING
Project name: Nutrifit
DESCRIPTION OF PROGRAM: Main interface for managing admin and user accounts. Includes AJAX-loaded admin/user tables, 
                        profile picture updates, add/delete functions, and responsive popups for user information and actions.
FIRST WRITTEN: 2/6/2025
LAST MODIFIED: 9/7/2025 
-->

<?php
include('../features/connection.php');
include('../features/restriction.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management</title>
  <?php include('../features/embed.php'); ?>
<!-- <link rel="stylesheet" href="../styles/admin_user_management.css"> -->
</head>


<body>
  <?php include('../features/header.php'); ?>
  <div class="content">

    <div class="usermanage_header">
      <h2>User Management</h2>
    </div>

    <div class="usermanage_buttons">
      <button class="green_btn">Update Profile Picture</button>
    </div>

    <div class="record_table_wrapper admin_user_management" id="admin_table_container"></div>
    <div class="record_table_wrapper admin_user_management" id="user_table_container"></div>
<script>
/* const logged_in_adm_id = <?php echo json_encode($logged_in_adm_id); ?>; */
const logged_in_adm_id = 1; //TESTING
</script>
</div>
<?php include('../features/footer.php'); ?>


<!-- Add Admin Popup -->
<div class="popup_overlay" id="add_admin_popup">
  <div class="popup_content">
    <button class="close_popup" onclick="close_popup()">&times;</button>
    <h2>New Admin</h2>
    <div class="popup_form_wrapper">
      <form>
        <div class="popup_field">
          <label for="name">Name: <span>*</span></label>
          <input type="text" id="name" required>
        </div>
        <div class="popup_field">
          <label for="email">Email: <span>*</span></label>
          <input type="email" id="email" required>
          <div id="email_error" style="color: red; font-size: 0.9em; margin-top: 5px; margin-left: 5px"></div>
        </div>
        <div class="popup_field">
          <label for="password">Password: <span>*</span></label>
          <input type="password" id="password" required>
        </div>
      </form>
    </div>
    <div class="popup_submit">
      <button type="submit" class="popup_insert_btn">Insert</button>
    </div>
  </div>
</div>

<!-- Confirm Delete Popup -->
<div class="confirm_popup_overlay" id="confirm_delete_popup">
  <div class="confirm_popup_box">
    <p><strong>Are you sure to <span style="color: #d94444">remove</span> this user?</strong></p>
    <div class="confirm_popup_btn_group">
      <button class="confirm_popup_yes">Yes</button>
      <button class="confirm_popup_no">No</button>
    </div>
  </div>
</div>

<!-- Profile Picture Popup -->
<div class="popup_overlay" id="profile_pic_popup" style="display: none;">
  <div class="profile_popup_content">
    <button class="userinfo_close_btn" onclick="close_profile_popup()">&times;</button>

    <div class="profile_popup_header">
      <h2 class="profile_title">Profile Picture</h2>
      <div class="upload_inline_section">
        <label for="new_profile_upload">Add New Profile: <span style="color: red">*</span></label>
        <input type="file" id="new_profile_upload">
      </div>
    </div>

    <div class="profile_pic_grid">
    <?php
    include '../features/connection.php';

    // Use the correct connection variable name
    $conn = $connection; // Fix naming for consistency

    $stmt = $conn->prepare("SELECT pic_id, pic_picture FROM profile_picture_t ORDER BY pic_id");
    $stmt->execute();
    $stmt->bind_result($picId, $picPicture);

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    while ($stmt->fetch()) {
        if (!empty($picPicture)) {
            $mimeType = $finfo->buffer($picPicture) ?: 'image/jpeg';
            $base64 = base64_encode($picPicture);
            echo "<img src='data:$mimeType;base64,$base64' class='profile_option' data-id='{$picId}'>";
        } else {
            echo "<p style='color: red;'>Invalid image for pic_id {$picId}</p>";
        }
    }

    $stmt->close();
    $conn->close();
    ?>
    </div>
    <div class="profile_btn_wrapper">
      <button class="profile_btn red">Delete Profile</button>
    </div>
  </div>
</div>

<!-- Admin Info Popup -->
<div class="userinfo_popup_overlay" id="admininfo_popup">
  <div class="userinfo_popup_content">
    <button class="userinfo_close_btn" onclick="close_admin_info_popup()">&times;</button>
    <h2>Admin Info</h2>
    <div class="userinfo_details_box" id="admininfo_details"></div>
    <div class="userinfo_delete_wrapper">
      <button class="userinfo_delete_btn" onclick="trigger_admin_delete_confirm()">Delete Admin</button>
    </div>
  </div>
</div>

<!-- User Info Popup -->
<div class="userinfo_popup_overlay" id="userinfo_popup">
  <div class="userinfo_popup_content">
    <button class="userinfo_close_btn" onclick="close_user_info_popup()">&times;</button>
    <h2>User Info</h2>
    <div class="userinfo_details_box" id="userinfo_details"></div>
    <div class="userinfo_delete_wrapper">
      <button class="userinfo_delete_btn" onclick="trigger_user_delete_confirm()">Delete User</button>
    </div>
  </div>
</div>

<script>
function close_popup() {
  document.getElementById("add_admin_popup").style.display = "none";
  document.getElementById("name").value = "";
  document.getElementById("email").value = "";
  document.getElementById("password").value = "";
  document.getElementById("email").style.border = "";

  // Clear email border and error message
  const emailInput = document.getElementById("email");
  const emailError = document.getElementById("email_error");

  emailInput.style.border = "";
  emailError.textContent = "";
  emailStatus = "unchecked";
}

  // OPEN PROFILE POPUP
  document.querySelector('.usermanage_buttons .green_btn').addEventListener('click', () => {
    document.getElementById("profile_pic_popup").style.display = "flex";
    bindProfileImageClicks();
  });

  // CLOSE PROFILE POPUP
  function close_profile_popup() {
    document.getElementById("profile_pic_popup").style.display = "none";

    // Optional: clear selection
    document.querySelectorAll('.profile_option').forEach(i => i.classList.remove('selected'));
    document.getElementById('new_profile_upload').value = "";
  }

  // SELECT PROFILE IMAGE
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('profile_option')) {
      document.querySelectorAll('.profile_option').forEach(i => i.classList.remove('selected'));
      e.target.classList.add('selected');
      selected_pic_id = e.target.getAttribute('data-id');
    }
  });

function open_popup() {
  document.getElementById("add_admin_popup").style.display = "flex";
}

function close_user_info_popup() {
  document.getElementById("userinfo_popup").style.display = "none";
}

function close_admin_info_popup() {
  document.getElementById("admininfo_popup").style.display = "none";
}

function show_user_info_popup(user) {
  const popup = document.getElementById("userinfo_popup");
  const box = document.getElementById("userinfo_details");
  box.innerHTML = `
    <div class="userinfo_row"><span>ID:</span> ${user.id}</div>
    <div class="userinfo_row"><span>Name:</span> ${user.name}</div>
    <div class="userinfo_row"><span>Email:</span> ${user.email}</div>
    <div class="userinfo_row"><span>Age:</span> ${user.age}</div>
    <div class="userinfo_row"><span>Gender:</span> ${user.gender}</div>
    <div class="userinfo_row"><span>Weight:</span> ${user.weight} KG</div>
    <div class="userinfo_row"><span>Height:</span> ${user.height} CM</div>`;
  popup.style.display = "flex";
  popup.setAttribute("data-user-id", user.rawId);
}

function show_admin_info_popup(admin) {
  const popup = document.getElementById("admininfo_popup");
  const box = document.getElementById("admininfo_details");
  box.innerHTML = `
    <div class="userinfo_row"><span>ID:</span> ${admin.id}</div>
    <div class="userinfo_row"><span>Name:</span> ${admin.name}</div>
    <div class="userinfo_row"><span>Email:</span> ${admin.email}</div>`;
  popup.style.display = "flex";
  popup.setAttribute("data-admin-id", admin.rawId);
}

function trigger_user_delete_confirm() {
  const userId = document.getElementById("userinfo_popup").getAttribute("data-user-id");
  const popup = document.getElementById("confirm_delete_popup");
  popup.style.display = "flex";

  popup.querySelector(".confirm_popup_yes").onclick = function () {
    fetch('../features/delete_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${userId}&type=user`
    }).then(res => res.text()).then(response => {
      if (response === 'deleted') {
        popup.style.display = "none";
        document.getElementById("userinfo_popup").style.display = "none";
        loadUserTable(); // Refresh
      } else {
        alert("Delete failed: " + response);
      }
    });
  };

  popup.querySelector(".confirm_popup_no").onclick = function () {
    popup.style.display = "none";
  };
}

function trigger_admin_delete_confirm() {
  const adminId = document.getElementById("admininfo_popup").getAttribute("data-admin-id");
  const popup = document.getElementById("confirm_delete_popup");
  popup.style.display = "flex";

  popup.querySelector(".confirm_popup_yes").onclick = function () {
    fetch('../features/delete_user.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${adminId}&type=admin`
    }).then(res => res.text()).then(response => {
      if (response === 'deleted') {
        popup.style.display = "none";
        document.getElementById("admininfo_popup").style.display = "none";
        loadAdminTable(); // Refresh
      } else {
        alert("Delete failed: " + response);
      }
    });
  };

  popup.querySelector(".confirm_popup_no").onclick = function () {
    popup.style.display = "none";
  };
}

function attachHandlers() {
  document.querySelectorAll(".admin_info_btn").forEach((btn) => {
    btn.onclick = () => {
      const row = btn.closest("tr");
      const admin = {
        rawId: row.getAttribute("data-id"),
        id: row.children[0].textContent,
        name: row.children[1].textContent,
        email: row.children[2].textContent
      };
      show_admin_info_popup(admin);
    };
  });

  document.querySelectorAll(".info_btn").forEach((btn) => {
    btn.onclick = () => {
      const row = btn.closest("tr");
      const user = {
        rawId: row.getAttribute("data-id"),
        id: row.children[0].textContent,
        name: row.children[1].textContent,
        email: row.children[2].textContent,
        age: row.children[3].textContent,
        gender: row.children[4].textContent,
        weight: row.children[5].textContent,
        height: row.children[6].textContent
      };
      show_user_info_popup(user);
    };
  });

  document.querySelectorAll(".del_btn").forEach((btn) => {
    btn.onclick = () => {
      const row = btn.closest("tr");
      const type = row.getAttribute("data-type");
      const id = row.getAttribute("data-id");
      const popup = document.getElementById("confirm_delete_popup");

      popup.style.display = "flex";
      popup.querySelector(".confirm_popup_yes").onclick = () => {
        fetch('../features/delete_user.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${id}&type=${type}`
        }).then(res => res.text()).then(response => {
          if (response === 'deleted') {
            popup.style.display = "none";
            type === 'admin' ? loadAdminTable() : loadUserTable();
          } else {
            alert("Delete failed: " + response);
          }
        });
      };
      popup.querySelector(".confirm_popup_no").onclick = () => {
        popup.style.display = "none";
      };
    };
  });

  document.querySelectorAll(".add_admin_btn").forEach((btn) => {
    btn.onclick = open_popup;
  });

  document.querySelectorAll(".admin_page_btn").forEach(btn => {
    btn.onclick = () => loadAdminTable(btn.getAttribute("data-page"));
  });
  document.querySelectorAll(".user_page_btn").forEach(btn => {
    btn.onclick = () => loadUserTable(btn.getAttribute("data-page"));
  });
}

function loadAdminTable(page = 1) {
  fetch(`../features/fetch_admin_table.php?page=${page}`)
    .then(res => res.text())
    .then(html => {
      document.getElementById("admin_table_container").innerHTML = html;
      attachHandlers();
    });
}

function loadUserTable(page = 1) {
  fetch(`../features/fetch_user_table.php?page=${page}`)
    .then(res => res.text())
    .then(html => {
      document.getElementById("user_table_container").innerHTML = html;
      attachHandlers();
    });
}

document.addEventListener("DOMContentLoaded", () => {
  loadAdminTable();
  loadUserTable();

  const emailInput = document.getElementById('email');
  const emailError = document.getElementById('email_error');
  let emailStatus = "unchecked";

  function isValidEmail(email) {
    // Basic regex: must have @ and domain
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function debounce(func, delay) {
    let timer;
    return function (...args) {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }

  const checkEmail = debounce(function () {
  const email = emailInput.value.trim();

  if (!email) {
    emailStatus = "unchecked";
    emailInput.style.border = "2px solid orange";
    emailError.textContent = "Email is required.";
    return;
  }

  if (!isValidEmail(email)) {
    emailStatus = "invalid";
    emailInput.style.border = "2px solid orange";
    emailError.textContent = "Please enter a valid email address.";
    return;
  }

  // Email format is valid, now check availability
  fetch('../features/add_admin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=check_email&email=' + encodeURIComponent(email)
  })
  .then(res => res.text())
  .then(raw => {
    const response = raw.trim();
    console.log("Email check response:", JSON.stringify(response));

    if (response === 'exists') {
      emailStatus = 'exists';
      emailInput.style.border = "2px solid red";
      emailError.textContent = "This email already exists.";
    } else if (response === 'available') {
      emailStatus = 'available';
      emailInput.style.border = "2px solid green";
      emailError.textContent = "";
    } else {
      emailStatus = 'unchecked';
      emailInput.style.border = "2px solid orange";
      emailError.textContent = "Could not verify email.";
    }
  });
}, 600);

emailInput.addEventListener('input', checkEmail);

  document.querySelector('.popup_insert_btn').addEventListener('click', function () {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!name || !email || !password || emailStatus !== 'available') {
      alert("Please fill all fields and ensure email is unique.");
      return;
    }

    fetch('../features/add_admin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `adm_name=${encodeURIComponent(name)}&adm_email=${encodeURIComponent(email)}&adm_password=${encodeURIComponent(password)}`
    })
    .then(res => res.text())
    .then(response => {
      if (response === 'success') {
        alert("Admin added.");
        close_popup();
        loadAdminTable();
      } else {
        alert("Insert failed: " + response);
      }
    });
  });

document.querySelector('.profile_btn.red').addEventListener('click', () => {
  if (!selected_pic_id) {
    alert("Please select a profile picture to delete.");
    return;
  }

  const confirmed = confirm("Are you sure you want to delete this profile picture?");
  if (!confirmed) return;

  fetch('../features/delete_admin_pic.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `pic_id=${selected_pic_id}`
  })
  .then(res => res.text())
  .then(response => {
    alert(response);

    if (response.trim() === 'Profile picture deleted successfully.') {
      // Remove the image from the grid
      const selectedImage = document.querySelector(`.profile_option.selected`);
      if (selectedImage) {
        selectedImage.remove();
      }
  
      selected_pic_id = null;
    }

    document.getElementById("new_profile_upload").value = '';
  });
});
});

document.getElementById('new_profile_upload').addEventListener('change', () => {
  const fileInput = document.getElementById('new_profile_upload');
  const file = fileInput.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('new_profile_upload', file);

  fetch('../features/upload_admin_pic.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(html => {
    // Append the returned image
    const container = document.querySelector('.profile_pic_grid');
    container.insertAdjacentHTML('beforeend', html);

    // Re-bind click listeners to include the new one
    bindProfileImageClicks();

    // Auto-select the new one
    const newImg = container.querySelector('.profile_option:last-child');
    document.querySelectorAll('.profile_option').forEach(i => i.classList.remove('selected'));
    newImg.classList.add('selected');
    selected_pic_id = newImg.getAttribute('data-id');

    fileInput.value = ""; // reset input
  })
  .catch(err => alert("Upload failed: " + err));
});

let selected_pic_id = null;

function bindProfileImageClicks() {
  document.querySelectorAll('.profile_option').forEach(img => {
    img.addEventListener('click', () => {
      document.querySelectorAll('.profile_option').forEach(i => i.classList.remove('selected'));
      img.classList.add('selected');
      selected_pic_id = img.getAttribute('data-id');
    });
  });
}
</script>
</body>
</html>