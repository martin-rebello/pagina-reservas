<?php
session_start();

// Habilitar la visualización de errores para depuración (QUITAR EN PRODUCCIÓN)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$empleado = $_SESSION['username'] ?? null;

if ($empleado === null) {
    header("Location: login.php"); // Redirect to your login page if not logged in
    exit("Acceso no autorizado o sesión expirada.");
}

require_once 'proceso_datos/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Lógica para 'Gestionar Reservas' (ya existente)
$reservations = [];
$sql_reservas = "SELECT r.id, r.fecha_reserva, r.hora_reserva, r.tipo_tramite, u.rut AS usuario_rut, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
                FROM reserva r
                JOIN usuario u ON r.usuario_id = u.rut
                ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC"; // Order by date and time, newest first

$result_reservas = $conn->query($sql_reservas);

if ($result_reservas && $result_reservas->num_rows > 0) {
    while ($row = $result_reservas->fetch_assoc()) {
        $reservations[] = $row;
    }
}


// --- Lógica para 'Generar Reportes' (MOVING FROM reportes.php) ---
$report_data = [];
$filter_fecha_inicio = $_GET['fecha_inicio'] ?? '';
$filter_fecha_fin = $_GET['fecha_fin'] ?? '';
$filter_tipo_tramite = $_GET['tipo_tramite'] ?? '';

// Check if the report section is active or if filters are applied
// This helps to only run the report query when needed
$is_report_section_active = (isset($_GET['section']) && $_GET['section'] == 'generar_reportes') || !empty($filter_fecha_inicio) || !empty($filter_fecha_fin) || !empty($filter_tipo_tramite) || (isset($_GET['export']) && $_GET['export'] == 'csv');

if ($is_report_section_active) {
    $sql_reporte = "SELECT r.id, r.fecha_reserva, r.hora_reserva, r.tipo_tramite,
                   u.rut AS usuario_rut, u.nombre AS usuario_nombre, u.apellido AS usuario_apellido, u.email AS usuario_email
            FROM reserva r
            JOIN usuario u ON r.usuario_id = u.rut
            WHERE 1=1"; // Condición base para facilitar la adición de filtros

    $params_reporte = [];
    $types_reporte = "";

    if (!empty($filter_fecha_inicio)) {
        $sql_reporte .= " AND r.fecha_reserva >= ?";
        $types_reporte .= "s";
        $params_reporte[] = $filter_fecha_inicio;
    }
    if (!empty($filter_fecha_fin)) {
        $sql_reporte .= " AND r.fecha_reserva <= ?";
        $types_reporte .= "s";
        $params_reporte[] = $filter_fecha_fin;
    }
    if (!empty($filter_tipo_tramite)) {
        $sql_reporte .= " AND r.tipo_tramite = ?";
        $types_reporte .= "s";
        $params_reporte[] = $filter_tipo_tramite;
    }

    $sql_reporte .= " ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";

    $stmt_reporte = $conn->prepare($sql_reporte);

    if ($stmt_reporte) {
        if (!empty($params_reporte)) {
            $stmt_reporte->bind_param($types_reporte, ...$params_reporte);
        }
        $stmt_reporte->execute();
        $result_reporte = $stmt_reporte->get_result();

        if ($result_reporte && $result_reporte->num_rows > 0) {
            while ($row = $result_reporte->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        $stmt_reporte->close();
    } else {
        error_log("Error al preparar la consulta de reportes: " . $conn->error);
        echo "<p style='color: red;'>Error interno al generar el reporte. Por favor, intente de nuevo más tarde.</p>";
    }
}


// Lógica de exportación a CSV (también dentro de intranet.php)
// Se ejecutará solo si el parámetro 'export=csv' está presente en la URL
if (isset($_GET['export']) && $_GET['export'] == 'csv' && $is_report_section_active) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reporte_reservas.csv');
    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'ID Reserva', 'Fecha', 'Hora', 'Tipo de Tramite',
        'RUT Usuario', 'Nombre Usuario', 'Apellido Usuario', 'Email Usuario'
    ]);

    foreach ($report_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit(); // Termina la ejecución para evitar que se envíe el HTML
}

