<?php
// NAME : Mr. Ivan Shak Loong Wye  
// PROJECT NAME : feedback.php  
// DESCRIPTION OF PROGRAM :  
//     This script allows administrators to view feedback obtained from users 
// 	and present it in an intuitive and clear table format.
// 	An additional button is also provided to view the feedback in more detail,
// 	including the text area explanation provided by the user.

// FIRST WRITTEN : June 16th, 2025  
// LAST MODIFIED : July 9th, 2025   
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


include(__DIR__ . '/../features/connection.php');

include(__DIR__ . '/../features/embed.php');

include(__DIR__ . '/../features/restriction.php');

$sql = "SELECT f.fdbk_timestamp, f.fdbk_rating, f.fdbk_response, u.usr_name, u.usr_email 
        FROM feedback_t f
        JOIN user_t u ON f.usr_id = u.usr_id";

$result = mysqli_query($connection, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($connection));
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Feedback</title>
</head>
<body>
    <div class="overlay"></div>

    <?php include('../features/header.php') ?>
    <div class="content" id="content">
        <div id="feedback_title">
            <h1>User Feedback</h1>
        </div>

        <div id="feedback_table">
            <table>
                <tr>
                    <th>Date & Time</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Rating</th>
                    <th>View Feedback</th>
                </tr>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?php echo $row['fdbk_timestamp']?></td>
                        <td><?php echo $row['usr_name']?></td>
                        <td><?php echo $row['usr_email']?></td>
                        <td><?php echo $row['fdbk_rating']?></td>
                        <td><img 
                            src="../images/feedback-info.png"
                            onclick="showPopup(
                                '<?php echo $row['fdbk_timestamp']; ?>',
                                '<?php echo $row['usr_name']; ?>',
                                '<?php echo $row['usr_email']; ?>',
                                '<?php echo $row['fdbk_rating']; ?>',
                                '<?php echo $row['fdbk_response']; ?>'
                            );">
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>

    <div id="feedback_popup" style="display: none">
        <div id="feedback_popup_title">
            <button id="exit_button" onclick="hidePopup();"><img src="../images/feedback-exit.jpg" alt=""></button>
            <h1>User Feedback</h1>
        </div>

        <div id="feedback_popup_info">
            <div id="left_section">
                <div><b>Date Sent: </b><span id="popup_date"></span></div>
                <div><b>From: </b><span id="popup_name"></span></div>
                <div><b>Rating: </b><span id="popup_rating"></span></div>
            </div>
            <div id="right_section">
                <div><b>Email: </b><span id="popup_email"></span></div>
            </div>
        </div>

        <div id="feedback_popup_response">
            <div><b>Response: </b></div>
            <div id="popup_response"></div>
        </div>
    </div>

    <?php include('../features/footer.php') ?>
    
</body>
</html>
