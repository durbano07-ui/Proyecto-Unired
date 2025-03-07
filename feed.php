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

        // Repostear (compartir) la publicación
        function sharePost(postId) {
            fetch('guardar_publicacion.php', {
                method: 'POST',
                body: JSON.stringify({ id_publicacion: postId }),
                headers: { 'Content-Type': 'application/json' }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      alert('Publicación compartida correctamente');
                      location.reload(); // Recargar el feed para mostrar la nueva publicación
                  } else {
                      alert('Error al compartir la publicación');
                  }
              });
        }
    </script>
</head>
<body>

    <div class="container">
        <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?> </h2>
        <a href="logout.php">Cerrar Sesión</a>
        
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

                        <button class="share-btn" onclick="sharePost(<?php echo $fila['Id_publicacion']; ?>)">
                            <i class="fas fa-share"></i> Compartir
                        </button>
                    </div>

                    <div class="comments" id="comments_<?php echo $fila['Id_publicacion']; ?>">
                        <!-- Aquí irán los comentarios, como ya lo tenías -->
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

</body>
</html>



