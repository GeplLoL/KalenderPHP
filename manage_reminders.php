<?php
session_start();
ob_start();
require_once 'db_connect.php';
require_once 'includes/nav.php';
global $conn;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Function to delete a reminder
function deleteReminder($reminder_id) {
    global $conn;
    $delete_sql = "DELETE FROM `meeldetuletused` WHERE meeldetuletus_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $reminder_id);
    return $delete_stmt->execute();
}

// Function to update a reminder
function updateReminder($reminder_id, $reminder_time) {
    global $conn;
    $update_sql = "UPDATE `meeldetuletused` SET meeldetuletuse_aeg = ? WHERE meeldetuletus_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $reminder_time, $reminder_id);
    return $update_stmt->execute();
}

// Function to add a new reminder
function addReminder($event_id, $reminder_time) {
    global $conn;
    $insert_sql = "INSERT INTO `meeldetuletused` (sondmus_id, meeldetuletuse_aeg) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("is", $event_id, $reminder_time);
    return $insert_stmt->execute();
}

// Process request based on user actions
if (isset($_GET['delete'])) {
    $reminder_id = (int)$_GET['delete'];
    if (deleteReminder($reminder_id)) {
        $_SESSION['message'] = "Reminder deleted successfully";
    }
    header("Location: manage_reminders.php");
    exit();
}

if (isset($_POST['update_reminder'])) {
    $reminder_id = (int)$_POST['reminder_id'];
    $reminder_time = $_POST['reminder_time'];
    if (updateReminder($reminder_id, $reminder_time)) {
        $_SESSION['message'] = "Reminder updated successfully";
    }
    header("Location: manage_reminders.php");
    exit();
}

if (isset($_POST['add_reminder'])) {
    $event_id = (int)$_POST['event_id'];
    $reminder_time = $_POST['reminder_time'];
    if (addReminder($event_id, $reminder_time)) {
        $_SESSION['message'] = "New reminder added successfully";
    }
    header("Location: manage_reminders.php");
    exit();
}

// Fetch reminders and events
$reminders = fetchReminders($user_id);
$events = fetchEvents($user_id);

// Fetch functions
function fetchReminders($user_id) {
    global $conn;
    $reminders = [];
    $reminder_sql = "SELECT `meeldetuletused`.meeldetuletus_id, `meeldetuletused`.meeldetuletuse_aeg, `sondmused`.pealkiri 
                     FROM `meeldetuletused`
                     INNER JOIN `sondmused` ON `meeldetuletused`.sondmus_id = `sondmused`.sondmus_id 
                     WHERE `sondmused`.kasutaja_id = ?";
    $stmt = $conn->prepare($reminder_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function fetchEvents($user_id) {
    global $conn;
    $events = [];
    $event_sql = "SELECT sondmus_id, pealkiri FROM `sondmused` WHERE kasutaja_id = ?";
    $stmt = $conn->prepare($event_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <title>Meeldetuletuste haldamine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script>
        function disableBtn() {
            document.getElementById('rem-btn').disabled = true;
        }

        function fieldsValidation() {
            const reminderTime = document.getElementById('reminder_time').value;
            document.getElementById('rem-btn').disabled = !reminderTime;
        }
    </script>
</head>

<body onload="disableBtn();">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Meeldetuletuste haldamine</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Форма добавления нового напоминания -->
        <div class="card mb-4">
            <div class="card-header text-white bg-primary">Lisa uus meeldetuletus</div>
            <div class="card-body">
                <form method="post" action="manage_reminders.php">
                    <div class="mb-3">
                        <label for="event_id" class="form-label">Vali sündmus:</label>
                        <select name="event_id" id="event_id" class="form-select" required>
                            <?php foreach ($events as $event): ?>
                                <option value="<?php echo $event['sondmus_id']; ?>">
                                    <?php echo htmlspecialchars($event['pealkiri']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="reminder_time" class="form-label">Meeldetuletuse aeg:</label>
                        <input oninput="fieldsValidation()" type="datetime-local" name="reminder_time" id="reminder_time" class="form-control" required>
                    </div>

                    <button type="submit" name="add_reminder" class="btn btn-success w-100" id="rem-btn">Lisa meeldetuletus</button>
                </form>
            </div>
        </div>

        <!-- Таблица с напоминаниями -->
        <?php if (count($reminders) > 0): ?>
            <h3 class="mb-3">Sinu meeldetuletused</h3>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Sündmuse pealkiri</th>
                        <th>Meeldetuletuse aeg</th>
                        <th>Tegevused</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reminders as $reminder): ?>
                        <tr>
                            <form method="post" action="manage_reminders.php">
                                <td>
                                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($reminder['pealkiri']); ?>">
                                </td>
                                <td>
                                    <input type="hidden" name="reminder_id" value="<?php echo $reminder['meeldetuletus_id']; ?>">
                                    <input type="datetime-local" name="reminder_time"
                                           value="<?php echo date('Y-m-d\TH:i', strtotime($reminder['meeldetuletuse_aeg'])); ?>"
                                           class="form-control" required>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="submit" name="update_reminder" class="btn btn-warning">Muuda</button>
                                        <a href="manage_reminders.php?delete=<?php echo $reminder['meeldetuletus_id']; ?>"
                                           class="btn btn-danger"
                                           onclick="return confirm('Kas olete kindel, et soovite kustutada meeldetuletuse?');">Kustuta</a>
                                    </div>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-info">Teil pole meeldetuletusi.</p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="../calendar" class="btn btn-secondary">Tagasi sündmuste juurde</a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <?php include 'includes/footer.html'; ?>
</body>
</html>
