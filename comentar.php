<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No estás logueado']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$postId = $data['id'];
$comentario = $data['comentario'];
$usuarioId = $_SESSION['Id_usuario'];

$database = new Database();
$conn = $database->getConnection();

$sql = "INSERT INTO comentarios (Id_publicacion, Id_usuario, Contenido_C, Fecha_Comentario) 
        VALUES (?, ?, ?, NOW())";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('iis', $postId, $usuarioId, $comentario);

    if ($stmt->execute()) {
        $userQuery = "SELECT Nombre FROM usuarios WHERE Id_usuario = ?";
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $usuarioId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();

        echo json_encode([
            'success' => true,
            'comment_id' => $stmt->insert_id,
            'nombre' => $userData['Nombre'],
            'contenido' => htmlspecialchars($comentario),
            'fecha' => date("Y-m-d H:i:s"),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta']);
    }

    $stmt->close();
}
$conn->close();
?>


