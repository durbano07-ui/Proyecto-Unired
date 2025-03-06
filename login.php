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
        body {
            font-family: Arial, sans-serif;
            background-color: #ecf0f1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        .login-form {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }

        .login-form label {
            display: block;
            font-size: 1rem;
            color: #7f8c8d;
            margin-bottom: 5px;
            text-align: left;
        }

        .login-form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #bdc3c7;
            font-size: 1rem;
        }

        .login-form input[type="email"], .login-form input[type="password"] {
            background-color: #f4f6f8;
        }

        .login-form button {
            background-color: #3498db;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1.1rem;
        }

        .login-form button:hover {
            background-color: #2980b9;
        }

        .login-form p {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .login-form a {
            color: #3498db;
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
    </style>
</head>
<body>

    <div class="login-form">
        <h2>Iniciar Sesión</h2>

        <?php if (!empty($mensaje)): ?>
            <p class="error-message"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <label for="email">Correo Electrónico:</label>
            <input type="email" name="email" required>

            <label for="password">Contraseña:</label>
            <input type="password" name="password" required>

            <button type="submit">Iniciar Sesión</button>
        </form>

        <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>

</body>
</html>
