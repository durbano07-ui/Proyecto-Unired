<?php
session_start();
$conn = new mysqli("localhost", "root", "", "UniredBd");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $conn->prepare("SELECT Id_usuario, Contraseña FROM Usuarios WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id_usuario, $hashed_password);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION["usuario"] = $id_usuario;
        header("Location: dashboard.php");
        exit();
    } else {
        $mensaje = "❌ Email o contraseña incorrectos.";
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - UniredBd</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
        }
        .contenedor {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            margin: auto;
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .mensaje {
            margin-top: 10px;
            font-weight: bold;
            color: red;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
    </style>
</head>
<body>
    <h2>Iniciar Sesión</h2>
    <div class="contenedor">
        <form id="loginForm" method="POST">
            <!-- Paso 1: Email -->
            <div class="step active">
                <input type="email" name="email" id="email" placeholder="Correo electrónico" required>
                <button type="button" onclick="siguientePaso()">Siguiente</button>
            </div>

            <!-- Paso 2: Contraseña -->
            <div class="step">
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
                <button type="submit">Iniciar Sesión</button>
            </div>
        </form>
        <p class="mensaje"><?php echo $mensaje; ?></p>
    </div>

    <script>
        let pasoActual = 0;
        const pasos = document.querySelectorAll(".step");

        function siguientePaso() {
            if (pasoActual < pasos.length - 1) {
                pasos[pasoActual].classList.remove("active");
                pasoActual++;
                pasos[pasoActual].classList.add("active");
            }
        }
    </script>
</body>
</html>
