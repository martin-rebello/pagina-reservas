<?php
session_start();

$empleado = $_SESSION['username'] ?? null;
// IMPORTANT: You should get the reservation_id from $_POST if it's coming from a form submission,
// or from $_GET if it's in the URL, not from $_SESSION for an update operation like this.
// Assuming it's passed from the form or URL:
$reservation_id = $_POST['reservation_id'] ?? $_GET['id'] ?? null; // Prioritize POST, then GET

// The form you provided in the previous turn sends reservation_id via POST
// So it should ideally be:
// $reservation_id = $_POST['reservation_id'] ?? null;


// The following variables are likely not needed for an update operation that only modifies
// reservation details. They seem to be user details, which you marked as 'readonly' in the form.
// If you *do* intend to update user details as well, you'd need a separate UPDATE query for the 'usuario' table.
// $rut = $_POST["rut"] ?? "";
// $email = $_POST["email"] ?? "";
// $nombre = $_POST["nombre"] ?? "";
// $apellido = $_POST["apellido"] ?? "";

$tramite = $_POST['tipo_tramite'] ?? ""; 
$fecha = $_POST['fecha_reserva'] ?? ""; 
$hora = $_POST['hora_reserva'] ?? ""; 

if ($empleado === null) {
    header("Location: ../login.php"); // Redirect to your login page if not logged in
    exit("Acceso no autorizado o sesión expirada.");
}

require_once 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Corrected SQL UPDATE statement: Only one SET keyword
// Also, cast $reservation_id to int for safer use in WHERE clause
$reservation_id = (int)$reservation_id;

// Basic validation: ensure essential fields are not empty and ID is valid
if ($reservation_id > 0 && !empty($tramite) && !empty($fecha) && !empty($hora)) {

    $stmt = $conn->prepare("UPDATE reserva SET tipo_tramite = ?, fecha_reserva = ?, hora_reserva = ? WHERE id = ?");
    
    if ($stmt) {
        // 's' for string (tramite, fecha, hora), 'i' for integer (reservation_id)
        $stmt->bind_param("sssi", $tramite, $fecha, $hora, $reservation_id); 
            
        if ($stmt->execute()) {
            // Check if any rows were affected (updated)
            if ($stmt->affected_rows > 0) {
                // Update successful, redirect the user
                $stmt->close();
                $conn->close();
                header("Location: ../intranet.php?status=success&message=Reserva actualizada correctamente."); // Redirect with success message
                exit();
            } else {
                // No rows affected, possibly no changes made or ID not found
                $stmt->close();
                $conn->close();
                header("Location: ../intranet.php?status=info&message=No se realizaron cambios en la reserva o la reserva no existe."); // Redirect with info message
                exit();
            }
        } else {
            // Handle execution error
            error_log("Error executing statement: " . $stmt->error); // Log the actual error
            $stmt->close();
            $conn->close();
            header("Location: ../intranet.php?status=error&message=Error al actualizar la reserva: " . urlencode($stmt->error)); // Redirect with error message
            exit();
        }
    } else {
        // Handle prepare statement error
        error_log("Error preparing statement: " . $conn->error); // Log the actual error
        $conn->close();
        header("Location: ../intranet.php?status=error&message=Error interno del servidor al preparar la actualización."); // Redirect with error message
        exit();
    }
} else {
    // Invalid input or missing reservation ID
    $conn->close();
    header("Location: ../intranet.php?status=error&message=Datos incompletos o ID de reserva inválido para la actualización.");
    exit();
}

$conn->close(); // Close connection if execution reaches here unexpectedly

?>