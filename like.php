<?php
require_once "Database.php";
session_start();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$postId = $data['postId'];
$userId = $_SESSION['Id_usuario'];

// Verificar si ya existe un like
$sql = "SELECT * FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Eliminar el like
    $sqlDelete = "DELETE FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("ii", $postId, $userId);
    $stmtDelete->execute();
} else {
    // Insertar el like
    $sqlInsert = "INSERT INTO likes (Id_publicacion, Id_usuario) VALUES (?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $postId, $userId);
    $stmtInsert->execute();
}
?>


