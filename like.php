<?php
require_once "Database.php";
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['Id_usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No estás logueado']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['postId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Falta el ID de la publicación']);
    exit();
}

$postId = $data['postId'];
$userId = $_SESSION['Id_usuario'];

// Verificar si ya existe un like
$sql = "SELECT * FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $postId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$success = false;
$action = '';

if ($result->num_rows > 0) {
    // Eliminar el like
    $sqlDelete = "DELETE FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("ii", $postId, $userId);
    $success = $stmtDelete->execute();
    $action = 'unlike';
} else {
    // Insertar el like
    $sqlInsert = "INSERT INTO likes (Id_publicacion, Id_usuario) VALUES (?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $postId, $userId);
    $success = $stmtInsert->execute();
    $action = 'like';
}

// Contar los likes actuales
$countSql = "SELECT COUNT(*) as total FROM likes WHERE Id_publicacion = ?";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param("i", $postId);
$countStmt->execute();
$countResult = $countStmt->get_result();
$likeCount = $countResult->fetch_assoc()['total'];

// Devolver respuesta
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'action' => $action,
    'likes' => $likeCount
]);
?>