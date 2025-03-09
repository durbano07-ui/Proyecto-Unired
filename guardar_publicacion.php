<?php
require_once "Database.php";
session_start();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['postId']) && isset($_SESSION['Id_usuario'])) {
    $postId = $data['postId'];
    $userId = $_SESSION['Id_usuario'];

    // Obtener el contenido de la publicación original
    $sql = "SELECT Contenido, Imagen_url, Video_url FROM publicaciones WHERE Id_publicacion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();

        // Crear una nueva publicación (repost) con el mismo contenido
        $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion, Id_publicacion_original) 
                      VALUES (?, ?, ?, ?, NOW(), ?)";
        $stmtInsert = $conn->prepare($sqlInsert);
        $stmtInsert->bind_param("isssi", $userId, $post['Contenido'], $post['Imagen_url'], $post['Video_url'], $postId);
        
        if ($stmtInsert->execute()) {
            // Obtener nombre del usuario que hace el repost
            $nombreUsuario = $_SESSION['nombre']; // Asumiendo que el nombre está guardado en la sesión
            $fechaRepost = date('Y-m-d H:i:s');

            // Responder con los datos del repost para actualizar el feed
            echo json_encode([
                'success' => true,
                'nombre' => $nombreUsuario,
                'fecha' => $fechaRepost,
                'contenido' => $post['Contenido'],
                'imagen' => $post['Imagen_url'],
                'video' => $post['Video_url']
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al guardar el repost"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Publicación no encontrada"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
}
?>
