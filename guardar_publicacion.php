<?php
require_once "Database.php";
session_start();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$postId = $data['id_publicacion'];
$userId = $_SESSION['Id_usuario'];

// Verificar si la publicación ya está guardada
$checkQuery = "SELECT * FROM publicaciones_guardadas WHERE Id_publicacion = ? AND Id_usuario = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ii", $postId, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

// Si ya está guardada, se elimina (para implementar toggle de guardar/desguardar)
if ($checkResult->num_rows > 0) {
    $deleteQuery = "DELETE FROM publicaciones_guardadas WHERE Id_publicacion = ? AND Id_usuario = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $postId, $userId);
    
    if ($deleteStmt->execute()) {
        echo json_encode(["success" => true, "action" => "removed"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar el guardado"]);
    }
} else {
    // Si no está guardada, se guarda
    $insertQuery = "INSERT INTO publicaciones_guardadas (Id_publicacion, Id_usuario, Fecha_guardado) VALUES (?, ?, NOW())";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ii", $postId, $userId);
    
    if ($insertStmt->execute()) {
        echo json_encode(["success" => true, "action" => "saved"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al guardar la publicación"]);
    }
}
?>