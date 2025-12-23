<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index.css">
    <title>Página Inicial</title>
</head>
<body>
    <div class="contenedor">
        <header>
            Rectángulo para logos
        </header>
        <br>
        <h1>Reservar Hora</h1>
        <div class="pasos">
            <div class="etapa">
                <span class="circulo1">1</span>
                <span class="texto-paso">Ingrese Rut</span>
            </div>
            <div class="etapa">
                <span class="circulo2">2</span>
                <span class="texto-paso">Seleccione Hora</span>
            </div>
        </div>
        <section>
            <span class="instruccion">Ingrese rut</span>
            <form action="proceso_datos/verificacion_index.php" method="post">
                <label>
                    <input type="text" name="rut" id="rut" placeholder="Ingrese su Rut">
                </label>
                Ejemplo: 20358047-9 
                <br>
                <br>
                <label>
                    <input type="text" name="email" id="email" placeholder="Ingrese su Email">
                </label>
                Ejemplo: juanito99@gmail.com
                <br>
                <br>
                <input class="boton" type="submit" value="Ingresar">
                <br>
                <a href="registro.php" style="color: grey;">¿ Sin cuenta ? Registrate aqui</a>
            </form>
        </section>
        <footer>
            Footer
            <br>
            <a href="login.php">Empleados</a>
        </footer>
    </div>

    <!-- Carga del validador de RUT -->
    <script src="js/valida_rut.js"></script>

    <!-- Validación del formulario -->
    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            var rut = document.getElementById("rut").value.trim();

            if (!Fn.validaRut(rut)) {
                e.preventDefault(); // Detiene el envío del formulario
                alert("El RUT ingresado no es válido. Por favor, ingréselo correctamente.");
            }
        });
    </script>
</body>
</html>