<?php

// Database connection using PDO for Azure SQL
try {
    $conn = new PDO("sqlsrv:server=tcp:calendarphp.database.windows.net,1433;Database=calendar", "Oleksandr", "{Abkillio2007}");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error connecting to SQL Server.";
    die(print_r($e));
}
