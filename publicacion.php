<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contenido'])) {
    $contenido = $_POST['contenido'];
    $usuarioId = $_SESSION['Id_usuario'];

    // Manejar imagen
    $imagen_url = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen_url = 'uploads/' . basename($_FILES['imagen']['name']);
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_url);
    }

    // Manejar video
    $video_url = null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $video_url = 'uploads/' . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], $video_url);
    }

    // Insertar publicación
    $sql = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $usuarioId, $contenido, $imagen_url, $video_url);
    
    if ($stmt->execute()) {
        header("Location: feed.php"); // Redirigir al feed
    } else {
        echo "Error al guardar la publicación: " . $stmt->error;
    }
}
?>
