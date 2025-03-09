<?php
class Post {

    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener comentarios de una publicación
    public function obtenerComentarios($postId) {
        $sql = "SELECT comentarios.Comentario, comentarios.Fecha_Comentario, usuarios.Nombre
                FROM comentarios 
                JOIN usuarios ON comentarios.Id_usuario = usuarios.Id_usuario
                WHERE comentarios.Id_publicacion = ?
                ORDER BY comentarios.Fecha_Comentario DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();

        $comentarios = array();
        while ($row = $result->fetch_assoc()) {
            $comentarios[] = $row;
        }

        return $comentarios;
    }

    // Función para compartir una publicación
    public function compartirPublicacion($postId, $usuarioId) {
        // Verificar si la publicación original existe
        $sql = "SELECT Contenido, Imagen_url, Video_url FROM publicaciones WHERE Id_publicacion = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $post = $result->fetch_assoc();

            // Insertar la publicación compartida
            $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion, Id_usuario_compartido) 
                          VALUES (?, ?, ?, ?, NOW(), ?)";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->bind_param("isssi", $usuarioId, $post['Contenido'], $post['Imagen_url'], $post['Video_url'], $usuarioId);

            if ($stmtInsert->execute()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // Guardar una nueva publicación
    public function guardarPublicacion($usuarioId, $contenido, $imagenUrl, $videoUrl) {
        $sql = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isss", $usuarioId, $contenido, $imagenUrl, $videoUrl);

        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
    }
}
?>
