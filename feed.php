<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Obtener las publicaciones
$sql = "SELECT p.Id_publicacion, p.Contenido, u.Nombre, p.Fecha_Publicacion, p.Imagen_url, p.Video_url 
        FROM publicaciones p 
        JOIN usuarios u ON p.Id_usuario = u.Id_usuario 
        ORDER BY p.Fecha_Publicacion DESC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado) {
    die("❌ Error al obtener publicaciones: " . $conn->error);
}
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
        // Función para compartir una publicación
        function compartirPublicacion(postId, nombreAutor) {
            fetch('compartir.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let feed = document.getElementById('feed');
                    let sharedPost = document.createElement('div');
                    sharedPost.classList.add('publicacion');
                    sharedPost.innerHTML = `
                        <div class="header">
                            <p><strong>${data.compartido_por} compartió una publicación de ${nombreAutor}</strong></p>
                            <small>${data.fecha_compartida}</small>
                        </div>
                        <p>${data.contenido}</p>
                        ${data.imagen ? `<img src="${data.imagen}" alt="Imagen">` : ''}
                        ${data.video ? `<video controls><source src="${data.video}" type="video/mp4"></video>` : ''}
                    `;
                    feed.prepend(sharedPost);
                    alert('Publicación compartida correctamente.');
                } else {
                    alert('Error al compartir la publicación.');
                }
            })
            .catch(error => console.error('Error al compartir:', error));
        }

        // Mostrar opciones en los comentarios (tres puntos)
        function toggleCommentOptions(commentId) {
            let menu = document.getElementById('comment-menu-' + commentId);
            menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
        }

        // Eliminar un comentario
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

        // Editar un comentario
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
        <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?> </h2>
        <a href="logout.php">Cerrar Sesión</a>
        
        <h2>Crear Publicación</h2>
        <form action="publicar.php" method="post" enctype="multipart/form-data">
            <textarea name="contenido" placeholder="Escribe tu publicación..."></textarea>

            <label for="imagenUpload" class="file-label">
                <i class="fas fa-image"></i> Subir Imagen
            </label>
            <input type="file" id="imagenUpload" name="imagen" accept="image/*" style="display: none;">

            <label for="videoUpload" class="file-label">
                <i class="fas fa-video"></i> Subir Video
            </label>
            <input type="file" id="videoUpload" name="video" accept="video/*" style="display: none;">

            <button type="submit">Publicar</button>
        </form>

        <h3>Publicaciones</h3>
        <div id="feed">
            <?php while ($fila = $resultado->fetch_assoc()) { ?>
                <div class='publicacion' id='post_<?php echo $fila['Id_publicacion']; ?>'>
                    <div class="header">
                        <div class="header-left">
                            <p><strong><?php echo htmlspecialchars($fila['Nombre']); ?></strong></p>
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

                    <div class='acciones'>
                        <button class="share-btn" onclick="compartirPublicacion(<?php echo $fila['Id_publicacion']; ?>, '<?php echo htmlspecialchars($fila['Nombre']); ?>')">
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
                </div>
            <?php } ?>
        </div>
    </div>

</body>
</html>





