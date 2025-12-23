<?php

$rut = $_POST["rut"] ?? "";
$email = $_POST["email"] ?? "";
$nombre = $_POST["nombre"] ?? "";
$apellido = $_POST["apellido"] ?? "";


function validarRutChileno($rutCompleto) {
    $rutSinFormato = preg_replace('/[^0-9kK]/', '', $rutCompleto);
    $numero = substr($rutSinFormato, 0, -1);
    $dv = strtoupper(substr($rutSinFormato, -1));

    $i = 2;
    $suma = 0;
    foreach (array_reverse(str_split($numero)) as $v) {
        if ($i == 8) {
            $i = 2;
        }
        $suma += $v * $i;
        $i++;
    }
    $dvEsperado = 11 - ($suma % 11);
    if ($dvEsperado == 11) {
        $dvEsperado = '0';
    } elseif ($dvEsperado == 10) {
        $dvEsperado = 'K';
    } else {
        $dvEsperado = (string) $dvEsperado;
    }
    return $dvEsperado === $dv;
}

if (!validarRutChileno($rut)) {
    die("El RUT ingresado no es válido.");
}


// --- Email Sanitization and Validation ---
$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico ingresado no es válido.");
}


$nombre = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-\']/', '', $nombre);
$apellido = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s\-\']/', '', $apellido);

$nombre = trim($nombre);
$apellido = trim($apellido);


require_once 'db_config.php';

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos.");
}

$check_stmt = $conexion->prepare("SELECT COUNT(*) FROM `usuario` WHERE `rut` = ?");
$check_stmt->bind_param("s", $rut);
$check_stmt->execute();
$check_stmt->bind_result($count);
$check_stmt->fetch();
$check_stmt->close();

if ($count > 0) {
    die("Error: El RUT ingresado ya existe en nuestra base de datos.");
}

$insert_stmt = $conexion->prepare("INSERT INTO `usuario` (`rut`, `email`, `nombre`, `apellido`) VALUES (?, ?, ?, ?);");
$insert_stmt->bind_param("ssss", $rut, $email, $nombre, $apellido);

if ($insert_stmt->execute()) {
    echo "Registro exitoso";
    header("Location: ../index.php");
    exit();
} else {
    echo "Error al registrar: " . $insert_stmt->error;
}

$insert_stmt->close();
$conexion->close();

?>