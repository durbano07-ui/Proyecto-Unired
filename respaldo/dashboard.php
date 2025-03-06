<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php"); // Redirigir al login si no está autenticado
    exit();
}

// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "UniredBd");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contenido = trim($_POST["contenido"]);
    $usuario_id = $_SESSION["usuario"]; // Obtener el ID del usuario de la sesión

    // Insertar la publicación en la base de datos
    $stmt = $conn->prepare("INSERT INTO Publicaciones (Contenido, Id_usuario) VALUES ( ?, ?)");
    $stmt->bind_param("si", $contenido, $usuario_id);

    if ($stmt->execute()) {
        $mensaje = "✅ Publicación realizada con éxito!";
    } else {
        $mensaje = "❌ Error al publicar.";
    }

    $stmt->close();
}

// Obtener las publicaciones
$query = "SELECT p.Contenido, p.Fecha_Publicacion, u.Nombre, u.foto_perfil FROM Publicaciones p INNER JOIN Usuarios u ON p.Id_usuario = u.Id_usuario ORDER BY p.Fecha_Publicacion DESC";
$result = $conn->query($query);
if ($result === false) {
    die("Error en la consulta: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feed - UniredBd</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .navbar {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }
        .navbar a {
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
        }
        .navbar a:hover {
            background-color: #575757;
        }
        .container {
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .feed {
            width: 60%;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .post {
            background-color: #fff;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        .username {
            font-weight: bold;
        }
        .post-time {
            font-size: 12px;
            color: #777;
        }
        .post-content {
            margin-top: 10px;
            font-size: 16px;
            color: #555;
        }
        .new-post-form {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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
    </style>
</head>
<body>

    <!-- Barra de navegación -->
    <div class="navbar">
        <a href="dashboard.php">Inicio</a>
        <a href="profile.php">Perfil</a>
        <a href="logout.php">Cerrar sesión</a>
    </div>

    <!-- Contenedor principal -->
    <div class="container">
        <!-- Feed de publicaciones -->
        <div class="feed">
            <!-- Formulario para nueva publicación -->
            <div class="new-post-form">
                <h3>¿Qué estás pensando?</h3>
                <form method="POST">
                    <textarea name="contenido" placeholder="Escribe algo..." required></textarea>
                    <button type="submit">Publicar</button>
                </form>
                <p><?php echo $mensaje; ?></p>
            </div>

            <!-- Mostrar publicaciones -->
            <div class="posts">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Mostrar cada publicación en formato feed
                        echo "
                            <div class='post'>
                                <div class='post-header'>
                                    <div class='user-info'>
                                        <img src='{$row['foto_perfil']}' alt='Foto de perfil' class='profile-pic'>
                                        <span class='username'>{$row['Nombre']}</span>
                                    </div>
                                    <span class='post-time'>{$row['Fecha_Publicacion']}</span>
                                </div>
                                <div class='post-content'>
                                    <p>{$row['Contenido']}</p>
                                </div>
                            </div>
                        ";
                    }
                } else {
                    echo "<p>No hay publicaciones aún.</p>";
                }

                // Cerrar la conexión
                $conn->close();
                ?>
            </div>
        </div>
    </div>
</body>
</html>
