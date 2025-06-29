<?php
include '../features/connection.php';

// $_SESSION['account_type'] = 'user';
include '../features/header.php';

include '../features/embed.php'; 

// Handle AJAX to log workout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'log_workout') {
    $usr_id = 1; // Temporary fixed user
    $work_id = $_POST['work_id'];
    $duration = $_POST['duration'];
    $intensity = $_POST['intensity'];
    $calories = $_POST['calories'];
    $timestamp = date('Y-m-d H:i:s');

    $insert = "INSERT INTO user_workout_session_t 
               (usr_id, work_id, wlog_timestamp, wlog_duration, wlog_calories_burned, wlog_intensity) 
               VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $connection->prepare($insert);
    $stmt->bind_param("iisdds", $usr_id, $work_id, $timestamp, $duration, $calories, $intensity);
    $stmt->execute();
    $stmt->close();
    ob_clean();
    echo json_encode(['status' => 'success']);
    exit;
}

$work_id = isset($_GET['work_id']) ? (int)$_GET['work_id'] : 1;

$query = "SELECT w.work_name, w.work_description, w.work_MET, w.work_image, 
                 w.work_beginner, w.work_intermediate, w.work_intense,
                 c.cate_name,
                 uwl.weight_log_weight, u.usr_gender, u.usr_birthdate
          FROM workout_t w
          LEFT JOIN category_t c ON w.cate_id = c.cate_id
          JOIN user_t u ON u.usr_id = 1
          LEFT JOIN user_weight_log_t uwl ON uwl.weight_log_id = (
              SELECT weight_log_id 
              FROM user_weight_log_t 
              WHERE usr_id = u.usr_id 
              ORDER BY weight_log_date DESC 
              LIMIT 1
          )
          WHERE w.work_id = ?";

$stmt = $connection->prepare($query);
$stmt->bind_param("i", $work_id);
$stmt->execute();
$stmt->bind_result($name, $description, $met, $image, $beginner, $intermediate, $intense, $category, $weight, $gender, $birthdate);
$stmt->fetch();
$stmt->close();

$imgSrc = 'data:image/png;base64,' . base64_encode($image);
$today = new DateTime();
$birthDate = new DateTime($birthdate);
$age = $today->diff($birthDate)->y;

$genderFactor = ($gender === 'M') ? 1.0 : 0.95;
$ageFactor = ($age < 40) ? 1.0 : (($age < 50) ? 0.97 : (($age < 60) ? 0.94 : 0.91));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Workout Training</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="motivation-wrapper">
  <h2 class="slogan-text2">
    <span class="bold-shadow">REMEM<span class="red">BE</span>R <span class="red">Who</span><br>
    <span class="red">You Want</span>ed <span class="red">To Be</span>
    </span>
  </h2>
  <div class="level-select">
    <label>Level</label>
    <div class="level-buttons">
      <button class="level-btn active" data-level="beginner">Beginner</button>
      <button class="level-btn" data-level="intermediate">Intermediate</button>
      <button class="level-btn" data-level="intense">Intense</button>
    </div>
  </div>
</div>

<div class="shape-wrapper2">
  <div class="gray-background"></div>
  <div class="shape2">
    <div class="session-wrapper">

      <div class="left-section">
        <h1><?php echo htmlspecialchars($name); ?></h1>
        <div class="stat-row">
          <span><i class="fa-solid fa-fire"></i> <span id="kcal">0</span> kcal</span>
          <span><i class="fa-solid fa-clock"></i> <span id="duration">0</span> min</span>
        </div>
        <p><strong>Category</strong></p>
        <button class="category-btn"><?php echo htmlspecialchars($category); ?></button>
        <p class="description"><?php echo htmlspecialchars($description); ?></p>
      </div>

      <div class="right-section2">
        <div class="timer-circle">
        <svg id="progress-ring" width="400" height="400">
          <circle id="progress-background" cx="200" cy="200" r="180" stroke="#eee" stroke-width="15" fill="none" />
          <circle id="progress-ring-circle" cx="200" cy="200" r="180" stroke="#BCD454" stroke-width="15" fill="none" stroke-linecap="round" />
        </svg>
          <div id="time-display">5:00</div>
        </div>
      </div>
    </div>
  </div>

<div class="button-area">
  <div id="start-wrapper">
    <button id="start-btn" class="action-btn green">Start Training</button>
  </div>
  <div id="control-wrapper" style="display: none;">
    <button id="pause-btn" class="action-btn white">⏸ Stop</button>
    <button id="end-btn" class="action-btn green">End Training</button>
  </div>
</div>


