<?php

// session_start();

include('../features/connection.php');

include('../features/restriction.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $user_id = $_SESSION['usr_id'];
    $feedback_rating = $_POST['feedback_rating'];
    $feedback_response = $_POST['feedback_response'];

    // Insert query
    $sql = "INSERT INTO feedback_t (usr_id, fdbk_timestamp, fdbk_rating, fdbk_response)
            VALUES (?, NOW(), ?, ?)";

    $stmt = $connection -> prepare($sql);
    $stmt -> bind_param("iis", $user_id, $feedback_rating, $feedback_response);

    if ($stmt -> execute()) {
        echo "
        <script>
            alert('Your form has been submitted.');
            window.location.href = 'about_us.php';
        </script>";
    } else {
        echo "
        <script>
            alert('Your submission has failed.');
            window.location.href = 'about_us.php';
        </script>";
    }

    $stmt -> close();
    $conn -> close();
    

}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php include('../features/embed.php'); ?>
</head>
<body>
    <div >
        <?php include('../features/header.php')?>
        <div id="top_section">
            <div id="about_us_title">
                <p><b><u>About Us</u></b></p>
            </div>

            <div class="about_us_text" id="about_us_text1">
                <p>At NutriFit, we believe everyone deserves a healthier lifestyle tailored to their goals. Whether it's losing weight, building muscle, eating better, or staying active, we're here to guide you.</p>
            </div>

            <div class="about_us_text" id="about_us_text2">
                <p>Our Mission:</p>
                <p>To empower individuals to reach their fitness and wellness goals through personalized workout routines and nutrition plans, backed by science and easy to follow.</p>
            </div>

            <div class="about_us_text" id="about_us_text3">
                <p>What We Offer:</p>
                <ul>
                    <li>Custom Fitness Plans: Designed around your goals, experience level, and preferences.</li>
                    <li>Personalized Meal Plans: Nutritious, goal-oriented meals that fit your lifestyle.</li>
                    <li>Progress Tracking: Keep an eye on your improvements with smart tracking tools.</li>
                    <li>Expert Advice: Evidence-based tips and guidance from fitness and nutrition professionals.</li>
                </ul>
            </div>

            <div id="explanation">
                <h4>Why NutriFit?</h4>
                <p>We’re more than just an app—we’re a support system. Our goal is to simplify healthy living, eliminate guesswork, and motivate you to become your best self. 
                    Join the NutriFit community and take control of your health today.</p>
            </div>

            <div id="social_media">
                <a href="https://facebook.com">
                    <div id="facebook">
                        <img src="../images/about-facebook.png" alt="Facebook icon">
                        <p>Follow us in Facebook</p>
                    </div>
                </a>

                <a href="https://instagram.com">
                    <div id="instagram">
                        <img src="../images/about-instagram.png" alt="Instagram icon">
                        <p>Follow us in Instagram</p>
                    </div>
                </a>
            </div>
        </div>

        <div id="bottom_section">
            <div id="feedback_title">
                <h1><i>Give us a Feedback</i></h1>
            </div>

            <div id="feedback_content">
                <form id="feedback_form" action="" method="POST">
                    <div class="rating_options">
                        <label>
                            <input type="radio" name="feedback_rating" value="1">
                            <span>1</span>
                        </label>
                        <label>
                            <input type="radio" name="feedback_rating" value="2">
                            <span>2</span>
                        </label>
                        <label>
                            <input type="radio" name="feedback_rating" value="3">
                            <span>3</span>
                        </label>
                        <label>
                            <input type="radio" name="feedback_rating" value="4">
                            <span>4</span>
                        </label>
                        <label>
                            <input type="radio" name="feedback_rating" value="5">
                            <span>5</span>
                        </label>
                    </div>

                    <textarea id="feedback_response" name="feedback_response" rows="4" cols="50" placeholder="Feedback.."></textarea>
                    <button type="submit">SUBMIT FEEDBACK</button>
                </form>
            </div>
        </div>

        <?php include('../features/footer.php') ?>
    </div>
    <script>
        document.getElementById("feedback_form").addEventListener("submit", function(event) {
            const rating = document.querySelector('input[name="feedback_rating"]:checked');

            if (!rating) {
                alert("Please select a rating before submitting.");
                event.preventDefault(); // Stop form submission
            }
        });
    </script>
</body>
</html>
