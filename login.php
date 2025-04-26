<?php
require_once "Database.php";
require_once "vendor/autoload.php"; // Asegúrate de instalar Google API Client con Composer

use Google\Client as GoogleClient;

session_start();
$mensaje = "";

// Configuración para OAuth de Google
$googleClient = new GoogleClient();
$googleClient->setClientId('992355462360-26cqqukout33it30h5l3biam5fnsaum7.apps.googleusercontent.com'); // Reemplaza con tu Client ID
$googleClient->setClientSecret('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'); // Reemplaza con tu Client Secret
$googleClient->setRedirectUri('http://localhost/PROYECTO%20Unired/PROYECTO%20RED%20SOCIAL/google_callback.php'); // URL de redirección después de autenticación
$googleClient->addScope('email');
$googleClient->addScope('profile');

// URL para el botón de inicio de sesión con Google
$googleAuthUrl = $googleClient->createAuthUrl();

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
    $sql = "SELECT Id_usuario, nombre, email, Contraseña, Foto_Perfil FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Error en la preparación de la consulta: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($Id_usuario, $nombre, $email, $hashed_Contraseña, $foto_perfil);
        $stmt->fetch();

        // Verificar la contraseña
        if (password_verify($password, $hashed_Contraseña)) {
            $_SESSION['Id_usuario'] = $Id_usuario;
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            $_SESSION['foto_perfil'] = $foto_perfil;

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .image-container img {
            width: 500px;
            height: auto;
        }
        
        /* Estilos para el botón de Google */
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #fff;
            color: #757575;
            border-radius: 5px;
            padding: 10px 15px;
            margin: 20px 0;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .google-btn:hover {
            background-color: #f1f1f1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .google-btn i {
            color: #4285F4;
            font-size: 1.2rem;
            margin-right: 10px;
        }
        
        .separator {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 20px 0;
            color: #ddd;
        }
        
        .separator::before,
        .separator::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        
        .separator::before {
            margin-right: 10px;
        }
        
        .separator::after {
            margin-left: 10px;
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
            
            <!-- Botón de inicio de sesión con Google -->
            <a href="<?php echo $googleAuthUrl; ?>" class="google-btn">
                <i class="fab fa-google"></i> Iniciar sesión con Google
            </a>
            
            <div class="separator">O</div>

            <form action="login.php" method="post">
                <label for="email">Correo Electrónico:</label>
                <input type="email" name="email" placeholder="Ingrese su correo" required>

                <label for="password">Contraseña:</label>
                <input type="password" name="password" placeholder="Ingrese su contraseña" required>

                <button type="submit">Iniciar Sesión</button>
            </form>

            <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
        </div>
    </div>
</body>
</html>