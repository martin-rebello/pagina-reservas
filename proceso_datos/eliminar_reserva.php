<?php
session_start();

$empleado = $_SESSION['username'] ?? null;
$reservation_id = $_GET['id'] ?? null;

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

$stmt = $conn->prepare("DELETE FROM reserva WHERE id = ?");

if ($stmt) {
    $stmt->bind_param("s", $reservation_id);
    
    if ($stmt->execute()) {
        // Check if any rows were affected (deleted)
        if ($stmt->affected_rows > 0) {
            // Deletion successful, redirect the user
            $stmt->close();
            $conn->close();
            header("Location: ../intranet.php"); // Redirect to a success page or back to form
            exit();
        } else {
            // No reservation found for this user_id or no rows were deleted
            echo "No reservation found for this user or deletion failed.";
        }
    } else {
        // Handle execution error
        echo "Error executing statement: " . $stmt->error;
    }
    $stmt->close();
} else {
    // Handle prepare statement error
    echo "Error preparing statement: " . $conn->error;
}

?>