<?php
session_start();

require_once 'db_connect.php';
require_once 'includes/nav.php';
global $conn;

// Fetch events for logged-in user
$events = [];
$reminders = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Fetch events
    $sql = "SELECT sondmus_id, pealkiri, kirjeldus, algus_aeg, lopp_aeg FROM `sondmused` WHERE kasutaja_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $events[] = [
            'id' => htmlspecialchars($row['sondmus_id']),
            'title' => htmlspecialchars($row['pealkiri']),
            'description' => htmlspecialchars($row['kirjeldus']),
            'start' => htmlspecialchars($row['algus_aeg']),
            'end' => htmlspecialchars($row['lopp_aeg'])
        ];
    }

    // Fetch reminders
    $reminder_sql = "SELECT meeldetuletuse_aeg FROM meeldetuletused INNER JOIN sondmused ON meeldetuletused.sondmus_id = sondmused.sondmus_id WHERE sondmused.kasutaja_id = ?";
    $reminder_stmt = $conn->prepare($reminder_sql);
    $reminder_stmt->bind_param("i", $user_id);
    $reminder_stmt->execute();
    $reminder_result = $reminder_stmt->get_result();

    while ($reminder = $reminder_result->fetch_assoc()) {
        $reminders[] = htmlspecialchars($reminder['meeldetuletuse_aeg']);
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="et">
<head>
    <meta charset="UTF-8">
    <title>Sündmused</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js'></script>
</head>
<body class="text-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4 gothic-text">Sinu sündmused</h2>

        <!-- Кнопки для управления событиями -->
        <div class="d-flex justify-content-center mb-4">
            <a href="manage_events.php" class="btn btn-custom me-3">Halda sündmusi</a>
            <a href="manage_reminders.php" class="btn btn-custom">Halda meeldetuletusi</a>
        </div>

        <!-- Календарь -->
        <div id='calendar' class="p-4 rounded"></div>

        <!-- Модальное окно для деталей события -->
        <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="eventModalLabel">Sündmuse detailid</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Pealkiri: </strong> <span id="eventTitle"></span></p>
                        <p><strong>Kirjeldus: </strong><span id="eventDescription"></span></p>
                        <p><strong>Algusaeg: </strong> <span id="eventStart"></span></p>
                        <p><strong>Lõpuaeg: </strong> <span id="eventEnd"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Sulge</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            // Функция для генерации случайного темного цвета
            function getRandomDarkColor() {
                var letters = '012345';
                var color = '#';
                for (var i = 0; i < 6; i++) {
                    color += letters[Math.floor(Math.random() * letters.length)];
                }
                return color;
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: <?php echo json_encode($events, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
                eventDidMount: function (info) {
                    // Присваиваем случайный темный цвет каждому событию
                    var randomColor = getRandomDarkColor();
                    info.el.style.backgroundColor = randomColor;
                    info.el.style.borderColor = randomColor;
                },
                eventClick: function (info) {
                    $('#eventTitle').text(info.event.title);
                    $('#eventDescription').text(info.event.extendedProps.description);
                    $('#eventStart').text(new Date(info.event.start).toLocaleString());
                    $('#eventEnd').text(new Date(info.event.end).toLocaleString());
                    $('#eventModal').modal('show');
                }
            });

            calendar.render();
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/js/bootstrap.min.js"></script>

<?php include 'includes/footer.html'; ?>

</body>
</html>