<script>
const MET = <?php echo $met; ?>;
const weight = <?php echo $weight; ?>;
const genderFactor = <?php echo $genderFactor; ?>;
const ageFactor = <?php echo $ageFactor; ?>;
const work_id = <?php echo $work_id; ?>;

let durations = {
  beginner: <?php echo $beginner; ?>,
  intermediate: <?php echo $intermediate; ?>,
  intense: <?php echo $intense; ?>
};

let kcalPerLevel = {
  beginner: Math.round(MET * 0.75 * weight * (durations.beginner / 60) * genderFactor * ageFactor),
  intermediate: Math.round(MET * 1.00 * weight * (durations.intermediate / 60) * genderFactor * ageFactor),
  intense: Math.round(MET * 1.25 * weight * (durations.intense / 60) * genderFactor * ageFactor)
};


let selectedLevel = 'beginner';


function formatTime(seconds) {
  if (seconds <= 0) return "0:00";
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
}

let countdown;
let remaining = 0;
let secondsElapsed = 0;
let totalSeconds = 0;
let isPaused = false;

const ring = document.getElementById('progress-ring-circle');
const fullDash = 2 * Math.PI * 180;

function updateRingProgress(elapsed, total) {
  const offset = fullDash * (1 - (elapsed / total));
  ring.style.strokeDashoffset = offset;
}

function startTimer(seconds) {
  clearInterval(countdown);

  // Only reset if starting a fresh session
  if (secondsElapsed === 0 || remaining <= 0) {
    totalSeconds = seconds;
    remaining = seconds;
    secondsElapsed = 0;
  }

  ring.style.strokeDasharray = fullDash;
  updateRingProgress(secondsElapsed, totalSeconds);
  $('#time-display').text(formatTime(remaining));

  countdown = setInterval(() => {
    if (remaining <= 0) {
      clearInterval(countdown);
      $('#time-display').text("0:00");
      updateRingProgress(totalSeconds, totalSeconds);
      setTimeout(() => {
        if (confirm("Training complete! View your result.")) {
          logAndRedirect(durations[selectedLevel], selectedLevel, kcalPerLevel[selectedLevel]);
        }
      }, 300);
      return;
    }

    secondsElapsed++;
    remaining--;
    $('#time-display').text(formatTime(remaining));
    updateRingProgress(secondsElapsed, totalSeconds);
  }, 1000);
}

function logAndRedirect(durationMins, level, calories) {
  $.post(window.location.href, {
    action: 'log_workout',
    work_id: work_id,
    duration: durationMins,
    intensity: level.charAt(0).toUpperCase() + level.slice(1),
    calories: calories
  }, function (res) {
    if (res.status === 'success') {
      window.location.href = 'fitness_result.php';
    }
  }, 'json');
}

const levelButtons = document.querySelectorAll('.level-btn');

levelButtons.forEach(button => {
  button.addEventListener('click', () => {
    levelButtons.forEach(btn => btn.classList.remove('active')); 
    button.classList.add('active'); 
  });
});

$(document).ready(function () {
  function updateStats(level) {
    $('#kcal').text(kcalPerLevel[level]);
    $('#duration').text(durations[level]);
    $('#time-display').text(formatTime(Math.round(durations[level] * 60)));
  }

  updateStats(selectedLevel);

  $('.level-btn').click(function () {
    selectedLevel = $(this).data('level');
    updateStats(selectedLevel);
  });

  $('#start-btn').click(function () {
    const seconds = Math.round(durations[selectedLevel] * 60);
    startTimer(seconds);
    $('#start-wrapper').hide();
    $('#control-wrapper').show();
  });

let isPaused = false;

$('#pause-btn').click(function () {
  if (!isPaused) {
    clearInterval(countdown);
    $(this).text('▶ Resume');
    isPaused = true;
  } else {
    startTimer(totalSeconds);
    $(this).text('⏸ Stop');
    isPaused = false;
  }
});


  $('#end-btn').click(function () {
    clearInterval(countdown);
    const secondsTrained = totalSeconds - remaining;
    const minutesTrained = Math.round(secondsTrained / 60);
    const adjustedKcal = Math.round(kcalPerLevel[selectedLevel] * (secondsTrained / totalSeconds));

    if (remaining > 0) {
      if (confirm("You haven’t completed the full workout. End training anyway?")) {
        logAndRedirect(minutesTrained, selectedLevel, adjustedKcal);
      }
    } else {
      logAndRedirect(durations[selectedLevel], selectedLevel, kcalPerLevel[selectedLevel]);
    }
  });
});


</script>
</body>
</html>

<?php include '../features/footer.php' ?>