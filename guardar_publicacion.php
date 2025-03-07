<?<?php
require_once "Database.php";
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    $data = json_decode(file_get_contents("php://input"), true);
    if (isset($data['id_publicacion'])) {
        $id_publicacion = $data['id_publicacion'];
        $usuarioId = $_SESSION['Id_usuario'];

        // Obtener el contenido de la publicación original
        $sql = "SELECT Contenido, Imagen_url, Video_url FROM publicaciones WHERE Id_publicacion = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_publicacion);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $contenido = $row['Contenido'];
            $imagen_url = $row['Imagen_url'];
            $video_url = $row['Video_url'];

            // Insertar el repost
            $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url) VALUES (?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("isss", $usuarioId, $contenido, $imagen_url, $video_url);
            if ($stmtInsert->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Publicación no encontrada']);
        }
    }
}
?>

