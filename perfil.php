<?php
session_start();
require_once "Database.php"; // Usando la clase Database en lugar de conexión directa

if (!isset($_SESSION["Id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Determinar qué perfil mostrar
$id_usuario = isset($_GET['id']) ? $_GET['id'] : $_SESSION["Id_usuario"];

// Obtener conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Consulta para obtener datos del usuario
$sql = "SELECT Nombre, Apellido, Foto_Perfil, Biografia, Fecha_registro FROM Usuarios WHERE Id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Obtener las publicaciones del usuario
$sql_posts = "SELECT p.Id_publicacion, p.Contenido, p.Fecha_Publicacion, p.Imagen_url, p.Video_url,
                (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion) AS likes_count,
                (SELECT COUNT(*) FROM comentarios WHERE Id_publicacion = p.Id_publicacion) AS comments_count
              FROM Publicaciones p 
              WHERE p.Id_usuario = ? 
              ORDER BY p.Fecha_Publicacion DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $id_usuario);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

// Contar seguidores y seguidos
$sql_seguidores = "SELECT COUNT(*) as total FROM seguidores WHERE Id_usuario_seguido = ?";
$stmt_seguidores = $conn->prepare($sql_seguidores);
$stmt_seguidores->bind_param("i", $id_usuario);
$stmt_seguidores->execute();
$result_seguidores = $stmt_seguidores->get_result();
$seguidores = ($result_seguidores->num_rows > 0) ? $result_seguidores->fetch_assoc()['total'] : 0;

$sql_seguidos = "SELECT COUNT(*) as total FROM seguidores WHERE Id_usuario = ?";
$stmt_seguidos = $conn->prepare($sql_seguidos);
$stmt_seguidos->bind_param("i", $id_usuario);
$stmt_seguidos->execute();
$result_seguidos = $stmt_seguidos->get_result();
$seguidos = ($result_seguidos->num_rows > 0) ? $result_seguidos->fetch_assoc()['total'] : 0;

// Contar publicaciones
$sql_total_posts = "SELECT COUNT(*) as total FROM publicaciones WHERE Id_usuario = ?";
$stmt_total_posts = $conn->prepare($sql_total_posts);
$stmt_total_posts->bind_param("i", $id_usuario);
$stmt_total_posts->execute();
$result_total_posts = $stmt_total_posts->get_result();
$total_posts = $result_total_posts->fetch_assoc()['total'];

// Verificar si el usuario actual sigue a este perfil
$is_following = false;
if ($id_usuario != $_SESSION['Id_usuario']) {
    $sql_check_follow = "SELECT * FROM seguidores WHERE Id_usuario = ? AND Id_usuario_seguido = ?";
    $stmt_check_follow = $conn->prepare($sql_check_follow);
    $stmt_check_follow->bind_param("ii", $_SESSION['Id_usuario'], $id_usuario);
    $stmt_check_follow->execute();
    $result_check_follow = $stmt_check_follow->get_result();
    $is_following = ($result_check_follow->num_rows > 0);
}

// Formatear fecha de registro
$fecha_registro = new DateTime($usuario['Fecha_registro']);
$fecha_formateada = $fecha_registro->format('F Y');

// Foto de perfil por defecto
$foto_perfil = !empty($usuario['Foto_Perfil']) ? $usuario['Foto_Perfil'] : 'uploads/default.jpg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo $usuario['Nombre']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: rgb(94, 211, 166);
            --primary-light: rgba(94, 211, 166, 0.2);
            --primary-dark: rgb(70, 160, 126);
            --secondary-color: #6c5ce7;
            --accent-color: #fd79a8;
            --text-color: #2d3436;
            --light-text: #636e72;
            --background-color: #f9f9f9;
            --card-bg: #ffffff;
            --border-radius: 16px;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* Estructura principal */
        .profile-page {
            display: grid;
            grid-template-columns: 280px 1fr 300px;
            min-height: 100vh;
        }
        
        .sidebar {
            /* Tu sidebar existente */
            position: sticky;
            top: 0;
            height: 100vh;
        }
        
        .main-content {
            padding: 0;
            border-left: 1px solid rgba(0,0,0,0.05);
            border-right: 1px solid rgba(0,0,0,0.05);
        }
        
        .right-sidebar {
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        /* Cabecera de la página */
        .page-header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .page-header .back-button {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            margin-right: 15px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-header .back-button:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-3px);
        }

        .page-header h1 {
            font-size: 1.25rem;
            margin: 0;
        }

        .page-header .post-count {
            font-size: 0.875rem;
            color: var(--light-text);
            margin-left: 8px;
        }

        /* Banner del perfil */
        .profile-banner {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            overflow: hidden;
        }

        .banner-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(circle, rgba(255,255,255,0.1) 2px, transparent 2px);
            background-size: 20px 20px;
        }

        /* Perfil principal */
        .profile-main {
            margin-top: -60px;
            padding: 0 20px;
            position: relative;
        }

        .profile-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }

        .profile-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .profile-avatar-container {
            margin-top: -60px;
            margin-bottom: 15px;
            position: relative;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: var(--border-radius);
            object-fit: cover;
            border: 4px solid white;
            box-shadow: var(--shadow);
            background-color: white;
        }

        .profile-status {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: var(--primary-color);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 3px solid white;
        }

        .profile-name-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .profile-name-info h2 {
            font-size: 1.5rem;
            margin: 0 0 5px 0;
        }

        .profile-username {
            color: var(--light-text);
            font-size: 0.95rem;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
        }

        .profile-btn {
            border: none;
            border-radius: 30px;
            padding: 8px 20px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .profile-btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .profile-btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(94, 211, 166, 0.3);
        }

        .profile-btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .profile-btn-outline:hover {
            background-color: var(--primary-light);
            transform: translateY(-2px);
        }

        .profile-btn-icon-only {
            width: 38px;
            height: 38px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .profile-btn-icon-only:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .profile-bio {
            margin-bottom: 20px;
            font-size: 1rem;
            line-height: 1.5;
        }

        /* Meta información del perfil */
        .profile-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            color: var(--light-text);
            font-size: 0.95rem;
        }

        .meta-item i {
            margin-right: 6px;
            color: var(--primary-color);
        }

        /* Estadísticas del perfil */
        .profile-stats {
            display: flex;
            background-color: var(--background-color);
            border-radius: var(--border-radius);
            padding: 15px;
            justify-content: space-around;
            text-align: center;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--light-text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Navegación de las pestañas */
        .profile-tabs {
            display: flex;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .profile-tab {
            flex: 1;
            padding: 15px 10px;
            text-align: center;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: transparent;
            position: relative;
            color: var(--light-text);
        }

        .profile-tab.active {
            color: var(--primary-color);
        }

        .profile-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 3px 3px 0 0;
        }

        .profile-tab:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        /* Contenido de las pestañas */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Publicaciones */
        .post-item {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        .post-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            margin-right: 15px;
            object-fit: cover;
        }

        .post-user-info {
            flex-grow: 1;
        }

        .post-user-name {
            font-weight: 600;
            font-size: 1rem;
            margin: 0;
        }

        .post-user-handle, .post-date {
            color: var(--light-text);
            font-size: 0.85rem;
        }

        .post-options {
            color: var(--light-text);
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s;
        }

        .post-options:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .post-content {
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.5;
        }

        .post-media {
            margin-bottom: 15px;
            border-radius: 12px;
            overflow: hidden;
        }

        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 12px;
        }

        .post-video {
            width: 100%;
            max-height: 400px;
            border-radius: 12px;
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .post-action {
            display: flex;
            align-items: center;
            color: var(--light-text);
            gap: 6px;
            font-size: 0.95rem;
            padding: 8px 12px;
            border-radius: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .post-action:hover {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }

        .no-posts {
            padding: 50px 20px;
            text-align: center;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .no-posts i {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 20px;
        }

        .no-posts h3 {
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .no-posts p {
            color: var(--light-text);
        }

        /* Otras pestañas */
        .media-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .media-item {
            aspect-ratio: 1/1;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }

        .media-item img, .media-item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Responsividad */
        @media (max-width: 1200px) {
            .profile-page {
                grid-template-columns: 70px 1fr 250px;
            }
        }

        @media (max-width: 992px) {
            .profile-page {
                grid-template-columns: 70px 1fr;
            }
            
            .right-sidebar {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .profile-page {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .profile-name-container {
                flex-direction: column;
            }
            
            .profile-actions {
                margin-top: 15px;
            }
            
            .profile-stats {
                flex-wrap: wrap;
            }
            
            .stat-item {
                flex: 1 0 30%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-page">
        <!-- Sidebar (importa tu sidebar existente) -->
        <div class="sidebar">
            <!-- Contenido del sidebar existente -->
        </div>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Cabecera de la página -->
            <header class="page-header">
                <a href="feed.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1>Perfil<span class="post-count"><?php echo $total_posts; ?> publicaciones</span></h1>
            </header>
            
            <!-- Banner del perfil -->
            <div class="profile-banner">
                <div class="banner-pattern"></div>
            </div>
            
            <!-- Información principal del perfil -->
            <div class="profile-main">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar-container">
                            <img src="<?php echo $foto_perfil; ?>" alt="Foto de perfil" class="profile-avatar">
                            <div class="profile-status"></div>
                        </div>
                        
                        <div class="profile-name-container">
                            <div class="profile-name-info">
                                <h2><?php echo $usuario['Nombre'] . ' ' . $usuario['Apellido']; ?></h2>
                                <div class="profile-username">@<?php echo strtolower($usuario['Nombre'] . $usuario['Apellido']); ?></div>
                            </div>
                            
                            <div class="profile-actions">
                                <?php if ($id_usuario == $_SESSION['Id_usuario']): ?>
                                    <a href="editar_perfil.php" class="profile-btn profile-btn-primary">
                                        <i class="fas fa-pen"></i> Editar perfil
                                    </a>
                                    <button class="profile-btn profile-btn-icon-only">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="profile-btn <?php echo $is_following ? 'profile-btn-outline' : 'profile-btn-primary'; ?>" id="follow-btn" data-user-id="<?php echo $id_usuario; ?>">
                                        <?php if ($is_following): ?>
                                            <i class="fas fa-user-check"></i> Siguiendo
                                        <?php else: ?>
                                            <i class="fas fa-user-plus"></i> Seguir
                                        <?php endif; ?>
                                    </button>
                                    <button class="profile-btn profile-btn-outline">
                                        <i class="fas fa-envelope"></i> Mensaje
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="profile-bio">
                            <?php echo $usuario['Biografia']; ?>
                        </div>
                        
                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Las Vegas, Nevada</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Se unió en <?php echo $fecha_formateada; ?></span>
                            </div>
                        </div>
                        
                        <div class="profile-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $total_posts; ?></div>
                                <div class="stat-label">Publicaciones</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $seguidores; ?></div>
                                <div class="stat-label">Seguidores</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $seguidos; ?></div>
                                <div class="stat-label">Siguiendo</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navegación de pestañas -->
                <div class="profile-tabs">
                    <div class="profile-tab active" data-tab="posts">
                        <i class="fas fa-th-large"></i> Publicaciones
                    </div>
                    <div class="profile-tab" data-tab="media">
                        <i class="fas fa-image"></i> Media
                    </div>
                    <div class="profile-tab" data-tab="likes">
                        <i class="fas fa-heart"></i> Me gusta
                    </div>
                </div>
                
                <!-- Contenido de las pestañas -->
                <div id="posts-content" class="tab-content active">
                    <?php if ($result_posts->num_rows > 0): ?>
                        <?php while ($post = $result_posts->fetch_assoc()): ?>
                            <div class="post-item">
                                <div class="post-header">
                                    <img src="<?php echo $foto_perfil; ?>" alt="Avatar" class="post-avatar">
                                    <div class="post-user-info">
                                        <h3 class="post-user-name"><?php echo $usuario['Nombre'] . ' ' . $usuario['Apellido']; ?></h3>
                                        <span class="post-user-handle">@<?php echo strtolower($usuario['Nombre'] . $usuario['Apellido']); ?></span>
                                        <span class="post-date"><?php echo date("d M Y", strtotime($post['Fecha_Publicacion'])); ?></span>
                                    </div>
                                    <div class="post-options">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </div>
                                </div>
                                
                                <div class="post-content">
                                    <p><?php echo htmlspecialchars($post['Contenido']); ?></p>
                                </div>
                                
                                <?php if (!empty($post['Imagen_url'])): ?>
                                    <div class="post-media">
                                        <img src="<?php echo htmlspecialchars($post['Imagen_url']); ?>" alt="Imagen de publicación" class="post-image">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['Video_url'])): ?>
                                    <div class="post-media">
                                        <video controls class="post-video">
                                            <source src="<?php echo htmlspecialchars($post['Video_url']); ?>" type="video/mp4">
                                            Tu navegador no soporta videos HTML5.
                                        </video>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-actions">
                                    <div class="post-action" onclick="likePost(<?php echo $post['Id_publicacion']; ?>)">
                                        <i class="far fa-heart"></i>
                                        <span><?php echo isset($post['likes_count']) ? $post['likes_count'] : 0; ?></span>
                                    </div>
                                    <div class="post-action" onclick="showComments(<?php echo $post['Id_publicacion']; ?>)">
                                        <i class="far fa-comment"></i>
                                        <span><?php echo isset($post['comments_count']) ? $post['comments_count'] : 0; ?></span>
                                    </div>
                                    <div class="post-action" onclick="sharePost(<?php echo $post['Id_publicacion']; ?>)">
                                        <i class="fas fa-share-alt"></i>
                                        <span>Compartir</span>
                                    </div>
                                    <div class="post-action" onclick="savePost(<?php echo $post['Id_publicacion']; ?>)">
                                        <i class="far fa-bookmark"></i>
                                        <span>Guardar</span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-posts">
                            <i class="far fa-file-alt"></i>
                            <h3>No hay publicaciones aún</h3>
                            <p>Las publicaciones que hagas aparecerán aquí</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="media-content" class="tab-content">
                    <div class="media-grid">
                        <?php
                        // Reiniciar el puntero de resultados
                        $stmt_media = $conn->prepare("SELECT Imagen_url, Video_url FROM Publicaciones WHERE Id_usuario = ? AND (Imagen_url IS NOT NULL OR Video_url IS NOT NULL) ORDER BY Fecha_Publicacion DESC");
                        $stmt_media->bind_param("i", $id_usuario);
                        $stmt_media->execute();
                        $result_media = $stmt_media->get_result();
                        
                        $mediaCount = 0;
                        while ($media = $result_media->fetch_assoc()) {
                            $mediaCount++;
                            if (!empty($media['Imagen_url'])) {
                                echo '<div class="media-item">';
                                echo '<img src="' . htmlspecialchars($media['Imagen_url']) . '" alt="Media">';
                                echo '</div>';
                            } elseif (!empty($media['Video_url'])) {
                                echo '<div class="media-item">';
                                echo '<video><source src="' . htmlspecialchars($media['Video_url']) . '" type="video/mp4"></video>';
                                echo '</div>';
                            }
                        }
                        
                        if ($mediaCount == 0) {
                            echo '<div class="no-posts" style="grid-column: span 3;">';
                            echo '<i class="far fa-images"></i>';
                            echo '<h3>No hay media disponible</h3>';
                            echo '<p>Las fotos y videos que publiques aparecerán aquí</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div id="likes-content" class="tab-content">
                    <div class="no-posts">
                        <i class="far fa-heart"></i>
                        <h3>No hay publicaciones con me gusta</h3>
                        <p>Las publicaciones que te gusten aparecerán aquí</p>
                    </div>
                </div>
            </div>
        </main>
        
        <!-- Sidebar derecho -->
        <aside class="right-sidebar">
            <!-- Aquí puedes añadir contenido para el sidebar derecho, como tendencias, sugerencias, etc. -->
            <div class="profile-card">
                <h3>A quién seguir</h3>
                <!-- Lista de sugerencias -->
            </div>
            
            <div class="profile-card">
                <h3>Tendencias</h3>
                <!-- Lista de tendencias -->
            </div>
        </aside>
    </div>

    <script>
        // Funcionalidad para las pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.profile-tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Desactivar todas las pestañas y contenidos
                    tabs.forEach(t => t.classList.remove('active'));
                    contents.forEach(c => c.classList.remove('active'));
                    
                    // Activar la pestaña y el contenido correspondiente
                    this.classList.add('active');
                    const tabName = this.getAttribute('data-tab');
                    document.getElementById(`${tabName}-content`).classList.add('active');
                });
            });
            
            // Funcionalidad para el botón de seguir
            const followBtn = document.getElementById('follow-btn');
            if (followBtn) {
                followBtn.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    const isFollowing = this.classList.contains('profile-btn-outline');
                    
                    // Enviar petición AJAX para seguir/dejar de seguir
                    fetch('toggle_follow.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            action: isFollowing ? 'unfollow' : 'follow'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Cambiar apariencia del botón
                            if (isFollowing) {
                                this.classList.replace('profile-btn-outline', 'profile-btn-primary');
                                this.innerHTML = '<i class="fas fa-user-plus"></i> Seguir';
                                
                                // Actualizar contador de seguidores
                                const followersCount = document.querySelector('.stat-item:nth-child(2) .stat-value');
                                followersCount.textContent = parseInt(followersCount.textContent) - 1;
                            } else {
                                this.classList.replace('profile-btn-primary', 'profile-btn-outline');
                                this.innerHTML = '<i class="fas fa-user-check"></i> Siguiendo';
                                
                                // Actualizar contador de seguidores
                                const followersCount = document.querySelector('.stat-item:nth-child(2) .stat-value');
                                followersCount.textContent = parseInt(followersCount.textContent) + 1;
                            }
                        } else {
                            alert(data.message || 'Ha ocurrido un error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            }
        });

        // Funciones para interactuar con las publicaciones
        function likePost(postId) {
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ postId: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar la interfaz de usuario
                    const likeButton = event.currentTarget;
                    const icon = likeButton.querySelector('i');
                    const count = likeButton.querySelector('span');
                    
                    if (data.action === 'like') {
                        icon.classList.replace('far', 'fas');
                        icon.style.color = '#fd79a8';
                        count.textContent = data.likes;
                    } else {
                        icon.classList.replace('fas', 'far');
                        icon.style.color = '';
                        count.textContent = data.likes;
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function showComments(postId) {
            window.location.href = `detalle.php?id=${postId}`;
        }

        function sharePost(postId) {
            const postUrl = `${window.location.origin}/detalle.php?id=${postId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'Publicación compartida',
                    url: postUrl
                })
                .catch(err => {
                    console.error('Error al compartir:', err);
                    prompt('Copia este enlace para compartir la publicación:', postUrl);
                });
            } else {
                prompt('Copia este enlace para compartir la publicación:', postUrl);
            }
        }

        function savePost(postId) {
            fetch('guardar_publicacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id_publicacion: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Cambiar el icono a "guardado"
                    const saveButton = event.currentTarget;
                    const icon = saveButton.querySelector('i');
                    icon.classList.replace('far', 'fas');
                    saveButton.querySelector('span').textContent = 'Guardado';
                    
                    // Opcional: Mostrar una notificación
                    const notification = document.createElement('div');
                    notification.textContent = 'Publicación guardada';
                    notification.style.position = 'fixed';
                    notification.style.bottom = '20px';
                    notification.style.right = '20px';
                    notification.style.background = 'var(--primary-color)';
                    notification.style.color = 'white';
                    notification.style.padding = '10px 20px';
                    notification.style.borderRadius = '10px';
                    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                    notification.style.zIndex = '9999';
                    
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.style.opacity = '0';
                        notification.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => {
                            document.body.removeChild(notification);
                        }, 500);
                    }, 3000);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>