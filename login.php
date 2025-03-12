<?php
require_once "Database.php";

session_start();
$mensaje = "";

// Habilitar la visualización de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        die("Error: Todos los campos son obligatorios.");
    }

    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    if (!$conn) {
        die("Error en la conexión a la base de datos: " . mysqli_connect_error());
    }

    // Consulta para verificar el usuario
    $sql = "SELECT Id_usuario, nombre, email, Contraseña FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($Id_usuario, $nombre, $email, $hashed_Contraseña);
        $stmt->fetch();

        // Verificar la contraseña
        if (password_verify($password, $hashed_Contraseña)) {
            $_SESSION['Id_usuario'] = $Id_usuario;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;

            header("Location: feed.php");
            exit();
        } else {
            $mensaje = "Contraseña incorrecta.";
        }
    } else {
        $mensaje = "Correo no registrado.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

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
        }

        .container {
            display: flex;
            width: 80%;
            margin: auto;
        }

        .login-form {
            flex: 1;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            max-width: 350px;
        }

        .login-form h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 20px;
        }

        .login-form label {
            display: block;
            font-size: 1rem;
            margin-bottom: 5px;
        }

        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .login-form input::placeholder {
            color: #ddd;
        }

        .login-form button {
            background-color:rgb(94, 211, 166);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
        }

        .login-form button:hover {
            background-color:rgb(94, 211, 166);
        }

        .login-form p {
            font-size: 0.9rem;
            text-align: center;
        }

        .login-form a {
            color: rgb(94, 211, 166);
            text-decoration: none;
        }

        .login-form a:hover {
            text-decoration: underline;
        }

        .error-message {
            color: red;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        .image-container {
    position: absolute; /* O usa 'fixed' si quieres que se mantenga siempre visible */
    top: 20px; /* Ajusta la distancia desde la parte superior */
    right: 20px; /* Ajusta la distancia desde la derecha */
}

.image-container img {
    width: 500px; /* Aumenta el tamaño del logo */
    height: auto; /* Mantiene la proporción */
}

}
    </style>
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Iniciar Sesión</h2>

            <?php if (!empty($mensaje)): ?>
                <p class="error-message"><?php echo $mensaje; ?></p>
            <?php endif; ?>

            <form action="login.php" method="post">
                <label for="email">Correo Electrónico:</label>
                <input type="email" name="email" placeholder="Ingrese su correo" required>

                <label for="password">Contraseña:</label>
                <input type="password" name="password" placeholder="Ingrese su contraseña" required>

                <button type="submit">Iniciar Sesión</button>
            </form>

            <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
        </div>

</body>
</html>

