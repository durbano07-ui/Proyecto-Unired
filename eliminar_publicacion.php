<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No estás logueado']);
    exit();
}

if (isset($_POST['id'])) {
    $postId = $_POST['id'];

    // Conectar a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Consulta para eliminar la publicación
    $sql = "DELETE FROM publicaciones WHERE Id_publicacion = ? AND Id_usuario = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('ii', $postId, $_SESSION['Id_usuario']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la publicación']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan parámetros']);
}
?>
