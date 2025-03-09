<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$sql = "SELECT p.Id_publicacion, p.Contenido, u.Nombre, p.Fecha_Publicacion, p.Imagen_url, p.Video_url,
            (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion) AS likes_count,
            (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion AND Id_usuario = ?) AS user_liked
        FROM publicaciones p 
        JOIN usuarios u ON p.Id_usuario = u.Id_usuario 
        ORDER BY p.Fecha_Publicacion DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['Id_usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Social</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script>
        function compartirPublicacion(postId) {
            fetch('compartir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Publicación compartida correctamente.');
                    location.reload();
                } else {
                    alert('Error al compartir la publicación.');
                }
            })
            .catch(error => console.error('Error al compartir:', error));
        }

        function toggleCommentOptions(commentId) {
            let menu = document.getElementById('comment-menu-' + commentId);
            menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
        }

        function eliminarComentario(commentId) {
            fetch('eliminar_comentario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ commentId: commentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('comment_' + commentId).remove();
                } else {
                    alert('Error al eliminar comentario.');
                }
            });
        }

        function editarComentario(commentId) {
            let commentText = document.getElementById('comment-text-' + commentId);
            let nuevoComentario = prompt("Edita tu comentario:", commentText.innerHTML);

            if (nuevoComentario) {
                fetch('editar_comentario.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ commentId: commentId, contenido: nuevoComentario })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        commentText.innerHTML = nuevoComentario;
                    } else {
                        alert('Error al editar comentario.');
                    }
                });
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h3>Publicaciones</h3>
        <div id="feed">
            <?php while ($fila = $resultado->fetch_assoc()) { ?>
                <div class='publicacion' id='post_<?php echo $fila['Id_publicacion']; ?>'>
                    <div class="header">
                        <p><strong><?php echo htmlspecialchars($fila['Nombre']); ?></strong></p>
                        <small><?php echo $fila['Fecha_Publicacion']; ?></small>
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

                    <div class='acciones'>
                        <button class="share-btn" onclick="compartirPublicacion(<?php echo $fila['Id_publicacion']; ?>)">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                    </div>

                    <div class="comments" id="comments_<?php echo $fila['Id_publicacion']; ?>">
                        <?php
                        $comentariosQuery = "SELECT c.Id_comentario, c.Contenido_C, c.Fecha_Comentario, u.Nombre 
                                            FROM comentarios c 
                                            JOIN usuarios u ON c.Id_usuario = u.Id_usuario 
                                            WHERE c.Id_publicacion = ? 
                                            ORDER BY c.Fecha_Comentario ASC";
                        $stmtComentarios = $conn->prepare($comentariosQuery);
                        $stmtComentarios->bind_param("i", $fila['Id_publicacion']);
                        $stmtComentarios->execute();
                        $comentariosResultado = $stmtComentarios->get_result();

                        while ($comentario = $comentariosResultado->fetch_assoc()) {
                            echo "<div class='comment' id='comment_{$comentario['Id_comentario']}'>
                                    <div class='comment-header'>
                                        <strong>{$comentario['Nombre']}</strong> <small>{$comentario['Fecha_Comentario']}</small>
                                        <div class='comment-options' onclick='toggleCommentOptions({$comentario['Id_comentario']})'>⋮</div>
                                        <div class='comment-menu' id='comment-menu-{$comentario['Id_comentario']}' style='display: none;'>
                                            <button onclick='editarComentario({$comentario['Id_comentario']})'>Editar</button>
                                            <button onclick='eliminarComentario({$comentario['Id_comentario']})'>Eliminar</button>
                                        </div>
                                    </div>
                                    <p id='comment-text-{$comentario['Id_comentario']}'>{$comentario['Contenido_C']}</p>
                                  </div>";
                        }
                        ?>
                    </div>

                    <div class="comment-input-container">
                        <textarea class="comment-input" placeholder="Escribe un comentario..." rows="3"></textarea>
                        <button class="submit-comment">Comentar</button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>







