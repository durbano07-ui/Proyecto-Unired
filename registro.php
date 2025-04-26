<?php
require_once "Database.php";
require_once "Usuario.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $biografia = $_POST["biografia"];

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
        $password = password_hash($password, PASSWORD_DEFAULT);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensaje = "El correo electrónico no es válido.";
        } elseif (!preg_match('/@gmail\.com$/', $email) && !preg_match('/@hotmail\.com$/', $email)) {
            $mensaje = "El correo debe ser de Gmail o Hotmail.";
        }

        if (empty($mensaje)) {
            $foto_perfil_url = "uploads/default.jpg";
            if (isset($_FILES["foto_perfil"]) && $_FILES["foto_perfil"]["error"] == 0) {
                $directorio_subida = "uploads/";
                $nombre_archivo = time() . "_" . basename($_FILES["foto_perfil"]["name"]);
                $ruta_archivo = $directorio_subida . $nombre_archivo;

                if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $ruta_archivo)) {
                    $foto_perfil_url = $ruta_archivo;
                }
            }

            $database = new Database();
            $usuario = new Usuario($database);

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
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('recursos/login.png') no-repeat center center fixed;
            background-color: black;
            background-size: contain;
            display: flex;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
            color: #fff;
            justify-content: center;
        }

        .container {
            width: 80%;
            max-width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .container h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        input::placeholder, textarea::placeholder {
            color: #ddd;
        }

        button {
            background-color: rgb(94, 211, 166);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
        }

        button:hover {
            background-color: rgb(75, 180, 140);
        }

        p {
            font-size: 0.9rem;
            text-align: center;
        }

        a {
            color: rgb(94, 211, 166);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .image-container {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .image-container img {
            width: 500px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
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
    </div>
</body>
</html>