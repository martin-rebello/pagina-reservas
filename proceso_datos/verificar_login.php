<?php

session_start();

$user_empleado = $_POST["username"] ?? "";
$user_password = $_POST["password"] ?? "";

require_once 'db_config.php';

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos.");
}

// Usar sentencia preparada para evitar inyección SQL
$stmt = $conexion->prepare("SELECT * FROM empleado WHERE usuario = ? and contrasena = ? ");
$stmt->bind_param("ss", $user_empleado, $user_password);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    // Usuario encontrado, redirigir

    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $user_password;

    $stmt->close();
    $conexion->close();
    header("Location: ../intranet.php");
    exit(); // Siempre usar exit() después de header()
} else {
    echo "No se encontró usuario.";
}

$stmt->close();
$conexion->close();
?>
