<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No se ha iniciado sesión.']);
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id_publicacion = $_POST['id'];
    $id_usuario = $_SESSION['Id_usuario'];

    // Verificar si el usuario ya dio like a la publicación
    $sql = "SELECT * FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_publicacion, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Ya ha dado like, eliminarlo
        $deleteSql = "DELETE FROM likes WHERE Id_publicacion = ? AND Id_usuario = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("ii", $id_publicacion, $id_usuario);
        $deleteStmt->execute();
    } else {
        // No ha dado like, agregarlo
        $insertSql = "INSERT INTO likes (Id_publicacion, Id_usuario) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("ii", $id_publicacion, $id_usuario);
        $insertStmt->execute();
    }

    // Contar los likes
    $countSql = "SELECT COUNT(*) AS likes_count FROM likes WHERE Id_publicacion = ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("i", $id_publicacion);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $count = $countResult->fetch_assoc();

    echo json_encode(['success' => true, 'likes_count' => $count['likes_count']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
}
?>
