<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <header></header>
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        <form action="proceso_datos/verificar_login.php" method="POST"> 
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-button">Entrar</button>
        </form>
        <style>a{
            color: white;
            text-decoration: none;
            transition: color 0.3s ease; 
            
        }</style>
    </div>
    <footer><a href="index.php">Inicio</a></footer>
</body>
</html>