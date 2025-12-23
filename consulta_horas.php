<?php

session_start();

$rut_from_session = $_SESSION['user_rut'] ?? null;

require_once 'proceso_datos/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$current_reservation = null;

if ($rut_from_session) {

    $stmt = $conn->prepare("SELECT tipo_tramite, fecha_reserva, hora_reserva FROM reserva WHERE usuario_id = ? ");
    if ($stmt) {
        $stmt->bind_param("s", $rut_from_session);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $current_reservation = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        // Handle prepare statement error
        echo "Error preparing statement: " . $conn->error;
    }
}

$conn->close();

?>

<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reserva Actual</title>
    <link rel='stylesheet' href='css/estilo2.css'>
</head>
<body>
    <header class='site-header'>
        <img src='img/logo.svg' alt='Logo Municipalidad' height='50'>
        <h1>Municipalidad de Ejemplo</h1>
    </header>
    <main class='container'>
        <?php if ($current_reservation): ?>
            <h2>Para reservar otra hora debe cancelar su reserva actual.</h2>
            <br>
            <br>
            <p>
                TRAMITE: <strong><?php echo htmlspecialchars($current_reservation['tipo_tramite']); ?></strong><br>
                FECHA: <strong><?php echo htmlspecialchars($current_reservation['fecha_reserva']); ?></strong><br>
                HORA: <strong><?php echo htmlspecialchars($current_reservation['hora_reserva']); ?></strong><br>
            </p>
        <?php else: ?>
            <p>No tiene ninguna reserva actual.</p>
        <?php endif; ?>
        <form action="proceso_datos/cancelar_hora.php" method="post">
            <input class="boton" type="submit" value="Cancelar">
        </form>
        <br>
        <br>
        <p><a href='index.php'>Volver al formulario</a></p>
    </main>
    <footer class='site-footer'>
        <p>© 2025 Municipalidad de Ejemplo. Todos los derechos reservados.</p>
        <p>Contacto: contacto@municipalidad.cl</p>
    </footer>
</body>
</html>