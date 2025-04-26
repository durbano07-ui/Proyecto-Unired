<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Obtener las publicaciones guardadas por el usuario
$sql = "SELECT p.Id_publicacion, p.Contenido, p.Tipo, p.evento_titulo, p.evento_fecha, 
               p.evento_hora, p.evento_ubicacion, p.evento_descripcion, 
               u.Nombre, u.Foto_Perfil, p.Fecha_Publicacion, p.Imagen_url, p.Video_url, p.Id_usuario,
               (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion) AS likes_count,
               (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion AND Id_usuario = ?) AS user_liked,
               pg.Fecha_guardado
        FROM publicaciones_guardadas pg
        JOIN publicaciones p ON pg.Id_publicacion = p.Id_publicacion
        JOIN usuarios u ON p.Id_usuario = u.Id_usuario 
        WHERE pg.Id_usuario = ? 
        ORDER BY pg.Fecha_guardado DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $_SESSION['Id_usuario'], $_SESSION['Id_usuario']);
$stmt->execute();
$resultado = $stmt->get_result();

if (!$resultado) {
    die("Error al obtener publicaciones guardadas: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicaciones Guardadas - Red Social</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>
    <style>
        /* Estilos para la página de guardados */
        .active {
            background-color: rgba(255, 255, 255, 0.1);
            font-weight: bold;
        }
        
        .container h2 {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .container h2 i {
            color: #1da1f2;
        }
        
        .no-guardados {
            text-align: center;
            padding: 50px 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .no-guardados p {
            color: #657786;
            margin: 5px 0;
            font-size: 16px;
        }
        
        .volver-feed {
            display: inline-block;
            margin-top: 20px;
            background-color: #1da1f2;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .volver-feed:hover {
            background-color: #0d8ddf;
        }
        
        /* Estilos para la fecha de guardado */
        .guardado-badge {
            position: absolute;
            top: 10px;
            right: 50px;
            background-color: rgba(29, 161, 242, 0.1);
            color: #1da1f2;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Estilo para el botón de desguardar */
        .desguardar-btn {
            background-color: #f0f2f5;
            color: #657786;
            border: 1px solid #e1e8ed;
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 5px;
            width: fit-content;
        }
        
        .desguardar-btn:hover {
            background-color: #e1e8ed;
            color: #e0245e;
        }
        
        .desguardar-btn i {
            font-size: 14px;
        }
    </style>
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
        <li><a href="guardados.php" class="active"><i class="fas fa-bookmark"></i> <span>Guardados</span></a></li>
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
    <h2><i class="fas fa-bookmark"></i> Publicaciones Guardadas</h2>
    
    <?php if ($resultado->num_rows == 0): ?>
    <div class="no-guardados">
        <i class="fas fa-bookmark" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
        <p>Aún no has guardado ninguna publicación.</p>
        <p>Cuando guardes una publicación, aparecerá aquí.</p>
        <a href="feed.php" class="volver-feed">Volver al inicio</a>
    </div>
    <?php else: ?>
    <div id="feed">
        <?php while ($fila = $resultado->fetch_assoc()): ?>
            <?php
            $foto_perfil = isset($fila['Foto_Perfil']) && !empty($fila['Foto_Perfil']) ? $fila['Foto_Perfil'] : 'uploads/default.jpg';
            $es_evento = ($fila['Tipo'] === 'evento');
            $fecha_guardado = date('j M, Y', strtotime($fila['Fecha_guardado']));
            ?>
            <div class='publicacion <?php echo $es_evento ? 'publicacion-evento' : ''; ?>' id='post_<?php echo $fila['Id_publicacion']; ?>'>
                <!-- Insignia de guardado -->
                <div class="guardado-badge">
                    <i class="fas fa-bookmark"></i> Guardado el <?php echo $fecha_guardado; ?>
                </div>
                
                <div class="post-content" onclick="window.location.href='detalle.php?id=<?php echo $fila['Id_publicacion']; ?>'">
                    <div class="header">
                        <div class="header-left">
                            <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" class="foto-perfil">
                            <div class="user-info">
                                <a href="perfil.php?id=<?php echo $fila['Id_usuario']; ?>" class="user-name" onclick="event.stopPropagation();"><?php echo htmlspecialchars($fila['Nombre']); ?></a>
                                <span class="post-time"><?php echo date('j M, Y', strtotime($fila['Fecha_Publicacion'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($es_evento): ?>
                    <div class="evento-container">
                        <?php if (!empty($fila['evento_titulo'])): ?>
                            <div class="evento-badge"><i class="fas fa-calendar-alt"></i> Evento</div>
                            <h3 class="evento-titulo"><?php echo htmlspecialchars($fila['evento_titulo']); ?></h3>
                        <?php endif; ?>
                        
                        <div class="evento-detalles">
                            <?php if (!empty($fila['evento_fecha'])): ?>
                            <div class="evento-fecha-hora">
                                <i class="far fa-calendar"></i>
                                <span><?php echo date('d/m/Y', strtotime($fila['evento_fecha'])); ?></span>
                                <?php if (!empty($fila['evento_hora'])): ?>
                                <i class="far fa-clock"></i>
                                <span><?php echo date('H:i', strtotime($fila['evento_hora'])); ?> hrs</span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($fila['evento_ubicacion'])): ?>
                            <div class="evento-ubicacion">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($fila['evento_ubicacion']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($fila['evento_descripcion'])): ?>
                        <div class="evento-descripcion">
                            <?php echo htmlspecialchars($fila['evento_descripcion']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <a href="detalle.php?id=<?php echo $fila['Id_publicacion']; ?>" class="evento-participar" onclick="event.stopPropagation();">
                            <i class="fas fa-info-circle"></i> Ver detalles del evento
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <p><?php echo htmlspecialchars($fila['Contenido']); ?></p>

                    <?php if (!empty($fila['Imagen_url'])): ?>
                        <img src='<?php echo htmlspecialchars($fila['Imagen_url']); ?>' alt='Imagen'>
                    <?php endif; ?>
                    <?php if (!empty($fila['Video_url'])): ?>
                        <video controls onclick="event.stopPropagation();">
                            <source src='<?php echo htmlspecialchars($fila['Video_url']); ?>' type='video/mp4'>
                        </video>
                    <?php endif; ?>
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
                    
                    <!-- Botón específico para desguardar -->
                    <button class="desguardar-btn" onclick="event.stopPropagation(); desguardarPublicacion(<?php echo $fila['Id_publicacion']; ?>)">
                        <i class="fas fa-bookmark"></i> Desguardar
                    </button>
                </div>

                <div class="opciones-menu" onclick="event.stopPropagation();">
                    <button class="opciones-btn" onclick="event.stopPropagation(); toggleOpciones(<?php echo $fila['Id_publicacion']; ?>)">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <div class="opciones-lista" id="opciones-<?php echo $fila['Id_publicacion']; ?>" style="display:none;">
                        <button onclick="event.stopPropagation(); desguardarPublicacion(<?php echo $fila['Id_publicacion']; ?>)">Desguardar</button>
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
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Función para desguardar una publicación
function desguardarPublicacion(postId) {
    if (confirm('¿Estás seguro de que quieres quitar esta publicación de tus guardados?')) {
        fetch('guardar_publicacion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_publicacion: postId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.action === 'removed') {
                // Eliminar la publicación de la interfaz
                const post = document.getElementById(`post_${postId}`);
                post.remove();
                
                // Si no quedan publicaciones, mostrar mensaje de "no guardados"
                const feed = document.getElementById('feed');
                if (feed.children.length === 0) {
                    location.reload(); // Recargar para mostrar el mensaje de que no hay guardados
                }
            } else {
                alert('Error al desguardar la publicación');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al desguardar la publicación');
        });
    }
}
</script>

</body>
</html>