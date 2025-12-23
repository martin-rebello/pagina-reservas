<?php
session_start();

// Habilitar la visualización de errores para depuración (QUITAR EN PRODUCCIÓN)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar la sesión del empleado
$empleado = $_SESSION['username'] ?? null;

if ($empleado === null) {
    header("Location: login.php");
    exit("Acceso no autorizado o sesión expirada.");
}

// Configuración de la Base de Datos
// Utiliza las credenciales de tu archivo db_config.php si lo tienes centralizado
// Si no, asegúrate de que estas sean correctas para tu entorno local (XAMPP/WAMP/MAMP)
require_once 'proceso_datos/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión a la base de datos
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

$report_data = [];
$filter_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filter_fecha_fin = $_GET['fecha_fin'] ?? '';
$filter_tipo_tramite = $_GET['tipo_tramite'] ?? '';

// Construcción de la consulta SQL
$sql = "SELECT r.id, r.fecha_reserva, r.hora_reserva, r.tipo_tramite,
               u.rut AS usuario_rut, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
        FROM reserva r
        JOIN usuario u ON r.usuario_id = u.rut
        WHERE 1=1"; // Condición base para facilitar la adición de filtros

$params = [];
$types = "";

if (!empty($filter_fecha_inicio)) {
    $sql .= " AND r.fecha_reserva >= ?";
    $types .= "s";
    $params[] = $filter_fecha_inicio;
}
if (!empty($filter_fecha_fin)) {
    $sql .= " AND r.fecha_reserva <= ?";
    $types .= "s";
    $params[] = $filter_fecha_fin;
}
if (!empty($filter_tipo_tramite)) {
    $sql .= " AND r.tipo_tramite = ?";
    $types .= "s";
    $params[] = $filter_tipo_tramite;
}

$sql .= " ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        // Usar call_user_func_array para bind_param con un array dinámico
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $report_data[] = $row;
        }
    }
    $stmt->close();
} else {
    // Si la preparación de la consulta falla
    error_log("Error al preparar la consulta de reportes: " . $conn->error);
    echo "<p style='color: red;'>Error interno al generar el reporte. Por favor, intente de nuevo más tarde.</p>";
}

$conn->close();

// Lógica de exportación a CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_reservas.csv');
    $output = fopen('php://output', 'w');

    // Añadir encabezados CSV
    fputcsv($output, [
        'ID Reserva', 'Fecha', 'Hora', 'Tipo de Tramite',
        'RUT Usuario', 'Nombre Usuario', 'Apellido Usuario', 'Email Usuario'
    ]);

    // Añadir datos al CSV
    foreach ($report_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intranet - Generar Reportes</title>
    <link rel="stylesheet" href="css/pagina3.css">
    <style>
        /* Estilos adicionales para los filtros y botones de reporte */
        .report-filters {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 2em;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .report-filters .form-group {
            flex: 1;
            min-width: 180px; /* Adjust as needed */
        }
        .report-filters label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .report-filters input[type="date"],
        .report-filters select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .report-filters button {
            padding: 10px 15px;
            background-color: #4499bd;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }
        .report-filters button:hover {
            background-color: #3a7c9f;
        }
        .report-actions {
            margin-top: 1em;
            text-align: right;
            margin-bottom: 1em; /* Añadido para espacio debajo de los botones de acción */
        }
        .report-actions .export-button {
            background-color: #28a745; /* Green for export */
            text-decoration: none; /* Asegura que no tenga subrayado si es un <a> */
            color: white; /* Asegura el color del texto */
            padding: 10px 15px; /* Mismo padding que el botón de filtro */
            border-radius: 4px; /* Mismo border-radius */
            display: inline-block; /* Permite padding y margin */
        }
        .report-actions .export-button:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="img/logo.svg" alt="Logo Municipalidad">
            <h1>Municipalidad de Ejemplo</h1>
        </div>
        <nav>
            <ul>
                <li><a href="intranet.php">Gestionar Reservas</a></li>
                <li><a href="reportes.php">Generar Reportes</a></li> <li><a href="logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <section id="generar_reportes">
            <h2>Generar Reportes de Reservas</h2>

            <div class="report-filters">
                <form method="GET" action="reportes.php" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; width: 100%;">
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha Inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($filter_fecha_inicio); ?>">
                    </div>
                    <div class="form-group">
                        <label for="fecha_fin">Fecha Fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($filter_fecha_fin); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo_tramite">Tipo de Trámite:</label>
                        <select id="tipo_tramite" name="tipo_tramite">
                            <option value="">Todos</option>
                            <option value="PRIMERA VEZ LICENCIA B, C, D, E" <?php if ($filter_tipo_tramite == 'PRIMERA VEZ LICENCIA B, C, D, E') echo 'selected'; ?>>PRIMERA VEZ LICENCIA B, C, D, E</option>
                            <option value="PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5" <?php if ($filter_tipo_tramite == 'PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5') echo 'selected'; ?>>PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5</option>
                            <option value="RENOVACIONES (TODAS LAS CLASES)" <?php if ($filter_tipo_tramite == 'RENOVACIONES (TODAS LAS CLASES)') echo 'selected'; ?>>RENOVACIONES (TODAS LAS CLASES)</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex-grow: 1;">
                        <button type="submit">Aplicar Filtros</button>
                    </div>
                </form>
            </div>

            <h3>Resultados del Reporte</h3>
            <?php if (!empty($report_data)): ?>
                <div class="report-actions">
                    <a href="reportes.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="export-button button">Exportar a CSV</a>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID Reserva</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Trámite</th>
                            <th>RUT Usuario</th>
                            <th>Nombre Usuario</th>
                            <th>Apellido Usuario</th>
                            <th>Email Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data as $reserva): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reserva['id']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['fecha_reserva']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['hora_reserva']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['tipo_tramite']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_rut']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_apellido']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se encontraron reservas con los filtros aplicados.</p>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>© 2025 Municipalidad de Ejemplo. Todos los derechos reservados.</p>
        <p>Contacto: contacto@municipalidad.cl</p>
    </footer>
</body>
</html>