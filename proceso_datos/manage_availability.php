<?php
session_start();

header('Content-Type: application/json'); // Indicamos que la respuesta será JSON

$empleado = $_SESSION['username'] ?? null;

// Seguridad: Solo permite el acceso si hay un empleado logueado
if ($empleado === null) {
    echo json_encode(["error" => "Acceso no autorizado o sesión expirada."]);
    exit();
}

require_once 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Error de conexión a la base de datos: " . $conn->connect_error]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Definimos todas las horas posibles para la gestión
$all_possible_hours = ["09:00:00", "10:00:00", "11:00:00", "12:00:00", "14:30:00", "15:00:00"];

// --- Acción: Obtener Disponibilidad para una Fecha ---
if ($action == 'getAvailability' && isset($_GET['date'])) {
    $selected_date = $_GET['date'];
    $response_data = [];

    // 1. Obtener la disponibilidad definida en la tabla `disponibilidad_horaria`
    $stmt_availability = $conn->prepare("SELECT hora, max_capacidad FROM disponibilidad_horaria WHERE fecha = ? ORDER BY hora ASC");
    if (!$stmt_availability) {
        echo json_encode(["error" => "Error al preparar la consulta de disponibilidad: " . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt_availability->bind_param("s", $selected_date);
    $stmt_availability->execute();
    $result_availability = $stmt_availability->get_result();
    $defined_slots = [];
    while ($row = $result_availability->fetch_assoc()) {
        $defined_slots[substr($row['hora'], 0, 5)] = (int)$row['max_capacidad']; // Guardamos HH:MM => capacidad
    }
    $stmt_availability->close();

    // 2. Contar las reservas existentes para la fecha
    $stmt_booked = $conn->prepare("SELECT hora_reserva FROM reserva WHERE fecha_reserva = ?");
    if (!$stmt_booked) {
        echo json_encode(["error" => "Error al preparar la consulta de reservas: " . $conn->error]);
        $conn->close();
        exit();
    }
    $stmt_booked->bind_param("s", $selected_date);
    $stmt_booked->execute();
    $result_booked = $stmt_booked->get_result();
    $booked_hours_count = [];
    while ($row = $result_booked->fetch_assoc()) {
        $hour_booked = substr($row['hora_reserva'], 0, 5); // HH:MM
        $booked_hours_count[$hour_booked] = ($booked_hours_count[$hour_booked] ?? 0) + 1;
    }
    $stmt_booked->close();

    // 3. Combinar datos para la respuesta
    foreach ($all_possible_hours as $full_hour) {
        $hour_display = substr($full_hour, 0, 5); // HH:MM

        $is_defined = isset($defined_slots[$hour_display]);
        $max_capacity = $is_defined ? $defined_slots[$hour_display] : 0; // Si no está definida, capacidad 0
        $booked_count = $booked_hours_count[$hour_display] ?? 0;
        
        $response_data[] = [
            'hour' => $hour_display,
            'is_available_for_management' => $is_defined, // Indica si el administrador la marcó como disponible
            'max_capacity' => $max_capacity,
            'booked_count' => $booked_count,
            'can_book' => ($is_defined && $booked_count < $max_capacity) // Indica si un usuario final puede reservar
        ];
    }

    echo json_encode(["success" => true, "availability" => $response_data]);

} 
// --- Acción: Actualizar Disponibilidad para una Fecha ---
elseif ($action == 'updateAvailability' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $date = $input['date'] ?? null;
    $hours_to_set = $input['hours'] ?? []; // Array de objetos {hour: "HH:MM", max_capacity: N}

    if (!$date) {
        echo json_encode(["error" => "Fecha no proporcionada."]);
        $conn->close();
        exit();
    }

    // Iniciar una transacción para asegurar que todas las operaciones son atómicas
    $conn->begin_transaction();

    try {
        // 1. Eliminar la disponibilidad existente para esta fecha
        $stmt_delete = $conn->prepare("DELETE FROM disponibilidad_horaria WHERE fecha = ?");
        if (!$stmt_delete) {
            throw new Exception("Error al preparar la sentencia DELETE: " . $conn->error);
        }
        $stmt_delete->bind_param("s", $date);
        $stmt_delete->execute();
        $stmt_delete->close();

        // 2. Insertar las nuevas horas disponibles con su capacidad
        if (!empty($hours_to_set)) {
            $stmt_insert = $conn->prepare("INSERT INTO disponibilidad_horaria (fecha, hora, max_capacidad) VALUES (?, ?, ?)");
            if (!$stmt_insert) {
                throw new Exception("Error al preparar la sentencia INSERT: " . $conn->error);
            }
            foreach ($hours_to_set as $slot) {
                $hour_full_format = $slot['hour'] . ":00"; // Convertir HH:MM a HH:MM:SS
                $max_cap = (int)$slot['max_capacity'];
                $stmt_insert->bind_param("ssi", $date, $hour_full_format, $max_cap);
                $stmt_insert->execute();
            }
            $stmt_insert->close();
        }

        $conn->commit(); // Confirmar la transacción
        echo json_encode(["success" => true, "message" => "Disponibilidad actualizada correctamente."]);

    } catch (Exception $e) {
        $conn->rollback(); // Revertir la transacción si hay un error
        error_log("Error al actualizar la disponibilidad: " . $e->getMessage()); // Registrar el error
        echo json_encode(["error" => "Error al actualizar la disponibilidad: " . $e->getMessage()]);
    }

} else {
    echo json_encode(["error" => "Acción inválida."]);
}

$conn->close();
?>