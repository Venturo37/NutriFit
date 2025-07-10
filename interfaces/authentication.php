<!-- 
NAME : Mr. Ivan Shak Loong Wye
PROJECT NAME : authentication.php
DESCRIPTION OF PROGRAM : 
	This script manages the full user authentication process, including login, registration, and password reset. 
	It also handles validation, session control, and error messaging. 
	Furthermore, it ensures secure access to protected parts of the system. 
	Designed to interact with both front-end forms and backend email handling via SMTP configuration.

FIRST WRITTEN : June 7th, 2025
LAST MODIFIED : July 9th, 2025 
-->

<?php
// UPDATE THIS TO UR DIRECTORY
$base_url = "http://localhost/Nutrifit/interfaces/authentication.php";
// $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
// $host = $_SERVER['HTTP_HOST'];
// $script_name = dirname($_SERVER['SCRIPT_NAME']);
// $base_url = $protocol . $host . $script_name;

// echo $base_url . "/interfaces/authentication.php";


include('../features/connection.php');


include('../features/embed.php');
if (isset($_SESSION['usr_id']) || isset($_SESSION['adm_id']) ) {
    unset($_SESSION['usr_id']); 
    unset($_SESSION['adm_id']); 
} 

include('../features/restriction.php');

$showResetForm = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $token_hash = hash("sha256", $token);

    $mysqli = require __DIR__ . '/../features/connection.php';

    $stmt = $mysqli->prepare("SELECT usr_id, rst_pass_log_expires FROM reset_password_log_t WHERE rst_pass_log_token = ?");
    $stmt->bind_param("s", $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && strtotime($row['rst_pass_log_expires']) > time()) {
        $_SESSION['reset_usr_id'] = $row['usr_id'];
        $showResetForm = true;
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action =  $_POST['action'];

    if ($action == 'signup') {

        # OBTAIN FORM VALUES
        $username = $_POST['username_input'];
        $email = $_POST['signup_email_input'];
        $password = $_POST['signup_password_input'];
        $gender = $_POST['gender_input'];
        $date = $_POST['date_input'];
        $weight = $_POST['weight_input'];
        $height = $_POST['height_input'];

        # OBTAIN FIRST PICTURE IN DATABASE AND ASSIGN
        $retrievePicture = $connection -> query("SELECT pic_id FROM profile_picture_t ORDER BY pic_id ASC LIMIT 1");
        $picRow = $retrievePicture -> fetch_assoc();
        $picId = $picRow['pic_id'];

        # VERIFY EMAIL UNIQUENESS
        $checkStmt = $connection -> prepare("SELECT COUNT(*) FROM user_t WHERE usr_email = ?");
        $checkStmt -> bind_param("s", $email);
        $checkStmt -> execute();
        $checkStmt -> bind_result($emailExists);
        $checkStmt -> fetch();
        $checkStmt -> close();

        if ($emailExists) {
            echo "<script>
                    alert('This email is already registered, please try a new one.');
                    window.location.href = 'authentication.php';
                </script>";
            exit();
        } else {
            // Insert new user
            $stmt = $connection -> prepare("INSERT INTO user_t (usr_name, usr_password, usr_birthdate, usr_gender, usr_email, usr_height, pic_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt -> bind_param("sssssds", $username, $password, $date, $gender, $email, $height, $picId);
            $stmt -> execute();
            
            $usrId = $connection->insert_id;

            $stmt->close();

            // Insert the weight into user_weight_log_t
            $weightStmt = $connection -> prepare("INSERT INTO user_weight_log_t (usr_id, weight_log_weight) VALUES (?, ?)");
            $weightStmt -> bind_param("id", $usrId, $weight);
            $weightStmt -> execute();
            $weightStmt -> close();

            // Optional: redirect or show success
            echo "<script>
                    alert('Registration successful!');
                    window.location.href = 'authentication.php';
                </script>";
            exit();

        }
    }

    // CHANGES, YOU CAN CHANGE THE LOCATION TO WHATEVER
    if ($action == 'login') {
        $email = $_POST['login_email_input'];
        $password = $_POST['login_password_input'];

        // Check user_t
        $query_user = "SELECT * FROM user_t WHERE usr_email='$email' AND usr_password='$password'";
        $result_user = mysqli_query($connection, $query_user);

        if (mysqli_num_rows($result_user) == 1) {
            $row = mysqli_fetch_assoc($result_user);
            $_SESSION['usr_id'] = $row['usr_id'];

            header("Location: fitness_page.php");
            exit();
        }

        // Check admin_t
        $query_admin = "SELECT * FROM admin_t WHERE adm_email='$email' AND adm_password='$password'";
        $result_admin = mysqli_query($connection, $query_admin);

        if (mysqli_num_rows($result_admin) == 1) {
            $row = mysqli_fetch_assoc($result_admin);
            $_SESSION['adm_id'] = $row['adm_id'];

            header("Location: admin_dashboard.php");
            exit();
        }

        // If neither matched
        echo "<script>
                alert('Login failed! Please try again.');
                window.location.href = 'authentication.php';
            </script>";
        exit();
    }


    // RESET PASSWORD PHP //

    if ($action == 'verify') {
        $email = $_POST["verify_email_input"];

        $token = bin2hex(random_bytes(16));
        $token_hash = hash("sha256", $token);
        $expiry = date("Y-m-d H:i:s", time() + 60 * 30);
        // $expiry = date("Y-m-d H:i:s", time() + 30);

        // CHANGES
        $mysqli = require __DIR__ . '/../features/connection.php';

        $get_user_sql = "SELECT usr_id FROM user_t WHERE usr_email = ?";
        $get_user_stmt = $mysqli->prepare($get_user_sql);
        $get_user_stmt->bind_param("s", $email);
        $get_user_stmt->execute();
        $result = $get_user_stmt->get_result();

        if ($result->num_rows === 0) {
            echo "<script>
                    alert('No user found with that email.');
                    window.location.href = 'authentication.php';
                </script>";
            exit();
        }

        $user = $result->fetch_assoc();
        $usr_id = $user['usr_id'];

        $update_sql = "INSERT INTO reset_password_log_t (usr_id, rst_pass_log_token, rst_pass_log_expires)
                    VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($update_sql);
        $stmt->bind_param("iss", $usr_id, $token_hash, $expiry);
        $stmt->execute();

        // Send the reset link
        $reset_link = $base_url . "?token=$token";
        $to = $email;
        $subject = "Reset your password";
        $message = "Hi,\n\nClick the following link to reset your password:\n$reset_link\n\nThis link will expire in 30 minutes.";
        $headers = "From: Your Name <ivanshak3@gmail.com>\r\n";
        $headers .= "Reply-To: ivanshak3@gmail.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";



        if (mail($to, $subject, $message, $headers)) {
            echo "<script>
                    alert('✅ Reset link sent to your email!');
                    window.location.href = 'authentication.php';
                </script>";
        } else {
            echo "<script>
                    alert('❌ Failed to send email.');
                    
                </script>";
        }
    }

    if ($action == 'reset') {
        $newPassword = $_POST['new_password_input'];
        $confirmPassword = $_POST['confirm_password_input'];

        if ($newPassword !== $confirmPassword) {
            echo "<script>
                alert('Passwords do not match!');
                window.location.href = '$base_url?token=$token';
            </script>";
            exit();
        }

        if (!isset($_SESSION['reset_usr_id'])) {
            echo "<script>
                    alert('Invalid session. Please try again.');
                    window.location.href = 'authentication.php';
                </script>";
            exit();
        }

        $usr_id = $_SESSION['reset_usr_id'];

        $stmt = $connection->prepare("UPDATE user_t SET usr_password = ? WHERE usr_id = ?");
        $stmt->bind_param("si", $newPassword, $usr_id);
        $stmt->execute();
        $stmt->close();

        unset($_SESSION['reset_usr_id']);

        echo "<script>
                alert('✅ Password has been reset. Please log in.');
                window.location.href = 'authentication.php';
            </script>";
        exit();
    }


}








?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriFit - Authentication</title>
</head>
<body>

<!-- Top left title card -->
<div id="content">
    <div class="authentication_container">
        <?php include("../features/header.php") ?>

        <div class="background-shape shape1"></div>
        <div class="background-shape shape3"></div>
        <img src="../images/auth-login-mobile.png" class="bottom-image image-login">
        <img src="../images/auth-signup.png" class="bottom-image image-signup">
        <img src="../images/auth-reset-mobile.png" class="bottom-image image-verify">
        <img src="../images/auth-reset-mobile.png" class="bottom-image image-reset">

        <div id="login">
            <div class="left_section">
                <form id="login_form" class="auth_form" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="header">
                        <h1>Welcome</h1>
                        <p>Please fill in your registered information</p>
                    </div>
                    
                    <div class="email">
                        <label for="login_email_input">Email: </label>
                        <input type="email" id="login_email_input" name="login_email_input" required>
                    </div>

                    <div class="password">
                        <label for="login_password_input">Password: </label>
                        <input type="password" id="login_password_input" name="login_password_input" required>
                    </div>

                    <div id="forgot_password">
                        <a href="#" onclick="showForm('verify')">Forgot password?</a>
                    </div>

                    <div class="button">
                        <button type="submit">Log In</button>
                    </div>

                    <div id="no_account">
                        <p>Don't have an account? <a href="#" onclick="showForm('signup')">Sign up!</a></p>
                    </div>
                </form>
            </div>
            <div class="right_section">
                <div><img src="../images/auth-login.png" alt=""></div>
            </div>
        </div>

        <div id='verify'>
            <div class="left_section">
                <form id="verify_form" class="auth_form" method="POST">
                    <input type="hidden" name="action" value="verify">
                    <div class="header">
                        <h1>Please fill in your <b>Email</b> to verify a new password for us!</h1>
                    </div>
                    
                    <div class="email">
                        <label for="verify_email_input">Email: </label>
                        <input type="email" id="verify_email_input" name="verify_email_input" required>
                    </div>

                    <div class="button"> 
                        <button type="submit">Send</button>
                    </div>
                </form>
            </div>
            <div class="right_section">
                <div><img src="../images/auth-reset.png" alt=""></div>
            </div>
        </div>

        <div id='reset'>
            <div class="left_section">
                <form id="reset_form" class="auth_form" method="POST">
                    <input type="hidden" name="action" value="reset">
                    <div class="header">
                        <h1>Please fill in your <b>New Password</b> and <b>Confirm Password</b> to reset your password!</h1>
                    </div>
                    
                    <div id="new_password">
                        <label for="new_password_input">New Password: </label>
                        <input type="password" id="new_password_input" name="new_password_input" required>
                    </div>

                    <div id="confirm_password">
                        <label for="confirm_password_input">Confirm Password: </label>
                        <input type="password" id="confirm_password_input" name="confirm_password_input" required>
                    </div>

                    <div class="button">
                        <button type="submit">Reset Password</button>
                    </div>
                </form>
            </div>
            <div class="right_section">
                <div><img src="../images/auth-reset.png" alt=""></div>
            </div>
        </div>

        <div id='signup'>
            <div class="left_section">
                <form id="signup_form" class="auth_form" method="POST">
                    <input type="hidden" name="action" value="signup">

                    <!-- PAGE 1 -->
                    <div id="signup_page1" style="display: none;">
                        <div class="header">
                            <h1>Create Your Account</h1>
                        </div>

                        <div id="username">
                            <label for="username_input">Username: </label>
                            <input type="text" id="username_input" name="username_input">
                        </div>
                        
                        <div id="email">
                            <label for="signup_email_input">Email: </label>
                            <input type="email" id="signup_email_input" name="signup_email_input">
                        </div>

                        <div id="password">
                            <label for="signup_password_input">Password: </label>
                            <input type="password" id="signup_password_input" name="signup_password_input">
                        </div>

                        <div id="gender">
                            <label class="gender_option">
                                <input type="radio" name="gender_input" value="M">
                                <div class="gender_button">
                                    <img src="../images/auth-signup-male.png" alt="">
                                    <div class="checkmark">
                                        <img src="../images/auth-signup-checkmark.png" alt="Selected">
                                    </div>

                                </div>
                                <p>Male</p>
                            </label>

                            <label class="gender_option">
                                <input type="radio" name="gender_input" value="F">
                                <div class="gender_button">
                                    <img src="../images/auth-signup-female.png" alt="">
                                    <div class="checkmark">
                                        <img src="../images/auth-signup-checkmark.png" alt="Selected">
                                    </div>

                                </div>
                                <p>Female</p>
                            </label>
                        </div>

                        <div id="selector_page1">
                            <div id="content_page1"></div>
                            <div id="button_container_page1">
                                <button type="button" onclick="goToPage2()"></button>
                            </div>
                        </div>



                        <div class="button">
                            <button type="button" onclick="goToPage2()">Next</button>
                        </div>

                        <div class="already_have_account">
                            <p>Already have an account? <a href="#" onclick="showForm('login')">Log in!</a></p>
                        </div>
                    </div>

                    <!-- PAGE 2 -->
                    <div id="signup_page2" style="display: none;">
                        <div class="header">
                            <h1>Create Your Account</h1>
                        </div>

                        <div id="user_input">
                            <div id="date">
                                <label for="date_input">Birth Date: </label>
                                <input type="date" id="date_input" name="date_input">
                            </div>
                            
                            <div id="weight">
                                <label for="weight_input">Weight (kg): </label>
                                <input type="number" id="weight_input" name="weight_input">
                            </div>

                            <div id="height">
                                <label for="height_input">Height (cm): </label>
                                <input type="number" id="height_input" name="height_input">
                            </div>
                        </div>

                        <div id="selector_page2">
                            <div id="button_container_page2">
                                <button type="button" onclick="goToPage1()"></button>
                            </div>
                            <div id="content_page2"></div>
                        </div>

                        <div class="button">
                            <button type="submit">Register</button>


                        </div>

                        <div class="already_have_account">
                            <p>Already have an account? <a href="#" onclick="showForm('login')">Log in!</a></p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="right_section">
                <div><img src="../images/auth-signup.png" alt=""></div>
            </div>
        </div>

        
    </div>
    <?php include("../features/footer.php") ?>
    
</div>


<!-- Users are presented with login page first -->
<script>
    const mediaQueryWidth = window.matchMedia("(max-width: 1200px)");
    const mediaQueryHeight = window.matchMedia("(max-height: 800px)");

    mediaQueryWidth.addEventListener("change", handleResponsiveImage);
    mediaQueryHeight.addEventListener("change", handleResponsiveImage);

    <?php 
    // if ($showResetForm): 
    ?>
        // showForm('reset');
    <?php 
// else: 
?>
        // showForm('login');
    <?php 
    // endif; 
    ?>
</script>

<script>
document.getElementById("signup_form").addEventListener("submit", function(event) {
    const username = document.getElementById("username_input").value.trim();
    const email = document.getElementById("signup_email_input").value.trim();
    const password = document.getElementById("signup_password_input").value.trim();
    const gender = document.querySelector('input[name="gender_input"]:checked');
    const date = document.getElementById("date_input").value.trim();
    const weight = document.getElementById("weight_input").value.trim();
    const height = document.getElementById("height_input").value.trim();

    let errorMessage = "";

    if (!username || !email || !password || !gender || !date || !weight || !height) {
        errorMessage = "Please fill in all fields.";
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errorMessage = "Invalid email format.";
    } else if (password.length < 6) {
        errorMessage = "Password should be at least 6 characters.";
    } else {
        // CHANGE HERE
        // Check if the date is in the future
        const selectedDate = new Date(date);
        const today = new Date();
        // Set the time of today's date to midnight for accurate comparison
        today.setHours(0, 0, 0, 0);
        if (selectedDate > today) {
            errorMessage = "Date cannot be later than today.";
        }
    }

    if (errorMessage) {
        alert(errorMessage);
        event.preventDefault(); // Prevent form submission
    }
});
document.getElementById("reset_form").addEventListener("submit", function(event) {
    const newPassword = document.getElementById("new_password_input").value.trim();
    const confirmPassword = document.getElementById("confirm_password_input").value.trim();

    if (newPassword.length <= 6) {
        alert("Password must be more than 6 characters.");
        event.preventDefault(); // Prevent form submission
    }
});

// window.addEventListener('DOMContentLoaded', () => {
//   const hash = window.location.hash.substring(1); // remove #
//   const validForms = ['login', 'verify', 'reset', 'signup'];

//   if (validForms.includes(hash)) {
//     showForm(hash);
//   } else {
//     showForm('login'); // fallback
//   }
// });

window.addEventListener('DOMContentLoaded', () => {
    <?php if ($showResetForm): ?>
        showForm('reset');
    <?php else: ?>
        const hash = window.location.hash.substring(1); // remove #
        const validForms = ['login', 'verify', 'reset', 'signup'];
        if (validForms.includes(hash)) {
            showForm(hash);
        } else {
            showForm('login'); // fallback
        }
    <?php endif; ?>
});
</script>



</body>
</html>


