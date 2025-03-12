<?php
require_once "Database.php";
require_once "Usuario.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $biografia = $_POST["biografia"];

    // Validación de la contraseña
    if (strlen($password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $mensaje = "La contraseña debe contener al menos una letra mayúscula.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $mensaje = "La contraseña debe contener al menos una letra minúscula.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $mensaje = "La contraseña debe contener al menos un número.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $mensaje = "La contraseña debe contener al menos un símbolo (como @, #, $, etc.).";
    } elseif (empty($mensaje)) {
        // Encriptar la contraseña si es válida
        $password = password_hash($password, PASSWORD_DEFAULT); // Encriptar la contraseña

        // Validación del correo electrónico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "El correo electrónico no es válido.";
        } elseif (!preg_match('/@gmail\.com$/', $email) && !preg_match('/@hotmail\.com$/', $email)) {
            $mensaje = "El correo debe ser de Gmail o Hotmail.";
        }

        // Si todo está bien, proceder con la inserción de datos
        if (empty($mensaje)) {
            // Manejo de la imagen de perfil
            $foto_perfil_url = "uploads/default.jpg"; // Imagen por defecto
            if (isset($_FILES["foto_perfil"]) && $_FILES["foto_perfil"]["error"] == 0) {
                $directorio_subida = "uploads/";
                $nombre_archivo = time() . "_" . basename($_FILES["foto_perfil"]["name"]);
                $ruta_archivo = $directorio_subida . $nombre_archivo;

                if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $ruta_archivo)) {
                    $foto_perfil_url = $ruta_archivo;
                }
            }

            // Crear instancia de la base de datos
            $database = new Database();
            $usuario = new Usuario($database);

            // Intentar registrar el usuario
            if ($usuario->registrarUsuario($nombre, $apellido, $email, $password, $biografia, $foto_perfil_url)) {
                $mensaje = "Registro exitoso. <a href='login.php'>Iniciar sesión</a>";
            } else {
                $mensaje = "Error en el registro.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Registro</h2>
    
    <?php if (!empty($mensaje)): ?>
        <p><?php echo $mensaje; ?></p>
    <?php endif; ?>

    <form action="registro.php" method="post" enctype="multipart/form-data">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" required>

        <label for="apellido">Apellido:</label>
        <input type="text" name="apellido" required>

        <label for="email">Correo Electrónico:</label>
        <input type="email" name="email" required>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>

        <label for="biografia">Biografía:</label>
        <textarea name="biografia" required></textarea>

        <label for="foto_perfil">Foto de Perfil:</label>
        <input type="file" name="foto_perfil" accept="image/*">

        <button type="submit">Registrarse</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
</body>
</html>

