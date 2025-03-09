<?php
require_once 'Database.php';

class GuardarPublicacion {

    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Guardar una publicación
    public function guardarPublicacion($usuarioId, $contenido, $imagenUrl = null, $videoUrl = null) {
        // Insertar la nueva publicación
        $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion) 
                      VALUES (?, ?, ?, ?, NOW())";
        $stmtInsert = $this->conn->prepare($sqlInsert);
        $stmtInsert->bind_param("isss", $usuarioId, $contenido, $imagenUrl, $videoUrl);

        if ($stmtInsert->execute()) {
            return [
                'success' => true,
                'message' => 'Publicación guardada exitosamente'
            ];
        }
        return [
            'success' => false,
            'message' => 'Error al guardar la publicación'
        ];
    }

    // Función para compartir una publicación
    public function compartirPublicacion($postId, $usuarioId) {
        // Obtener la información de la publicación original
        $sql = "SELECT p.Id_publicacion, p.Contenido, p.Imagen_url, p.Video_url, u.Nombre AS autor_nombre
                FROM publicaciones p
                JOIN usuarios u ON p.Id_usuario = u.Id_usuario
                WHERE p.Id_publicacion = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $publicacion = $resultado->fetch_assoc();

            // Insertar la publicación compartida, incluyendo el nombre del usuario que comparte
            $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion, Id_usuario_compartido) 
                          VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->bind_param("isssi", $usuarioId, $publicacion['Contenido'], $publicacion['Imagen_url'], $publicacion['Video_url'], $postId);

            if ($stmtInsert->execute()) {
                return [
                    'success' => true,
                    'author_name' => $publicacion['autor_nombre'],  // Nombre del autor original
                    'shared_by' => $this->getUserName($usuarioId)  // Nombre de la persona que comparte
                ];
            }
        }

        return ['success' => false];
    }

    // Obtener nombre del usuario que comparte
    private function getUserName($userId) {
        $sql = "SELECT Nombre FROM usuarios WHERE Id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return $user['Nombre'];
        }
        return null;
    }
}
?>


