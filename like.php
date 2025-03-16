<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No estás logueado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$postId = $data['id'];

$database = new Database();
$conn = $database->getConnection();

// Verificar si el usuario ya ha dado like
$sql = "SELECT * FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $postId, $_SESSION['Id_usuario']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Ya existe el like, eliminarlo
    $sqlDelete = "DELETE FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
    $stmtDelete = $conn->prepare($sqlDelete);
    $stmtDelete->bind_param("ii", $postId, $_SESSION['Id_usuario']);
    $stmtDelete->execute();
    $isLiked = false;
} else {
    // Insertar nuevo like
    $sqlInsert = "INSERT INTO likes (Id_publicacion, Id_usuario) VALUES (?, ?)";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("ii", $postId, $_SESSION['Id_usuario']);
    $stmtInsert->execute();
    $isLiked = true;
}

// Contar los likes
$sqlLikesCount = "SELECT COUNT(*) AS likes_count FROM likes WHERE Id_publicacion = ?";
$stmtLikesCount = $conn->prepare($sqlLikesCount);
$stmtLikesCount->bind_param("i", $postId);
$stmtLikesCount->execute();
$likesCountResult = $stmtLikesCount->get_result();
$likesCount = $likesCountResult->fetch_assoc()['likes_count'];

echo json_encode([
    'success' => true,
    'isLiked' => $isLiked,
    'likesCount' => $likesCount,
]);



