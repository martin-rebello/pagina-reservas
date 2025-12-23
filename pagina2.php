<?php
// Make sure to start the session at the very beginning of this file
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Retrieve the RUT from the session
$rut_from_session = $_SESSION['user_rut'] ?? null;
$email_from_session = $_SESSION['user_email'] ?? null;

if ($rut_from_session === null) {
    header("Location: index.php"); // Redirect to your login page if not logged in
    exit("Acceso no autorizado o sesión expirada.");
}

require_once 'proceso_datos/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);

$current_reservation = null;

if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

$has_active_reservation = false;
$stmt = $conn->prepare("SELECT COUNT(*) FROM reserva WHERE usuario_id = ?"); 
if ($stmt) {
    $stmt->bind_param("s", $rut_from_session);
    $stmt->execute();
    $stmt->bind_result($reservation_count);
    $stmt->fetch();
    $stmt->close();

    if ($reservation_count > 0) {
        $has_active_reservation = true;
    }
} else {
    error_log("Error preparing reservation check statement: " . $conn->error);
}

$conn->close(); 

if ($has_active_reservation) {
    header("Location: consulta_horas.php"); 
    exit("Ya tienes una reserva activa.");
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Reserva de Hora - Paso 2</title>
<link rel="stylesheet" href="css/pagina2.css" />
    <style>
        .hora-btn:disabled {
background-color: #ccc;
cursor: not-allowed;
opacity: 0.6;
        }
    </style>
</head>
<body>

<header>
<img src="C:\Users\spatr\Desktop\pagina2_reservas\img\logo_color.svg" alt="Logo Encabezado" />
<h1>Reserva de Hora - Paso 2</h1>
<div style="width: 50px;"></div>
</header>

<div class="wrapper">
<h2>Seleccione Tipo de Trámite, Fecha y Hora</h2>

<form action="proceso_datos/procesar_reserva.php" method="POST" id="reservationForm">
        <div class="form-group">
<label for="tramite">Tipo de Trámite:</label>
<select id="tramite" name="tramite" required>
 <option value="">Seleccione una opcion</option>
 <option value="PRIMERA VEZ LICENCIA B, C, D, E">PRIMERA VEZ LICENCIA B, C, D, E</option>
 <option value="PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5">PRIMERA VEZ LICENCIA PROFESIONAL A1, A2, A3, A4, A5</option>
 <option value="RENOVACIONES (TODAS LAS CLASES)">RENOVACIONES (TODAS LAS CLASES)</option>
</select>
</div>

<div class="form-group">
<label for="fecha">Seleccione una fecha:</label>
<input type="date" id="fecha" name="fecha" required />
</div>

<div class="form-group">
<label>Horas disponibles:</label>
<div class="horas" id="horasDisponiblesContainer">
  <p>Seleccione una fecha para ver las horas disponibles.</p>
</div>
<input type="hidden" id="horaSeleccionadaInput" name="hora" required>
</div>

<div class="form-actions">
<button type="button" onclick="window.location.href='index.php'">Volver</button>
 <button type="submit">Reservar</button>
</div>
    </form>
</div>

<footer>
<img src="C:\Users\spatr\Desktop\pagina2_reservas\img\logo_white_bottom.svg" alt="Logo Pie de Página" /> 
<p> Direccion del transito - Todos los derechos reservados</p>
</footer>

<script>
    
    window.addEventListener('pageshow', function(event) {
    // Check if the page was loaded from the browser's back/forward cache
    if (event.persisted) {
            // If it was, force a full reload. This will trigger the PHP logic again.
            window.location.reload();
    }
    });



    let horaSeleccionadaInput = document.getElementById('horaSeleccionadaInput');
        const fechaInput = document.getElementById('fecha');
        const horasContainer = document.getElementById('horasDisponiblesContainer');

        // Function to fetch and display available hours for a given date
        async function loadAvailableHours(date) {
            if (!date) {
    horasContainer.innerHTML = '<p>Seleccione una fecha para ver las horas disponibles.</p>';
    horaSeleccionadaInput.value = ''; // Clear selected hour
    return;
            }

            try {
    // Unselect any previously selected hour
    document.querySelectorAll('.hora-btn.selected').forEach(btn => btn.classList.remove('selected'));
    horaSeleccionadaInput.value = ''; // Clear selected hour

    horasContainer.innerHTML = '<p>Cargando horas...</p>'; // Show loading message

    const response = await fetch(`get_availability.php?action=getAvailableHours&date=${date}`);
    const data = await response.json();

    if (data.error) {
        horasContainer.innerHTML = `<p>Error: ${data.error}</p>`;
        return;
    }

    const availableHours = data.availableHours;
    if (availableHours.length === 0) {
        horasContainer.innerHTML = '<p>No hay horas disponibles para esta fecha.</p>';
    } else {
        horasContainer.innerHTML = ''; // Clear previous buttons
        availableHours.forEach(hour => {
            const button = document.createElement('button');
            button.type = 'button'; // Important: prevent it from submitting the form
            button.classList.add('hora-btn');
            button.textContent = hour;
            button.addEventListener('click', () => {
    document.querySelectorAll('.hora-btn').forEach(b => b.classList.remove('selected'));
    button.classList.add('selected');
    horaSeleccionadaInput.value = hour; // Set the value of the hidden input
            });
            horasContainer.appendChild(button);
        });
    }
            } catch (error) {
    console.error('Error fetching available hours:', error);
    horasContainer.innerHTML = '<p>Error al cargar las horas disponibles.</p>';
            }
        }

        // Event listener for date input change
        fechaInput.addEventListener('change', (event) => {
            loadAvailableHours(event.target.value);
        });

        // Optional: Set min date to today and max date
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1; // Months start at 0!
        let dd = today.getDate();

        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;

        const formattedToday = yyyy + '-' + mm + '-' + dd;
        fechaInput.min = formattedToday;

        // You could also fetch available dates from the server to set a more precise range
        // or disable specific dates in a custom calendar.
        async function initializeAvailableDates() {
            try {
    const response = await fetch(`get_availability.php?action=getAvailableDates`);
    const data = await response.json();

    if (data.error) {
        console.error('Error fetching available dates:', data.error);
        return;
    }

    // This part is more complex for a native date picker.
    // If you want to disable specific dates, you'd need a custom calendar library.
    // For a native date picker, setting min/max is the most you can do easily.
    // For now, we'll just set the min date to today.
    // If `data.availableDates` comes back with specific future dates,
    // you might set `fechaInput.max` to the latest available date.
    if (data.availableDates && data.availableDates.length > 0) {
        const latestDate = data.availableDates[data.availableDates.length - 1];
        fechaInput.max = latestDate;
    }
            } catch (error) {
    console.error('Error initializing available dates:', error);
            }
        }

        initializeAvailableDates(); // Call this on page load

    // Listen for the form's submit event for final validation
    document.getElementById('reservationForm').addEventListener('submit', function(event) {
    const tramite = document.getElementById('tramite').value;
    const fecha = fechaInput.value;
    const hora = horaSeleccionadaInput.value;

    if (!tramite || !fecha || !hora) {
    alert("Debe seleccionar tipo de trámite, fecha y hora.");
    event.preventDefault();
    return;
    }
    });
</script>

</body>
</html>
