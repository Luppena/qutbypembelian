<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', 'qutby');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Kill any locked processes on sessions table
$result = $mysqli->query("SHOW PROCESSLIST");
$killed = 0;
while ($row = $result->fetch_assoc()) {
    if (isset($row['Info']) && strpos($row['Info'], 'sessions') !== false && $row['Time'] > 5) {
        $mysqli->query("KILL " . $row['Id']);
        $killed++;
    }
}
echo "Killed $killed locked processes\n";

// Truncate sessions table
$mysqli->query("TRUNCATE TABLE sessions");
echo "Sessions table truncated successfully\n";

$mysqli->close();
echo "Done!\n";
