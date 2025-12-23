<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/registro.css">
    <title>Página Inicial</title>
</head>
<body>
    <div class="contenedor">
        <header>
            Rectángulo para logos
        </header>
        <h1>Registra tu Cuenta</h1>
        <section>
            <span class="instruccion">Ingrese sus Datos</span>
            <form action="proceso_datos/verificacion_registro.php" method="post">
                <div class="caja">
                    <div>
                        <label for="nombre">Nombre:</label>
                        <input type="text" name="nombre" id="nombre" placeholder="">
                    </div>
                    <div>
                        <label for="apellido">Apellido:</label>
                        <input type="text" name="apellido" id="apellido" placeholder="">
                    </div>
                    <div>
                        <label for="rut">Rut:</label>
                        <input type="text" name="rut" id="rut" placeholder="Ej: 20358047-9">
                    </div>
                    <div>
                        <label for="email">Email:</label>
                        <input type="text" name="email" id="email" placeholder="Ej: juanito1999@gmail.com">
                    </div>
                </div>
                <input class="boton" type="submit" value="Registrarse">
            </form>
        </section>
        <footer>
            Footer
            <br>
        </footer>
    </div>

    <!-- Carga del validador de RUT -->
    <script src="js/valida_rut.js"></script>

    <!-- Validación del formulario -->
    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
        var nombre = document.getElementById("nombre").value.trim();
        var apellido = document.getElementById("apellido").value.trim();
        var rut = document.getElementById("rut").value.trim();
        var email = document.getElementById("email").value.trim();

        var nameRegex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+$/; // Allows letters, accents
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/; // Basic email pattern

        var isValid = true;

        if (!nameRegex.test(nombre)) {
            isValid = false;
            alert("El Nombre solo debe contener letras.");
        } else if (!nameRegex.test(apellido)) {
            isValid = false;
            alert("El Apellido solo debe contener letras.");
        } else if (!Fn.validaRut(rut)) {
            isValid = false;
            alert("El RUT ingresado no es válido.");
        } else if (!emailRegex.test(email)) {
            isValid = false;
            alert("Por favor, ingrese un correo electrónico válido.");
        }

        if (!isValid) {
            e.preventDefault(); // Stop form from submitting
        }
    });
</script>
</body>
</html>