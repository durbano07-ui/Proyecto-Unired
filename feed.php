<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red Social</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script>
        // Función para manejar el like (y deslike)
        function toggleLike(postId, likeBtn) {
            let likesCount = document.getElementById('likes-count-' + postId);
            let heartIcon = likeBtn.querySelector('i');

            // Cambiar el color del corazón al darle like
            if (heartIcon.style.color === 'rgb(231, 76, 60)') {
                heartIcon.style.color = ''; // Deslike: quitar color rojo
                likesCount.innerHTML = parseInt(likesCount.innerHTML) - 1;
            } else {
                heartIcon.style.color = '#e74c3c'; // Like: poner color rojo
                likesCount.innerHTML = parseInt(likesCount.innerHTML) + 1;
            }

            // Enviar petición AJAX para guardar el estado del like
            fetch('like.php', {
                method: 'POST',
                body: JSON.stringify({ postId: postId }),
                headers: { 'Content-Type': 'application/json' }
            });
        }

        // Mostrar el cuadro de comentario al hacer click en el botón de comentar
        function toggleCommentBox(postId) {
            let commentBox = document.getElementById('comment-box-' + postId);
            commentBox.style.display = (commentBox.style.display === 'none' || commentBox.style.display === '') ? 'block' : 'none';
        }

        // Función para enviar un comentario
        function submitComment(postId) {
            let commentText = document.getElementById('comment-input-' + postId).value;

            if (commentText) {
                // Enviar el comentario a través de AJAX
                fetch('feed.php', {
                    method: 'POST',
                    body: JSON.stringify({ comentario: commentText, postId: postId }),
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        // Agregar el nuevo comentario a la vista
                        let commentContainer = document.getElementById('comments_' + postId);
                        let newComment = document.createElement('div');
                        newComment.classList.add('comment');
                        newComment.innerHTML = `
                            <div class="comment-header">
                                <span>${data.nombre}</span> <small>${data.fecha}</small>
                            </div>
                            <p>${data.contenido}</p>
                        `;
                        commentContainer.appendChild(newComment);
                        
                        // Limpiar el campo de entrada
                        document.getElementById('comment-input-' + postId).value = '';
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
        <form action="feed.php" method="post" enctype="multipart/form-data">
            <textarea name="contenido" placeholder="Escribe tu publicación..."></textarea>

            <!-- Botón para seleccionar imagen -->
            <label for="imagenUpload" class="file-label">
                <i class="fas fa-image"></i> Subir Imagen
            </label>
            <input type="file" id="imagenUpload" name="imagen" accept="image/*" style="display: none;">

            <!-- Botón para seleccionar video -->
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
                        <button class="like-btn <?php echo ($fila['user_liked'] > 0) ? 'liked' : ''; ?>" onclick="toggleLike(<?php echo $fila['Id_publicacion']; ?>, this)">
                            <i class="fas fa-heart" style="color: <?php echo ($fila['user_liked'] > 0) ? '#e74c3c' : '#fff'; ?>;"></i>
                            <div class="likes-count" id="likes-count-<?php echo $fila['Id_publicacion']; ?>"><?php echo $fila['likes_count']; ?></div>
                        </button>

                        <button class="comment-btn" onclick="toggleCommentBox(<?php echo $fila['Id_publicacion']; ?>)">
                            <i class="fas fa-comment"></i> Comentar
                        </button>
                        <button class="share-btn">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                    </div>

                    <div class="comments" id="comments_<?php echo $fila['Id_publicacion']; ?>">
                        <!-- Aquí se mostrarán los comentarios -->
                    </div>

                    <div class="comment-input-container" id="comment-box-<?php echo $fila['Id_publicacion']; ?>" style="display:none;">
                        <textarea class="comment-input" id="comment-input-<?php echo $fila['Id_publicacion']; ?>" placeholder="Escribe un comentario..." rows="3"></textarea>
                        <button class="submit-comment" onclick="submitComment(<?php echo $fila['Id_publicacion']; ?>)">Comentar</button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

</body>
</html>





