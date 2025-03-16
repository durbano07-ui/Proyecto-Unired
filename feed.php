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
$sql = "SELECT p.Id_publicacion, p.Contenido, u.Nombre, u.Foto_Perfil, p.Fecha_Publicacion, p.Imagen_url, p.Video_url, p.Id_usuario,
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
    <script src="script.js"></script>
</head>
<body>
    <div class="container">
        <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?> </h2>
        
        <h3>Publicaciones</h3>
        <div id="feed">
            <?php while ($fila = $resultado->fetch_assoc()) { ?>
                <div class="publicacion" id="post_<?php echo $fila['Id_publicacion']; ?>">
                    <img src="<?php echo htmlspecialchars($fila['Foto_Perfil']); ?>" alt="Foto de perfil" class="foto-perfil">
                    <div class="header">
                        <p><strong><?php echo htmlspecialchars($fila['Nombre']); ?></strong> <small><?php echo $fila['Fecha_Publicacion']; ?></small></p>
                    </div>
                    <p><?php echo htmlspecialchars($fila['Contenido']); ?></p>

                    <?php if ($fila['Imagen_url']) { ?>
                        <img src="<?php echo htmlspecialchars($fila['Imagen_url']); ?>" alt="Imagen de la publicación">
                    <?php } ?>

                    <?php if ($fila['Video_url']) { ?>
                        <video controls>
                            <source src="<?php echo htmlspecialchars($fila['Video_url']); ?>" type="video/mp4">
                        </video>
                    <?php } ?>

                    <div class="acciones">
                        <button class="like-btn" onclick="toggleLike(<?php echo $fila['Id_publicacion']; ?>, this)">
                            <i class="fas fa-heart" style="color: <?php echo ($fila['user_liked'] > 0) ? '#e74c3c' : '#fff'; ?>"></i>
                            <div class="likes-count" id="likes-count-<?php echo $fila['Id_publicacion']; ?>"><?php echo $fila['likes_count']; ?></div>
                        </button>

                        <button class="comment-btn" onclick="toggleCommentBox(<?php echo $fila['Id_publicacion']; ?>)">
                            <i class="fas fa-comment"></i> Comentar
                        </button>

                        <button class="share-btn" onclick="sharePost(<?php echo $fila['Id_publicacion']; ?>)">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                    </div>

                    <div class="comments" id="comments_<?php echo $fila['Id_publicacion']; ?>">
                        <!-- Comentarios se cargarán aquí -->
                    </div>

                    <div class="comment-input-container" id="comment-box-<?php echo $fila['Id_publicacion']; ?>" style="display:none;">
                        <textarea id="comment-input-<?php echo $fila['Id_publicacion']; ?>" placeholder="Escribe un comentario..."></textarea>
                        <button onclick="submitComment(<?php echo $fila['Id_publicacion']; ?>)">Comentar</button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function toggleLike(postId, button) {
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: postId,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeCount = document.getElementById('likes-count-' + postId);
                    const heartIcon = button.querySelector('i');
                    const isLiked = data.isLiked;

                    if (isLiked) {
                        heartIcon.style.color = '#e74c3c'; // Rojo
                    } else {
                        heartIcon.style.color = '#fff'; // Blanco
                    }

                    likeCount.textContent = data.likesCount;
                }
            })
            .catch(error => console.error('Error al dar like:', error));
        }

        function toggleCommentBox(postId) {
            const commentBox = document.getElementById('comment-box-' + postId);
            commentBox.style.display = commentBox.style.display === 'none' ? 'block' : 'none';
        }

        function submitComment(postId) {
            const commentInput = document.getElementById('comment-input-' + postId);
            const comment = commentInput.value.trim();

            if (comment === '') return;

            fetch('comentar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: postId,
                    comentario: comment,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const commentList = document.getElementById('comments_' + postId);
                    commentList.innerHTML += `
                        <div class="comment">
                            <p><strong>${data.nombre}</strong> <small>${data.fecha}</small></p>
                            <p>${data.contenido}</p>
                        </div>
                    `;
                    commentInput.value = ''; // Limpiar input
                }
            })
            .catch(error => console.error('Error al comentar:', error));
        }

        function sharePost(postId) {
            fetch('guardar_publicacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_publicacion: postId,
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Publicación compartida exitosamente');
                }
            })
            .catch(error => console.error('Error al compartir:', error));
        }
    </script>
</body>
</html>



