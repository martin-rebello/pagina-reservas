<?php
// get_availability.php

header('Content-Type: application/json'); // Tell the browser to expect JSON

// Database Connection (REPLACE WITH YOUR ACTUAL CREDENTIALS)
require_once 'proceso_datos/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$response = [];

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'getAvailableDates') {
        // Fetch dates that are in the future and have available slots
        // This query assumes you want to show dates as long as there's *any* slot free.
        // A more complex query might check a 'dias_disponibles' table or aggregate from available hours.

        // For simplicity, let's just get dates from today onwards that are not fully booked
        // This requires a more dynamic approach checking total capacity vs. bookings.
        // For now, let's consider a simple case: just dates that *could* have slots.
        // In a real app, you'd fetch from a `dias_disponibles` table or analyze `citas_agendadas`.

        // Example: Get all dates with at least one slot available
        $current_date = date('Y-m-d');
        $sql_dates = "SELECT DISTINCT fecha_reserva FROM reserva WHERE fecha_reserva >= ? AND fecha_reserva NOT IN (
                        SELECT fecha_reserva FROM reserva
                        GROUP BY fecha_reserva, hora_reserva
                        HAVING COUNT(*) >= 1 -- Assuming 1 booking per slot for simplicity. Adjust for capacity.
                    ) ORDER BY fecha_reserva ASC LIMIT 30"; // Fetch next 30 days
        // A better approach would be to have a `dias_disponibles` table and query it.
        // Let's assume you have a list of all potential hours for any day
        $all_possible_hours = [
            "09:00:00", "10:00:00", "11:00:00", "12:00:00", "14:30:00", "15:00:00"
        ];
        $availableDates = [];

        // This part would be more efficient if you have a table of available slots
        // For demonstration, let's mock some available dates
        for ($i = 0; $i < 15; $i++) { // Show next 15 days
            $date = date('Y-m-d', strtotime("+$i days"));
            $availableDates[] = $date;
        }

        $response['availableDates'] = $availableDates;

    } elseif ($_GET['action'] == 'getAvailableHours' && isset($_GET['date'])) {
        $selected_date = $_GET['date'];

        // Define all possible hours for any given day
        $all_possible_hours = ["09:00", "10:00", "11:00", "12:00", "14:30", "15:00"];
        $available_hours = [];

        // Query booked hours for the selected date
        $stmt = $conn->prepare("SELECT hora_reserva FROM reserva WHERE fecha_reserva = ?");
        $stmt->bind_param("s", $selected_date);
        $stmt->execute();
        $result = $stmt->get_result();

        $booked_hours = [];
        while ($row = $result->fetch_assoc()) {
            $booked_hours[] = substr($row['hora_reserva'], 0, 5); // Format to HH:MM
        }
        $stmt->close();

        // Determine which hours are available
        foreach ($all_possible_hours as $hour) {
            if (!in_array($hour, $booked_hours)) {
                $available_hours[] = $hour;
            }
        }
        $response['availableHours'] = $available_hours;

    } else {
        $response["error"] = "Invalid action or missing parameters.";
    }
} else {
    $response["error"] = "No action specified.";
}

echo json_encode($response);
$conn->close();
?>