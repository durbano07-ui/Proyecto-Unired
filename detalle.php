<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID de publicación inválido.";
    exit();
}

$postId = $_GET['id'];

$database = new Database();
$conn = $database->getConnection();

// Obtener detalles de la publicación
$sql = "SELECT p.Id_publicacion, p.Contenido, u.Nombre, u.Foto_Perfil, p.Fecha_Publicacion, p.Imagen_url, p.Video_url, p.Id_usuario,
        (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion) AS likes_count,
        (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion AND Id_usuario = ?) AS user_liked
        FROM publicaciones p 
        JOIN usuarios u ON p.Id_usuario = u.Id_usuario 
        WHERE p.Id_publicacion = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $_SESSION['Id_usuario'], $postId);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
} else {
    echo "No se encontraron detalles para esta publicación.";
    exit();
}

// Obtener los comentarios de la publicación
$sqlComentarios = "SELECT c.Id_comentario, c.Contenido_C, u.Nombre, u.Foto_Perfil, c.Fecha_Comentario 
                   FROM comentarios c
                   JOIN usuarios u ON c.Id_usuario = u.Id_usuario
                   WHERE c.Id_publicacion = ?
                   ORDER BY c.Fecha_Comentario DESC";

$stmtComentarios = $conn->prepare($sqlComentarios);
$stmtComentarios->bind_param("i", $postId);
$stmtComentarios->execute();
$comentariosResultado = $stmtComentarios->get_result();

// Formatear la fecha
$fecha_publicacion = new DateTime($fila['Fecha_Publicacion']);
$fecha_formateada = $fecha_publicacion->format('j M, Y · g:i A');

