<?php
// Establece la zona horaria para asegurar que las fechas y horas se manejen correctamente.
// Utiliza una zona horaria adecuada para Chile (ej. America/Santiago).
session_start();
date_default_timezone_set('America/Santiago'); 

// Incluye las clases de PHPMailer. Asegúrate de que las rutas sean correctas.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

require_once 'db_config.php';

// 1. Verificar si el formulario ha sido enviado usando el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Recolectar y sanear los datos del formulario
    $tramite = isset($_POST['tramite']) ? htmlspecialchars(trim($_POST['tramite'])) : '';
    $fecha = isset($_POST['fecha']) ? htmlspecialchars(trim($_POST['fecha'])) : '';
    $hora = isset($_POST['hora']) ? htmlspecialchars(trim($_POST['hora'])) : '';

    // Retrieve the RUT and user email from the session (assuming you store email in session upon login)
    $rut = $_SESSION['user_rut'] ?? null;
    $user_email = $_SESSION['user_email'] ?? ''; // **IMPORTANT**: Replace with actual user email from session

    // 3. Validar los datos
    $errores = []; // Array para almacenar los mensajes de error

    if (empty($tramite) || $tramite == "Seleccione una opción") {
        $errores[] = "Debe seleccionar un tipo de trámite.";
    }

    if (empty($fecha)) {
        $errores[] = "Debe seleccionar una fecha.";
    } else {
        // Valida que la fecha seleccionada no sea en el pasado.
        $fechaSeleccionada = new DateTime($fecha);
        $hoy = new DateTime();
        $hoy->setTime(0, 0, 0); // Normaliza la hora de "hoy" para comparar solo la fecha.

        if ($fechaSeleccionada < $hoy) {
            $errores[] = "La fecha seleccionada no puede ser pasada.";
        }
    }

    if (empty($hora)) {
        $errores[] = "Debe seleccionar una hora.";
    }

    // 4. Procesar los datos o mostrar errores
    if (empty($errores)) {
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

        if ($conn->connect_error) {
            $errores[] = "Error de conexión a la base de datos: " . $conn->connect_error;
        } else {
            $conn->set_charset("utf8");

            // Prepare and execute the INSERT statement for reservation
            $stmt_insert = $conn->prepare("INSERT INTO reserva (tipo_tramite, fecha_reserva, hora_reserva, usuario_id) VALUES (?, ?, ?, ?);");
            $stmt_insert->bind_param("ssss", $tramite, $fecha, $hora, $rut);

            if ($stmt_insert->execute()) {
                // Reservation successfully saved. Now, fetch user's name and email for the email.
                // Close the INSERT statement immediately after its successful execution.
                $stmt_insert->close(); 
                
                // Prepare a new statement to fetch user name and potentially email from DB
                $stmt_name_email = $conn->prepare("SELECT nombre, email FROM usuario WHERE rut = ?");
                $stmt_name_email->bind_param("s", $rut);
                $stmt_name_email->execute();
                $result_name_email = $stmt_name_email->get_result();

                if ($result_name_email->num_rows > 0) {
                    $row = $result_name_email->fetch_assoc();
                    $user_name = $row['nombre'];
                    // Prioritize DB email if found, otherwise use session email
                    $user_email = $row['email'] ?? $user_email; 
                }
                $stmt_name_email->close(); // Close the statement used for fetching name/email

                // Attempt to send the confirmation email
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'reservas.transito2025@gmail.com';
                    $mail->Password   = 'qopy ycpk shrc odoj'; // Use your generated App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('no-reply@municipalidad.cl', 'Municipalidad de Ejemplo');
                    $mail->addAddress($user_email, $user_name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Confirmación de su Reserva en la Municipalidad';
                    $mail->Body    = "
                        <html>
                        <head>
                            <title>Confirmacion de Reserva</title>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { width: 80%; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
                                h2 { color: #0056b3; }
                                .details p { margin: 5px 0; }
                                .footer { margin-top: 20px; font-size: 0.9em; color: #777; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <h2>¡Reserva Confirmada en la Municipalidad de Ejemplo!</h2>
                                <p>Estimado/a <strong>" . $user_name . "</strong>,</p>
                                <p>Le confirmamos que su reserva ha sido agendada exitosamente con los siguientes detalles:</p>
                                <div class='details'>
                                    <p><strong>Tipo de Trámite:</strong> " . $tramite . "</p>
                                    <p><strong>Fecha de la Reserva:</strong> " . date('d/m/Y', strtotime($fecha)) . "</p>
                                    <p><strong>Hora de la Reserva:</strong> " . date('H:i', strtotime($hora)) . "</p>
                                    <p><strong>Número de Identificación (RUT):</strong> " . $rut . "</p>
                                </div>
                                <p>Por favor, sea puntual para su cita. Si necesita cancelar o reagendar, contáctenos con anticipación.</p>
                                <div class='footer'>
                                    <p>Gracias por usar nuestro sistema de reservas.</p>
                                    <p>Municipalidad de Ejemplo</p>
                                    <p>contacto@municipalidad.cl</p>
                                </div>
                            </div>
                        </body>
                        </html>
                    ";
                    $mail->AltBody = "Su reserva ha sido confirmada.\nTipo de Trámite: " . $tramite . "\nFecha: " . date('d/m/Y', strtotime($fecha)) . "\nHora: " . date('H:i', strtotime($hora)) . "\nNúmero de Identificación (RUT): " . $rut . "\n\nGracias por usar nuestro sistema de reservas.";

                    $mail->send();
                    $conn->close(); // Close the database connection here after all operations are done
                    header("Location: ../reserva_exitosa.php");
                    exit();
                } catch (Exception $e) {
                    $errores[] = "La reserva se guardó, pero no se pudo enviar el correo de confirmación. Error de PHPMailer: {$mail->ErrorInfo}";
                    // If email fails, but reservation succeeded, you might still redirect or show a specific error
                    // For now, it will fall through to display common errors
                }
            } else {
                $errores[] = "Error al guardar la reserva: " . $stmt_insert->error;
            }

            // Ensure the INSERT statement is closed even if execution fails
            // It's good practice to close a statement after trying to execute it.
            // Check if it's still open before closing.
            if (isset($stmt_insert) && $stmt_insert !== null) {
                 // Check if it's not already closed. This is defensive, as execute() might not close it on failure.
                 // However, calling close() multiple times is the error, so we remove the redundant one from the end.
                 // This block is only needed if $stmt_insert could remain open on error.
                 // Since we close it on success, and we're about to close the $conn,
                 // the explicit close here can be redundant if $conn->close() handles it.
            }
        }
        // Close the main database connection here if it was opened
        if (isset($conn) && $conn !== null) {
            $conn->close();
        }
    } 
    
    // Si hay errores (ya sea de validación o de base de datos), mostrarlos
    if (!empty($errores)) {
        echo "<!DOCTYPE html>
              <html lang='es'>
              <head>
                  <meta charset='UTF-8'>
                  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                  <title>Error en la Reserva</title>
                  <link rel='stylesheet' href='css/estilo2.css'>
              </head>
              <body>
                  <header class='site-header'>
                      <img src='img/logo.svg' alt='Logo Municipalidad' height='50'>
                      <h1>Municipalidad de Ejemplo</h1>
                  </header>
                  <main class='container'>
                      <h2>Error en la Reserva</h2>
                      <p>Ha ocurrido un problema con su solicitud:</p>";
        echo "<ul>";
        foreach ($errores as $error) {
            echo "<li>" . $error . "</li>";
        }
        echo "</ul>";
        echo "<p><a href='index.php'>Volver al formulario</a></p>
                  </main>
                  <footer class='site-footer'>
                      <p>© 2025 Municipalidad de Ejemplo. Todos los derechos reservados.</p>
                      <p>Contacto: contacto@municipalidad.cl</p>
                  </footer>
              </body>
              </html>";
    }

} else {
    // Si alguien intenta acceder a 'procesar_reserva.php' directamente sin enviar el formulario POST,
    // lo redirigimos de vuelta al formulario principal.
    header("Location: index.php");
    exit(); // Termina el script después de la redirección.
}
?>