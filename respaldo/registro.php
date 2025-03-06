<?php
$conn = new mysqli("localhost", "root", "", "UniredBd");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $biografia = trim($_POST["biografia"]);
    
    // Manejo de la foto de perfil
    $foto_perfil_url = null;

    if (isset($_FILES["foto_perfil"]) && $_FILES["foto_perfil"]["error"] === UPLOAD_ERR_OK) {
        var_dump($_FILES["foto_perfil"]); // Depuración: Ver qué está llegando en $_FILES
        
        $directorio = "uploads/";
        if (!is_dir($directorio)) {
            mkdir($directorio, 0777, true);
        }
    
        $nombreArchivo = time() . "_" . basename($_FILES["foto_perfil"]["name"]);
        $rutaArchivo = $directorio . $nombreArchivo;
    
        if (move_uploaded_file($_FILES["foto_perfil"]["tmp_name"], $rutaArchivo)) {
            $foto_perfil_url = $rutaArchivo;
        } else {
            echo "❌ Error al subir la imagen.";
        }
    } else {
        echo "⚠️ No se subió ningún archivo o hubo un error. Código: " . $_FILES["foto_perfil"]["error"];
    }
    

    $checkEmail = $conn->prepare("SELECT Id_usuario FROM Usuarios WHERE Email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $checkEmail->store_result();

    if ($checkEmail->num_rows > 0) {
        $mensaje = "Este correo ya está registrado.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Usuarios (Nombre, Apellido, Email, Contraseña, Biografia, Foto_perfil) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nombre, $apellido, $email, $password, $biografia, $foto_perfil_url);

        if ($stmt->execute()) {
            header("Location: login.php");
            exit();
        } else {
            $mensaje = "❌ Error al registrar el usuario.";
        }

        $stmt->close();
    }
    $checkEmail->close();
}
$conn->close();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - UniredBd</title>
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
        input, textarea, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
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
    <h2>Registro de Usuario</h2>
    <div class="contenedor">
        <form id="registroForm" method="POST" enctype="multipart/form-data">
            <!-- Paso 1: Nombre -->
            <div class="step active">
                <input type="text" name="nombre" id="nombre" placeholder="Nombre" required>
                <button type="button" onclick="siguientePaso()">Siguiente</button>
            </div>

            <!-- Paso 2: Apellido -->
            <div class="step">
                <input type="text" name="apellido" id="apellido" placeholder="Apellido" required>
                <button type="button" onclick="siguientePaso()">Siguiente</button>
            </div>

            <!-- Paso 3: Email -->
            <div class="step">
                <input type="email" name="email" id="email" placeholder="Correo electrónico" required>
                <button type="button" onclick="siguientePaso()">Siguiente</button>
            </div>

            <!-- Paso 4: Contraseña -->
            <div class="step">
                <input type="password" name="password" id="password" placeholder="Contraseña" required>
                <button type="button" onclick="siguientePaso()">Siguiente</button>
            </div>
    
            <!-- Paso 5: Foto de perfil -->
            <div class="step">
                <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*">
                <button type="button" onclick="siguientePaso()">Siguiente</button>
            </div>

            <!-- Paso 6: Biografía -->
            <div class="step">
                <textarea name="biografia" id="biografia" placeholder="Escribe tu biografía"></textarea>
                <button type="submit">Registrarse</button>
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
