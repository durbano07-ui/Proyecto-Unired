<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Procesar la creación de la publicación
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contenido'])) {
    $contenido = $_POST['contenido'];
    $usuarioId = $_SESSION['Id_usuario'];

    // Manejar la carga de archivos (imagen y video)
    $imagen_url = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen_url = 'uploads/' . basename($_FILES['imagen']['name']);
        move_uploaded_file($_FILES['imagen']['tmp_name'], $imagen_url);
    }

    $video_url = null;
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $video_url = 'uploads/' . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], $video_url);
    }

    $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion) VALUES (?, ?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("isss", $usuarioId, $contenido, $imagen_url, $video_url);

    if ($stmtInsert->execute()) {
        header("Location: feed.php"); // Redirigir para mostrar la nueva publicación
        exit();
    } else {
        echo "Error al crear la publicación.";
    }
}

// Obtener las publicaciones
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
        // Función para manejar el like
        function toggleLike(postId, likeBtn) {
            let likesCount = document.getElementById('likes-count-' + postId);
            let heartIcon = likeBtn.querySelector('i');

            // Cambiar el color del corazón al darle like
            if (heartIcon.style.color === 'rgb(231, 76, 60)') {
                heartIcon.style.color = '';
                likesCount.innerHTML = parseInt(likesCount.innerHTML) - 1;
            } else {
                heartIcon.style.color = '#e74c3c'; // Color rojo
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

        // Función para enviar el comentario
        function submitComment(postId) {
            const commentText = document.getElementById('comment-input-' + postId).value.trim();

            if (commentText !== '') {
                // Realizar una solicitud AJAX para guardar el comentario
                fetch('comentar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: postId,
                        comentario: commentText
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Crear el nuevo comentario en el HTML
                        let commentContainer = document.getElementById('comments_' + postId);
                        let newComment = document.createElement('div');
                        newComment.classList.add('comment');
                        newComment.innerHTML = `
                            <div class="comment-header">
                                <strong>${data.nombre}</strong> <small>${data.fecha}</small>
                            </div>
                            <p>${data.contenido}</p>
                        `;
                        commentContainer.appendChild(newComment);

                        // Limpiar el input del comentario
                        document.getElementById('comment-input-' + postId).value = '';
                    } else {
                        alert(data.message); // Mostrar mensaje de error
                    }
                })
                .catch(error => {
                    console.error('Error al enviar el comentario:', error);
                });
            }
        }

        // Función para manejar el repost (compartir la publicación)
        function sharePost(postId) {
            fetch('compartir.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Agregar el repost al feed
                    let feed = document.getElementById('feed');
                    let repost = document.createElement('div');
                    repost.classList.add('publicacion');
                    repost.innerHTML = `
                        <div class="header">
                            <div class="header-left">
                                <p><strong>${data.nombre} (Repost)</strong></p>
                                <small>${data.fecha}</small>
                            </div>
                        </div>
                        <p>${data.contenido}</p>
                        ${data.imagen ? `<img src="${data.imagen}" alt="Imagen">` : ''}
                        ${data.video ? `<video controls><source src="${data.video}" type="video/mp4"></video>` : ''}
                    `;
                    feed.prepend(repost); // Insertar el repost al inicio del feed
                } else {
                    alert('Error al compartir la publicación.');
                }
            })
            .catch(error => console.error('Error al compartir:', error));
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
                        <button class="share-btn" onclick="sharePost(<?php echo $fila['Id_publicacion']; ?>)">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                    </div>

                    <div class="comments" id="comments_<?php echo $fila['Id_publicacion']; ?>">
                        <?php
                        $comentariosQuery = "SELECT c.Id_comentario, c.Comentario, u.Nombre, c.Fecha_Comentario 
                                             FROM comentarios c 
                                             JOIN usuarios u ON c.Id_usuario = u.Id_usuario 
                                             WHERE c.Id_publicacion = ? ORDER BY c.Fecha_Comentario ASC";
                        $comentariosStmt = $conn->prepare($comentariosQuery);
                        $comentariosStmt->bind_param("i", $fila['Id_publicacion']);
                        $comentariosStmt->execute();
                        $comentarios = $comentariosStmt->get_result();
                        
                        while ($comentario = $comentarios->fetch_assoc()) {
                            echo "<div class='comment'>";
                            echo "<div class='comment-header'><strong>" . htmlspecialchars($comentario['Nombre']) . "</strong> <small>" . $comentario['Fecha_Comentario'] . "</small></div>";
                            echo "<p>" . htmlspecialchars($comentario['Comentario']) . "</p>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <div class="comment-box" id="comment-box-<?php echo $fila['Id_publicacion']; ?>" style="display: none;">
                        <textarea id="comment-input-<?php echo $fila['Id_publicacion']; ?>" placeholder="Escribe un comentario..."></textarea>
                        <button onclick="submitComment(<?php echo $fila['Id_publicacion']; ?>)">Enviar</button>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

</body>
</html>



