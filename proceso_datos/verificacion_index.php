<?php

session_start();

$rut = $_POST["rut"] ?? "";
$email = $_POST["email"] ?? "";

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

if (empty($rut)) {
    die("Por favor, ingrese un RUT válido.");
}

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico ingresado no es válido.");
}

require_once 'db_config.php';

$conexion = new mysqli($servername, $username, $password, $dbname);

if ($conexion->connect_error) {
    die("Error al conectar con la base de datos.");
}

// Usar sentencia preparada para evitar inyección SQL
$stmt = $conexion->prepare("SELECT * FROM usuario WHERE rut = ? and email = ?");
$stmt->bind_param("ss", $rut, $email);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    // Usuario encontrado, redirigir
    $stmt->close();
    $stmt =$conexion->prepare("SELECT * FROM reserva WHERE usuario_id = ?");
    $stmt->bind_param("s",$rut);
    $stmt->execute();
    $reserva = $stmt->get_result();
    if ($reserva->num_rows > 0) {
        $_SESSION['user_rut'] = $rut; // Store the rut in a session variable
        $stmt->close();
        $conexion->close();
        header("Location: ../consulta_horas.php");
        exit(); 
    }else{
        $_SESSION['user_rut'] = $rut; // Store the rut in a session variable
        $_SESSION['user_email'] = $email; // Store the rut in a session variable
        $stmt->close();
        $conexion->close();
        header("Location: ../pagina2.php");
        exit(); // Siempre usar exit() después de header()
    }
} else {
    echo "No se encontró ningún usuario con ese RUT.";
}

$stmt->close();
$conexion->close();
?>
