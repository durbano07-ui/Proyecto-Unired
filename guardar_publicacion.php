<?php
require_once "Database.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['contenido']) && !empty($_POST['contenido'])) {
        $contenido = $_POST['contenido'];
        $imagen_url = null;
        $video_url = null;

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Aquí procesas la imagen
            $imagen_url = 'path_to_images/' . basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_url);
        }

        if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
            // Aquí procesas el video
            $video_url = 'path_to_videos/' . basename($_FILES['video']['name']);
            move_uploaded_file($_FILES['video']['tmp_name'], $video_url);
        }

        $database = new Database();
        $conn = $database->getConnection();

        $sql = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $_SESSION['Id_usuario'], $contenido, $imagen_url, $video_url);
        $stmt->execute();

        header("Location: feed.php");
    }
}
?>
