<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id_publicacion']) && is_numeric($data['id_publicacion'])) {
    $postId = $data['id_publicacion'];
    $userId = $_SESSION['Id_usuario'];

    // Obtener la información de la publicación
    $sql = "SELECT Id_usuario FROM publicaciones WHERE Id_publicacion = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
        
        // Verificar si el usuario es el autor de la publicación
        $es_autor = ($post['Id_usuario'] == $userId);
        
        // Permitir edición siempre que el usuario sea el autor
        $puede_editar = $es_autor;
        
        echo json_encode([
            "success" => true,
            "puede_editar" => $puede_editar,
            "es_autor" => $es_autor
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Publicación no encontrada"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Parámetros inválidos"]);
}
?>