<?php
require_once 'Database.php';

class Post {

    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
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

    // Obtener publicaciones y sus comentarios
    public function obtenerFeed($usuarioId) {
        $sql = "SELECT p.Id_publicacion, p.Contenido, p.Imagen_url, p.Video_url, p.Fecha_Publicacion, u.Nombre AS autor_nombre
                FROM publicaciones p
                JOIN usuarios u ON p.Id_usuario = u.Id_usuario
                WHERE p.Id_usuario != ? 
                ORDER BY p.Fecha_Publicacion DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $usuarioId);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener los comentarios de una publicación
    public function obtenerComentarios($postId) {
        $sql = "SELECT c.Comentario, c.Fecha_Comentario, u.Nombre AS autor_nombre
                FROM comentarios c
                JOIN usuarios u ON c.Id_usuario = u.Id_usuario
                WHERE c.Id_publicacion = ?
                ORDER BY c.Fecha_Comentario DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Obtener los likes de una publicación
    public function obtenerLikes($postId) {
        $sql = "SELECT COUNT(*) AS likes_count
                FROM likes
                WHERE Id_publicacion = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

session_start();
$database = new Database();
$conn = $database->getConnection();
$post = new Post($conn);

$usuarioId = $_SESSION['Id_usuario'];  // Asegúrate de que la sesión esté activa y contenga el Id_usuario

// Obtener publicaciones y comentarios
$publicaciones = $post->obtenerFeed($usuarioId);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['compartir'])) {
    $postId = $_POST['post_id'];
    $resultado = $post->compartirPublicacion($postId, $usuarioId);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Feed</title>
    <link rel="stylesheet" href="styles.css"> <!-- Asegúrate de tener tu archivo de estilos -->
</head>
<body>

    <div id="feed">
        <?php while ($fila = $publicaciones->fetch_assoc()) : ?>
            <div class="publicacion" id="post_<?php echo $fila['Id_publicacion']; ?>">
                <div class="header">
                    <div class="header-left">
                        <p><strong><?php echo htmlspecialchars($fila['autor_nombre']); ?></strong></p>
                        <small><?php echo $fila['Fecha_Publicacion']; ?></small>
                    </div>
                </div>

                <p><?php echo htmlspecialchars($fila['Contenido']); ?></p>

                <?php if (!empty($fila['Imagen_url'])) { ?>
                    <img src='<?php echo htmlspecialchars($fila['Imagen_url']); ?>' alt='Imagen'>
                <?php } ?>
                <?php if (!empty($fila['Video_url'])) { ?>
                    <video controls>
                        <source src='<?php echo htmlspecialchars($fila['Video_url']); ?>' type='video/mp4'>
                    </video>
                <?php } ?>

                <!-- Botones de interacciones -->
                <div class="acciones">
                    <form action="feed.php" method="post">
                        <input type="hidden" name="post_id" value="<?php echo $fila['Id_publicacion']; ?>">
                        <button type="submit" name="compartir">Compartir</button>
                    </form>
                    
                    <p>Comentarios:</p>
                    <?php 
                    $comentarios = $post->obtenerComentarios($fila['Id_publicacion']);
                    while ($comentario = $comentarios->fetch_assoc()) {
                        echo "<div><strong>" . htmlspecialchars($comentario['autor_nombre']) . "</strong>: " . htmlspecialchars($comentario['Comentario']) . "</div>";
                    }
                    ?>
                    
                    <p>Likes: <?php echo $post->obtenerLikes($fila['Id_publicacion'])['likes_count']; ?></p>
                </div>

                <!-- Mostrar si la publicación fue compartida -->
                <?php if (isset($resultado) && $resultado['success']) { ?>
                    <p><strong>Compartido por:</strong> <?php echo htmlspecialchars($resultado['shared_by']); ?> <br> <strong>Publicado por:</strong> <?php echo htmlspecialchars($resultado['author_name']); ?></p>
                <?php } ?>
            </div>
        <?php endwhile; ?>
    </div>

</body>
</html>


