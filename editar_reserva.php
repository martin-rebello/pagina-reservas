<?php
session_start();

$empleado = $_SESSION['username'] ?? null;
$reservation_id = $_GET['id'] ?? null;
$_SESSION['id'] = $reservation_id; // Store the id in a session variable

if ($empleado === null) {
    header("Location: login.php"); // Redirect to your login page if not logged in
    exit("Acceso no autorizado o sesión expirada.");
}

require_once 'proceso_datos/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$reservation_id = (int)($_GET['id'] ?? 0); // Get ID from URL, cast to int, default to 0

$reservation_data = null;

if ($reservation_id > 0) {
    // Prepare and execute a SQL query to fetch the reservation details
    $stmt = $conn->prepare("SELECT r.id, r.fecha_reserva, r.hora_reserva, r.tipo_tramite, u.rut AS usuario_rut, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
                            FROM reserva r
                            JOIN usuario u ON r.usuario_id = u.rut
                            WHERE r.id = ?");

    if ($stmt) {
        $stmt->bind_param("i", $reservation_id); // 'i' for integer
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $reservation_data = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        error_log("Error preparing select statement: " . $conn->error);
    }
}

$conn->close();

// If no reservation data is found, redirect back or show an error
if ($reservation_data === null) {
    header("Location: intranet.php"); // Redirect back to the main management page
    exit("Reserva no encontrada.");
}

// Extract data for easier use in HTML
$id = htmlspecialchars($reservation_data['id']);
$fecha_reserva = htmlspecialchars($reservation_data['fecha_reserva']);
$hora_reserva = htmlspecialchars($reservation_data['hora_reserva']);
$tipo_tramite = htmlspecialchars($reservation_data['tipo_tramite']);
$usuario_rut = htmlspecialchars($reservation_data['usuario_rut']);
$usuario_nombre = htmlspecialchars($reservation_data['usuario_nombre']);
$usuario_apellido = htmlspecialchars($reservation_data['usuario_apellido']);
$usuario_email = htmlspecialchars($reservation_data['usuario_email']);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Reserva</title>
    <link rel="stylesheet" href="css/pagina3.css">
</head>
<body>
    <header>
        <div class="logo">
            <img src="C:\Users\spatr\Desktop\pagina3_intranet\img_intranet\logo_color.svg" alt="Logo Dirección de Tránsito">
            <h1>Editar Reserva</h1>
        </div>
        <nav>
            <ul>
                <li><a href="intranet.php#gestionar_reservas">Volver a Gestión de Reservas</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="form-container">
            <h2>Editar Reserva #<?php echo $id; ?></h2>
            <form action="proceso_datos/procesar_edicion.php" method="POST">
                <input type="hidden" name="reservation_id" value="<?php echo $id; ?>">

                <fieldset>
                    <legend>Datos del Usuario</legend>
                    <div class="form-group">
                        <label for="usuario_rut">RUT Usuario:</label>
                        <input type="text" id="rut" name="usuario_rut" value="<?php echo $usuario_rut; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="usuario_nombre">Nombre Usuario:</label>
                        <input type="text" id="nombre" name="usuario_nombre" value="<?php echo $usuario_nombre; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="usuario_apellido">Apellido Usuario:</label>
                        <input type="text" id="apellido" name="usuario_apellido" value="<?php echo $usuario_apellido; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="usuario_email">Email Usuario:</label>
                        <input type="email" id="email" name="usuario_email" value="<?php echo $usuario_email; ?>" readonly>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Datos de la Reserva</legend>
                    <div class="form-group">
                        <label for="fecha_reserva">Fecha de Reserva:</label>
                        <input type="date" id="fecha_reserva" name="fecha_reserva" value="<?php echo $fecha_reserva; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_reserva">Hora de Reserva:</label>
                        <input type="time" id="hora_reserva" name="hora_reserva" value="<?php echo $hora_reserva; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tipo_tramite">Tipo de Trámite:</label>
                        <select id="tipo_tramite" name="tipo_tramite" required>
                            <option value="">Seleccione una opción</option>
                            <option value="PRIMERA VEZ LICENCIA B, C, D, E" <?php if ($tipo_tramite == 'PRIMERA VEZ LICENCIA B, C, D, E') echo 'selected'; ?>>PRIMERA VEZ LICENCIA B, C, D, E</option>
                            <option value="PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5" <?php if ($tipo_tramite == 'PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5') echo 'selected'; ?>>PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5</option>
                            <option value="RENOVACIONES (TODAS LAS CLASES)" <?php if ($tipo_tramite == 'RENOVACIONES (TODAS LAS CLASES)') echo 'selected'; ?>>RENOVACIONES (TODAS LAS CLASES)</option>
                        </select>
                    </div>
                </fieldset>

                <div class="form-actions">
                    <button type="button" onclick="history.back()">Cancelar</button>
                    <button type="submit">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <img src="C:\Users\spatr\Desktop\pagina3_intranet\img_intranet\logo_white_bottom.svg" alt="Logo Pie de Página" />
        <p> Direccion del transito - Todos los derechos reservados</p>
    </footer>
</body>
</html>