<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No estás logueado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id']) && isset($_POST['comentario']) && !empty($_POST['comentario'])) {
        $postId = $_POST['id'];
        $comentario = $_POST['comentario'];
        $usuarioId = $_SESSION['Id_usuario'];

        $database = new Database();
        $conn = $database->getConnection();

        if ($conn->connect_error) {
            echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
            exit();
        }

        // Insertar el comentario en la base de datos
        $sql = "INSERT INTO comentarios (Id_publicacion, Id_usuario, Contenido_C, Fecha_Comentario) 
                VALUES (?, ?, ?, NOW())";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('iis', $postId, $usuarioId, $comentario);

            if ($stmt->execute()) {
                // Obtener el ID del comentario insertado
                $commentId = $stmt->insert_id;

                // Obtener el nombre del usuario y la fecha del comentario
                $userQuery = "SELECT Nombre FROM usuarios WHERE Id_usuario = ?";
                $userStmt = $conn->prepare($userQuery);
                $userStmt->bind_param("i", $usuarioId);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $userData = $userResult->fetch_assoc();

                echo json_encode([
                    'success' => true,
                    'comment_id' => $commentId,
                    'nombre' => $userData['Nombre'],
                    'contenido' => htmlspecialchars($comentario),
                    'fecha' => date("Y-m-d H:i:s") // Fecha actual del servidor
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al ejecutar la consulta: ' . $stmt->error]);
            }

            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta: ' . $conn->error]);
        }

        $conn->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Faltan datos para el comentario']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido']);
}
?>