$conn->close();

// Determine which section should be active initially
$active_section = $_GET['section'] ?? 'gestionar_reservas'; // Default to gestionar_reservas
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Intranet - Administración</title>
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
        /* Style to control section visibility based on PHP variable */
        .section-hidden {
            display: none;
        }
        .section-active {
            display: block;
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
                <li><a href="intranet.php?section=gestionar_reservas" class="<?php echo ($active_section == 'gestionar_reservas' ? 'active' : ''); ?>">Gestionar Reservas</a></li>
                <li><a href="intranet.php?section=generar_reportes" class="<?php echo ($active_section == 'generar_reportes' ? 'active' : ''); ?>">Generar Reportes</a></li>
                <li><a href="proceso_datos/logout.php">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <section id="gestionar_reservas" class="<?php echo ($active_section == 'gestionar_reservas' ? 'section-active' : 'section-hidden'); ?>">
            <h2>Gestionar Reservas</h2>
            <?php if (!empty($reservations)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Trámite</th>
                            <th>RUT Usuario</th>
                            <th>Nombre Usuario</th>
                            <th>Apellido Usuario</th>
                            <th>Email Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reserva): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reserva['id']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['fecha_reserva']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['hora_reserva']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['tipo_tramite']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_rut']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_apellido']); ?></td>
                                <td><?php echo htmlspecialchars($reserva['usuario_email']); ?></td>
                                <td>
                                    <a href="editar_reserva.php?id=<?php echo htmlspecialchars($reserva['id']); ?>">Editar</a> |
                                    <a href="proceso_datos/eliminar_reserva.php?id=<?php echo htmlspecialchars($reserva['id']); ?>" onclick="return confirm('¿Está seguro de eliminar esta reserva?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay reservas programadas.</p>
            <?php endif; ?>
        </section>

        <section id="generar_reportes" class="<?php echo ($active_section == 'generar_reportes' ? 'section-active' : 'section-hidden'); ?>">
            <h2>Generar Reportes de Reservas</h2>

            <div class="report-filters">
                <form method="GET" action="intranet.php" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; width: 100%;">
                    <input type="hidden" name="section" value="generar_reportes"> <div class="form-group">
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
                    <a href="intranet.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv', 'section' => 'generar_reportes'])); ?>" class="export-button button">Exportar a CSV</a>
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
                                <td><?php htmlspecialchars($reserva['hora_reserva']); ?></td>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Updated to use the 'section' query parameter instead of hash
            const navLinks = document.querySelectorAll('nav ul li a');
            const sections = document.querySelectorAll('main section');

            function showSection(id) {
                sections.forEach(section => {
                    section.classList.remove('section-active');
                    section.classList.add('section-hidden');
                });
                const targetSection = document.getElementById(id);
                if (targetSection) {
                    targetSection.classList.remove('section-hidden');
                    targetSection.classList.add('section-active');
                }
            }

            // Get active section from URL parameter or default to 'gestionar_reservas'
            const urlParams = new URLSearchParams(window.location.search);
            const initialSection = urlParams.get('section') || 'gestionar_reservas';
            showSection(initialSection);

            navLinks.forEach(link => {
                link.addEventListener('click', function(event) {
                    // Prevent default only if it's a section link, not logout
                    if (this.getAttribute('href').startsWith('intranet.php?section=')) {
                        event.preventDefault(); // Prevent default link behavior

                        const newSection = new URLSearchParams(this.search).get('section');
                        showSection(newSection);

                        // Update URL parameter without reloading
                        const newUrl = new URL(window.location);
                        newUrl.searchParams.set('section', newSection);
                        window.history.pushState({ path: newUrl.href }, '', newUrl.href);
                    }
                    // For logout.php, allow default behavior
                });
            });
        });
    </script>
</body>
</html>