$foto_perfil = !empty($fila['Foto_Perfil']) ? $fila['Foto_Perfil'] : 'uploads/default.jpg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicación de <?php echo htmlspecialchars($fila['Nombre']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: rgb(94, 211, 166);
            --secondary-color: #2f3e47;
            --accent-color: #5e8dd3;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-color: #333;
            --light-text: #f8f9fa;
            --border-color: #dedede;
            --hover-color: rgba(94, 211, 166, 0.1);
        }

        body {
            background-color: var(--light-bg);
        }

        /* Contenedor principal con efecto de tarjeta */
        .post-detail-container {
            width: 650px;
            margin-left: 280px;
            background: linear-gradient(145deg, #ffffff, #f5f5f5);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-top: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        /* Efecto de degradado en la parte superior */
        .post-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            position: relative;
        }

        .post-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(to bottom, rgba(0,0,0,0.05), transparent);
        }

        .post-header h1 {
            font-size: 22px;
            margin: 0;
            font-weight: 600;
        }

        .back-button {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        /* Contenido principal de la publicación */
        .post-detail {
            padding: 25px;
        }

        .post-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 56px;
            height: 56px;
            border-radius: 20px;
            margin-right: 15px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 3px solid white;
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: bold;
            font-size: 18px;
            color: var(--secondary-color);
        }

        .author-username {
            color: #6c757d;
            font-size: 14px;
        }

        /* Contenido de la publicación con estilo mejorado */
        .post-content {
            font-size: 22px;
            line-height: 1.5;
            margin: 25px 0;
            color: var(--text-color);
            font-weight: 400;
            letter-spacing: 0.01em;
        }

        /* Contenedor de media con bordes redondeados y sombra sutil */
        .post-media {
            margin: 20px 0;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background-color: #f7f7f7;
        }

        .post-media img, .post-media video {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            display: block;
        }

        /* Información de fecha con icono */
        .post-date {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 14px;
            margin: 20px 0;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .post-date i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        /* Estadísticas de interacción con iconos y colores distintivos */
        .post-stats {
            display: flex;
            justify-content: space-around;
            padding: 15px 0;
            margin-bottom: 20px;
            background-color: rgba(94, 211, 166, 0.05);
            border-radius: 15px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--secondary-color);
        }

        .stat-icon {
            font-size: 22px;
            margin-bottom: 5px;
            color: var(--primary-color);
        }

        .stat-count {
            font-weight: bold;
            font-size: 18px;
        }

        .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Botones de acción en contenedor con efecto de vidrio */
        .post-actions {
            display: flex;
            justify-content: space-around;
            padding: 15px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .action-button {
            background: none;
            border: none;
            color: var(--secondary-color);
            font-size: 20px;
            cursor: pointer;
            padding: 10px;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
        }

        .action-button:hover {
            background-color: var(--hover-color);
            transform: translateY(-2px);
        }

        .action-button.liked {
            color: #e74c3c;
        }

        .action-button.liked:hover {
            background-color: rgba(231, 76, 60, 0.1);
        }

        /* Formulario de comentario con diseño minimalista */
        .comment-form {
            display: flex;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .comment-form img {
            width: 45px;
            height: 45px;
            border-radius: 15px;
            margin-right: 15px;
            object-fit: cover;
        }

        .comment-input-container {
            flex-grow: 1;
        }

        .comment-input {
            width: 100%;
            border: none;
            border-bottom: 2px solid var(--border-color);
            outline: none;
            font-size: 16px;
            padding: 10px 0;
            transition: border-color 0.3s;
            background: transparent;
        }

        .comment-input:focus {
            border-color: var(--primary-color);
        }

        .comment-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            float: right;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 14px;
        }

        .comment-button:hover {
            background-color: #4aa386;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(94, 211, 166, 0.3);
        }

        /* Sección de comentarios con estilo distintivo */
        .comments-header {
            font-size: 18px;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 15px;
            padding-left: 25px;
            display: flex;
            align-items: center;
        }

        .comments-header i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .comments-section {
            padding: 0 25px 25px;
        }

        .comment {
            display: flex;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }

        .comment:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            margin-right: 15px;
            object-fit: cover;
        }

        .comment-content {
            flex-grow: 1;
        }

        .comment-author {
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            margin-bottom: 5px;
        }

        .comment-name {
            font-weight: bold;
            color: var(--secondary-color);
            margin-right: 8px;
        }

        .comment-username {
            color: #6c757d;
            font-size: 14px;
            margin-right: 8px;
        }

        .comment-date {
            color: #6c757d;
            font-size: 13px;
            margin-left: auto;
        }

        .comment-text {
            color: var(--text-color);
            line-height: 1.4;
        }

        .no-comments {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        /* Right sidebar con estilo único */
        .right-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: -5px 0 15px rgba(0,0,0,0.05);
            overflow-y: auto;
        }

        .search-box {
            display: flex;
            align-items: center;
            background-color: var(--light-bg);
            border-radius: 20px;
            padding: 10px 15px;
            margin-bottom: 20px;
        }

        .search-box i {
            color: #6c757d;
            margin-right: 10px;
        }

        .search-box input {
            width: 100%;
            border: none;
            background: transparent;
            outline: none;
            font-size: 15px;
        }

        /* Animaciones y efectos adicionales */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .liked .fa-heart {
            animation: pulse 0.3s ease-in-out;
        }

        /* Responsividad */
        @media screen and (max-width: 1200px) {
            .post-detail-container {
                margin-left: 100px;
                width: calc(100% - 400px);
            }
        }

        @media screen and (max-width: 992px) {
            .right-sidebar {
                display: none;
            }
            
            .post-detail-container {
                width: calc(100% - 120px);
            }
        }

        @media screen and (max-width: 768px) {
            .post-detail-container {
                margin-left: 80px;
                width: calc(100% - 100px);
                border-radius: 0;
            }
            
            .post-header {
                padding: 15px;
            }
            
            .post-detail {
                padding: 15px;
            }
            
            .comments-section {
                padding: 0 15px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Mantener el sidebar existente -->
        <div class="logo">
            <i class="fas fa-share-nodes"></i>
        </div>
        
        <ul class="nav-items">
            <li><a href="feed.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
            <li><a href="explore.php"><i class="fas fa-search"></i> <span>Explorar</span></a></li>
            <li><a href="notificaciones.php"><i class="fas fa-bell"></i> <span>Notificaciones</span></a></li>
            <li><a href="mensajes.php"><i class="fas fa-envelope"></i> <span>Mensajes</span></a></li>
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

    <div class="post-detail-container">
        <!-- Cabecera con degradado -->
        <div class="post-header">
            <a href="feed.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
            <h1>Detalle de la Publicación</h1>
        </div>

        <!-- Detalle de la publicación -->
        <div class="post-detail">
            <div class="post-author">
                <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="<?php echo htmlspecialchars($fila['Nombre']); ?>" class="author-avatar">
                <div class="author-info">
                    <span class="author-name"><?php echo htmlspecialchars($fila['Nombre']); ?></span>
                    <span class="author-username">@<?php echo strtolower($fila['Nombre']); ?></span>
                </div>
            </div>

            <div class="post-content">
                <?php echo htmlspecialchars($fila['Contenido']); ?>
            </div>

            <?php if (!empty($fila['Imagen_url'])) { ?>
                <div class="post-media">
                    <img src="<?php echo htmlspecialchars($fila['Imagen_url']); ?>" alt="Imagen de la publicación">
                </div>
            <?php } ?>

            <?php if (!empty($fila['Video_url'])) { ?>
                <div class="post-media">
                    <video controls>
                        <source src="<?php echo htmlspecialchars($fila['Video_url']); ?>" type="video/mp4">
                        Tu navegador no soporta videos HTML5.
                    </video>
                </div>
            <?php } ?>

            <div class="post-date">
                <i class="far fa-clock"></i> <?php echo $fecha_formateada; ?>
            </div>

            <!-- Estadísticas de interacción -->
            <div class="post-stats">
                <div class="stat-item">
                    <div class="stat-icon"><i class="far fa-comment"></i></div>
                    <div class="stat-count"><?php echo $comentariosResultado->num_rows; ?></div>
                    <div class="stat-label">Comentarios</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-retweet"></i></div>
                    <div class="stat-count">0</div>
                    <div class="stat-label">Compartidos</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="far fa-heart"></i></div>
                    <div class="stat-count"><?php echo $fila['likes_count']; ?></div>
                    <div class="stat-label">Me gusta</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="far fa-eye"></i></div>
                    <div class="stat-count">0</div>
                    <div class="stat-label">Vistas</div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="post-actions">
                <button class="action-button" id="comment-button" title="Comentar">
                    <i class="far fa-comment"></i>
                </button>
                <button class="action-button" id="retweet-button" title="Compartir">
                    <i class="fas fa-retweet"></i>
                </button>
                <button class="action-button <?php echo ($fila['user_liked'] > 0) ? 'liked' : ''; ?>" 
                        id="like-button" 
                        onclick="toggleLike(<?php echo $fila['Id_publicacion']; ?>, this)"
                        title="Me gusta">
                    <i class="<?php echo ($fila['user_liked'] > 0) ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
                <button class="action-button" id="bookmark-button" title="Guardar">
                    <i class="far fa-bookmark"></i>
                </button>
                <button class="action-button" id="share-button" 
                        onclick="sharePost(<?php echo $fila['Id_publicacion']; ?>)"
                        title="Compartir enlace">
                    <i class="fas fa-share-alt"></i>
                </button>
            </div>

            <!-- Formulario para comentar -->
            <div class="comment-form">
                <img src="<?php echo isset($_SESSION['foto_perfil']) ? $_SESSION['foto_perfil'] : 'uploads/default.jpg'; ?>" alt="Tu perfil">
                <div class="comment-input-container">
                    <textarea id="comment-input" class="comment-input" placeholder="¿Qué piensas sobre esta publicación?"></textarea>
                    <button class="comment-button" onclick="submitComment(<?php echo $fila['Id_publicacion']; ?>)">
                        Comentar
                    </button>
                </div>
            </div>

            <!-- Título de la sección de comentarios -->
            <div class="comments-header">
                <i class="fas fa-comments"></i> Comentarios (<?php echo $comentariosResultado->num_rows; ?>)
            </div>
        </div>

        <!-- Sección de comentarios -->
        <div class="comments-section" id="comments-section">
            <?php
            if ($comentariosResultado->num_rows > 0) {
                while ($comentario = $comentariosResultado->fetch_assoc()) {
                    $comentario_foto = !empty($comentario['Foto_Perfil']) ? $comentario['Foto_Perfil'] : 'uploads/default.jpg';
                    $fecha_comentario = new DateTime($comentario['Fecha_Comentario']);
                    $fecha_comentario_formateada = $fecha_comentario->format('j M, Y');
            ?>
                <div class="comment" id="comment-<?php echo $comentario['Id_comentario']; ?>">
                    <img src="<?php echo htmlspecialchars($comentario_foto); ?>" alt="<?php echo htmlspecialchars($comentario['Nombre']); ?>" class="comment-avatar">
                    <div class="comment-content">
                        <div class="comment-author">
                            <span class="comment-name"><?php echo htmlspecialchars($comentario['Nombre']); ?></span>
                            <span class="comment-username">@<?php echo strtolower($comentario['Nombre']); ?></span>
                            <span class="comment-date"><?php echo $fecha_comentario_formateada; ?></span>
                        </div>
                        <div class="comment-text">
                            <?php echo htmlspecialchars($comentario['Contenido_C']); ?>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
            ?>
                <div class="no-comments">
                    <p>Aún no hay comentarios. ¡Sé el primero en expresar tu opinión!</p>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <!-- Barra lateral derecha -->
    <div class="right-sidebar">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Buscar en la red">
        </div>
        
        <div class="trending-section">
            <h3>Destacado para ti</h3>
            <!-- Aquí puedes mostrar contenido destacado -->
        </div>
        
        <div class="suggested-users">
            <h3>Personas que quizás conozcas</h3>
            <!-- Usuarios sugeridos -->
        </div>
    </div>

    <script>
        // Función para dar/quitar me gusta
        function toggleLike(postId, button) {
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => {
                if (response.ok) {
                    // Cambiar el estilo del botón
                    button.classList.toggle('liked');
                    
                    // Cambiar el ícono
                    const icon = button.querySelector('i');
                    if (icon.classList.contains('far')) {
                        icon.classList.replace('far', 'fas');
                    } else {
                        icon.classList.replace('fas', 'far');
                    }
                    
                    // Actualizar contador
                    const statItem = document.querySelector('.stat-item:nth-child(3)');
                    if (statItem) {
                        const countElement = statItem.querySelector('.stat-count');
                        let count = parseInt(countElement.textContent);
                        
                        if (button.classList.contains('liked')) {
                            countElement.textContent = count + 1;
                        } else {
                            countElement.textContent = Math.max(0, count - 1);
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Función para enviar comentario
        function submitComment(postId) {
            const comentario = document.getElementById('comment-input').value.trim();
            
            if (comentario === '') {
                return;
            }
            
            fetch('comentar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: postId,
                    comentario: comentario
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Crear nuevo comentario
                    const commentsSection = document.getElementById('comments-section');
                    
                    // Quitar mensaje de "no hay comentarios" si existe
                    const noComments = commentsSection.querySelector('.no-comments');
                    if (noComments) {
                        noComments.remove();
                    }
                    
                    // Crear elemento de comentario
                    const commentElement = document.createElement('div');
                    commentElement.className = 'comment';
                    commentElement.id = `comment-${data.comment_id}`;
                    
                    const currentUserAvatar = document.querySelector('.comment-form img').src;
                    
                    // Formato actualizado para el nuevo comentario
                    commentElement.innerHTML = `
                        <img src="${currentUserAvatar}" alt="${data.nombre}" class="comment-avatar">
                        <div class="comment-content">
                            <div class="comment-author">
                                <span class="comment-name">${data.nombre}</span>
                                <span class="comment-username">@${data.nombre.toLowerCase()}</span>
                                <span class="comment-date">Justo ahora</span>
                            </div>
                            <div class="comment-text">
                                ${data.contenido}
                            </div>
                        </div>
                    `;
                    
                    // Añadir el comentario al inicio
                    commentsSection.insertBefore(commentElement, commentsSection.firstChild);
                    
                    // Limpiar el campo de texto
                    document.getElementById('comment-input').value = '';
                    
                    // Actualizar contador de comentarios
                    const commentCount = document.querySelector('.comments-header').textContent;
                    const countMatch = commentCount.match(/\((\d+)\)/);
                    if (countMatch) {
                        const currentCount = parseInt(countMatch[1]);
                        const newCount = currentCount + 1;
                        document.querySelector('.comments-header').innerHTML = `<i class="fas fa-comments"></i> Comentarios (${newCount})`;
                    }
                    
                    // Actualizar también en las estadísticas
                    const commentStat = document.querySelector('.stat-item:first-child .stat-count');
                    if (commentStat) {
                        let count = parseInt(commentStat.textContent);
                        commentStat.textContent = count + 1;
                    }
                    
                    // Efecto de aparición
// Efecto de aparición
commentElement.style.opacity = '0';
                    setTimeout(() => {
                        commentElement.style.opacity = '1';
                        commentElement.style.transition = 'opacity 0.3s ease-in';
                    }, 10);
                } else {
                    alert(data.message || 'Error al comentar');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Función para compartir publicación
        function sharePost(postId) {
            // Obtener la URL actual
            const postUrl = window.location.origin + window.location.pathname + '?id=' + postId;
            
            // Intentar usar la API de compartir si está disponible
            if (navigator.share) {
                navigator.share({
                    title: 'Publicación compartida',
                    url: postUrl
                }).catch(err => {
                    console.error('Error al compartir:', err);
                    // Fallback
                    prompt('Copia este enlace para compartir la publicación:', postUrl);
                });
            } else {
                // Fallback para navegadores que no soportan la API Share
                prompt('Copia este enlace para compartir la publicación:', postUrl);
            }
        }

        // Función para el botón de guardar (bookmark)
        document.getElementById('bookmark-button').addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            if (icon.classList.contains('far')) {
                icon.classList.replace('far', 'fas');
                this.title = "Guardado";
                
                // Mostrar mensaje de confirmación
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = '<i class="fas fa-check"></i> Publicación guardada';
                notification.style.position = 'fixed';
                notification.style.bottom = '20px';
                notification.style.right = '20px';
                notification.style.backgroundColor = 'var(--primary-color)';
                notification.style.color = 'white';
                notification.style.padding = '10px 20px';
                notification.style.borderRadius = '10px';
                notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                notification.style.zIndex = '9999';
                notification.style.transition = 'all 0.3s ease';
                
                document.body.appendChild(notification);
                
                // Desvanecer después de 3 segundos
                setTimeout(() => {
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            } else {
                icon.classList.replace('fas', 'far');
                this.title = "Guardar";
            }
        });

        // Animación suave al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.post-detail-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            }, 100);
        });
    </script>
</body>
</html>