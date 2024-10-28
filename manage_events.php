<?php
session_start();
ob_start(); // Start output buffering
require_once 'db_connect.php';
require_once 'includes/nav.php';
global $conn;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle delete event request
if (isset($_GET['delete'])) {
    $event_id = intval($_GET['delete']); // Преобразуем в int для защиты от инъекций
    $delete_sql = "DELETE FROM sondmused WHERE sondmus_id = ? AND kasutaja_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $event_id, $user_id); // Проверка user_id для удаления только своих событий
    $delete_stmt->execute();

    header("Location: manage_events.php");
    exit();
}

// Handle update event request
if (isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $update_sql = "UPDATE `sondmused` SET pealkiri = ?, kirjeldus = ?, algus_aeg = ?, lopp_aeg = ? WHERE sondmus_id = ? AND kasutaja_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ssssii", $title, $description, $start_time, $end_time, $event_id, $user_id);
    $update_stmt->execute();
}

// Handle add event request
if (isset($_POST['add_event'])) {
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    $insert_sql = "INSERT INTO sondmused (kasutaja_id, pealkiri, kirjeldus, algus_aeg, lopp_aeg, loodud) VALUES (?, ?, ?, ?, ?, NOW())";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("issss", $user_id, $title, $description, $start_time, $end_time);
    $insert_stmt->execute();
}

// Fetch events for logged-in user
$events = [];
$event_sql = "SELECT sondmus_id, pealkiri, kirjeldus, algus_aeg, lopp_aeg FROM `sondmused` WHERE kasutaja_id = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $user_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();

while ($event = $event_result->fetch_assoc()) {
    $events[] = $event;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <title>Sündmuste haldamine</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <script src="js/events_script.js"></script>
</head>

<body onload="disableBtn();">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Sündmuste haldamine</h2>

        <!-- Form to add new event -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                Lisa uus sündmus
            </div>
            <div class="card-body">
                <form method="post" action="manage_events.php">
                    <div class="mb-3">
                        <label for="title" class="form-label">Pealkiri:</label>
                        <input oninput="fieldsValidation()" type="text" name="title" id="title" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Kirjeldus:</label>
                        <textarea oninput="fieldsValidation()" name="description" id="description" class="form-control" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="start_time" class="form-label">Algusaeg:</label>
                        <input oninput="fieldsValidation()" type="datetime-local" name="start_time" id="start_time" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="end_time" class="form-label">Lõpuaeg:</label>
                        <input oninput="fieldsValidation()" type="datetime-local" name="end_time" id="end_time" class="form-control" required>
                    </div>

                    <button type="submit" name="add_event" class="btn btn-custom w-100" id="events-btn">Lisa sündmus</button>
                </form>
            </div>
        </div>

        <!-- Display events with options to edit or delete -->
        <?php if (count($events) > 0): ?>
            <h3 class="mb-3">Sinu sündmused</h3>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Pealkiri</th>
                        <th>Kirjeldus</th>
                        <th>Algusaeg</th>
                        <th>Lõpuaeg</th>
                        <th>Tegevused</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <form method="post" action="manage_events.php">
                                <td>
                                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event['sondmus_id']); ?>">
                                    <input type="text" name="title" value="<?php echo htmlspecialchars($event['pealkiri']); ?>"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <textarea name="description" class="form-control" required><?php echo htmlspecialchars($event['kirjeldus']); ?></textarea>
                                </td>
                                <td>
                                    <input type="datetime-local" name="start_time"
                                        value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['algus_aeg']))); ?>"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <input type="datetime-local" name="end_time"
                                        value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($event['lopp_aeg']))); ?>"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <button type="submit" name="update_event" class="btn btn-warning mb-2 fixed-size">Muuda</button>
                                    <a href="manage_events.php?delete=<?php echo htmlspecialchars($event['sondmus_id']); ?>"
                                        class="btn btn-danger fixed-size"
                                        onclick="return confirm('Kas olete kindel, et soovite kustutada sündmuse?');">Kustuta</a>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-info">Teil pole sündmusi.</p>
        <?php endif; ?>

        <!-- Button to redirect back to events.php -->
        <div class="text-center mt-4">
            <a href="../calendar" class="btn btn-secondary">Tagasi sündмuste juurde</a>
        </div>
    </div>
<?php include 'includes/footer.html'; ?>

</body>
</html>
