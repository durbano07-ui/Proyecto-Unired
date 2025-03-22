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

if (!$resultado) {
    die("Error al obtener publicaciones: " . $conn->error);
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
    <script src="script.js"></script>
</head>
<body>
<div class="sidebar">
    <div class="logo">
        <i class="fas fa-share-nodes"></i>
    </div>
    
    <ul class="nav-items">
        <li><a href="feed.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
        <li><a href="explore.php"><i class="fas fa-search"></i> <span>Explorar</span></a></li>
        <li><a href="notificaciones.php"><i class="fas fa-bell"></i> <span>Notificaciones</span></a></li>
        <li><a href="mensajes/msg.html"><i class="fas fa-envelope"></i> <span>Mensajes</span></a></li>
        <li><a href="guardados.php"><i class="fas fa-bookmark"></i> <span>Guardados</span></a></li>
        <li><a href="comunidades.php"><i class="fas fa-users"></i> <span>Comunidades</span></a></li>
        <li><a href="perfil.php"><i class="fas fa-user"></i> <span>Perfil</span></a></li>
        <li><a href="configuracion.php"><i class="fas fa-cog"></i> <span>Configuración</span></a></li>
    </ul>
    
    <div class="user-profile">
        <a href="perfil.php" class="user-link">
            <img src="<?php echo isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : 'uploads/default.jpg'; ?>" alt="Perfil" class="user-avatar">
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['nombre']; ?></div>
                <div class="user-handle">@<?php echo strtolower($_SESSION['nombre']); ?></div>
            </div>
        </a>
        <a href="logout.php" class="logout-icon"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<div class="container">
    <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?> </h2>

    <h2>Crear Publicación</h2>
    <form action="feed.php" method="post" enctype="multipart/form-data">
        <textarea name="contenido" placeholder="Escribe tu publicación..."></textarea>

        <label for="imagenUpload" class="file-label">
            <i class="fas fa-image"></i>
        </label>
        <input type="file" id="imagenUpload" name="imagen" accept="image/*" style="display: none;">

        <label for="videoUpload" class="file-label">
            <i class="fas fa-video"></i>
        </label>
        <input type="file" id="videoUpload" name="video" accept="video/*" style="display: none;">

        <button type="submit">Publicar</button>
    </form>

    <h3>Publicaciones</h3>
    <div id="feed">
        <?php while ($fila = $resultado->fetch_assoc()) {
            $foto_perfil = isset($fila['Foto_Perfil']) && !empty($fila['Foto_Perfil']) ? $fila['Foto_Perfil'] : 'uploads/default.jpg';
            
        ?>
            <div class='publicacion' id='post_<?php echo $fila['Id_publicacion']; ?>'>
                <div class="post-content" onclick="window.location.href='detalle.php?id=<?php echo $fila['Id_publicacion']; ?>'">
                    <div class="header">
                        <div class="header-left">
                        <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" class="foto-perfil">
                            <p><strong><a href="perfil.php?id=<?php echo $fila['Id_usuario']; ?>" onclick="event.stopPropagation();"><?php echo htmlspecialchars($fila['Nombre']); ?></a></strong></p>
                            <small><?php echo $fila['Fecha_Publicacion']; ?></small>
                        </div>
                    </div>
                    <p><?php echo htmlspecialchars($fila['Contenido']); ?></p>

                    <?php if (!empty($fila['Imagen_url'])) { ?>
                        <img src='<?php echo htmlspecialchars($fila['Imagen_url']); ?>' alt='Imagen'>
                    <?php } ?>
                    <?php if (!empty($fila['Video_url'])) { ?>
                        <video controls onclick="event.stopPropagation();">
                            <source src='<?php echo htmlspecialchars($fila['Video_url']); ?>' type='video/mp4'>
                        </video>
                    <?php } ?>
                </div>

                <div class='acciones' onclick="event.stopPropagation();">
                    <button class="like-btn <?php echo ($fila['user_liked'] > 0) ? 'liked' : ''; ?>"
                            onclick="event.stopPropagation(); toggleLike(<?php echo $fila['Id_publicacion']; ?>, this)">
                        <i class="fas fa-heart" style="color: <?php echo ($fila['user_liked'] > 0) ? '#e74c3c' : '#fff'; ?>;"></i>
                        <div class="likes-count" id="likes-count-<?php echo $fila['Id_publicacion']; ?>"><?php echo $fila['likes_count']; ?></div>
                    </button>

                    <button class="comment-btn" data-post-id="<?php echo $fila['Id_publicacion']; ?>" onclick="event.stopPropagation(); toggleCommentBox(<?php echo $fila['Id_publicacion']; ?>)">
                        <i class="fas fa-comment"></i> Comentar
                    </button>

                    <button class="share-btn" onclick="event.stopPropagation(); sharePost(<?php echo $fila['Id_publicacion']; ?>)">
                        <i class="fas fa-share"></i> Compartir
                    </button>
                </div>

                <div class="opciones-menu" onclick="event.stopPropagation();">
                    <button class="opciones-btn" onclick="event.stopPropagation(); toggleOpciones(<?php echo $fila['Id_publicacion']; ?>)">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                <div class="opciones-lista" id="opciones-<?php echo $fila['Id_publicacion']; ?>" style="display:none;">
                        <?php if ($fila['Id_usuario'] == $_SESSION['Id_usuario']): ?>
                            <button onclick="event.stopPropagation(); eliminarPublicacion(<?php echo $fila['Id_publicacion']; ?>)">Eliminar</button>
                            <button onclick="event.stopPropagation(); editarPublicacion(<?php echo $fila['Id_publicacion']; ?>)">Editar</button>
                        <?php endif; ?>
                        <button onclick="event.stopPropagation(); guardarPublicacion(<?php echo $fila['Id_publicacion']; ?>)">Guardar</button>
                    </div>
                </div>
                <div class="comments" id="comments_<?php echo $fila['Id_publicacion']; ?>" onclick="event.stopPropagation();">
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
                                </div>
                                <p>{$comentario['Contenido_C']}</p>
                            </div>";
                    }
                    ?>
                </div>

                <div class="comment-input-container" id="comment-box-<?php echo $fila['Id_publicacion']; ?>" style="display:none;" onclick="event.stopPropagation();">
                    <textarea id="comment-input-<?php echo $fila['Id_publicacion']; ?>" class="comment-input" placeholder="Escribe un comentario..." rows="3" onclick="event.stopPropagation();"></textarea>
                    <button class="submit-comment" onclick="event.stopPropagation(); submitComment(<?php echo $fila['Id_publicacion']; ?>)">Comentar</button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
</body>
</html>
