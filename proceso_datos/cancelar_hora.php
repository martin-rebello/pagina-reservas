<?php

session_start();

$rut_from_session = $_SESSION['user_rut'] ?? null;

require_once 'db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

if ($rut_from_session) {

    $stmt = $conn->prepare("DELETE FROM reserva WHERE usuario_id = ?");
    
    if ($stmt) {
        $stmt->bind_param("s", $rut_from_session);
        
        if ($stmt->execute()) {
            // Check if any rows were affected (deleted)
            if ($stmt->affected_rows > 0) {
                // Deletion successful, redirect the user
                $stmt->close();
                $conn->close();
                header("Location: ../index.php"); // Redirect to a success page or back to form
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
} else {
    // Handle case where rut_from_session is null (user not logged in or session expired)
    echo "User not logged in.";
}

$conn->close(); // Ensure connection is closed if not exited earlier

?>