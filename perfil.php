<?php
session_start();
$conn = new mysqli("localhost", "root", "", "UniredBd");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (!isset($_SESSION["Id_usuario"])) {
    die("Debes iniciar sesión\n");
}

// Determinar qué perfil mostrar (el propio o el de otro usuario)
$id_usuario = isset($_GET['id']) ? $_GET['id'] : $_SESSION["Id_usuario"];

// Preparamos la consulta para obtener los datos del usuario
$sql = "SELECT Nombre, Apellido, Foto_Perfil, Biografia, Fecha_registro FROM Usuarios WHERE Id_usuario = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Obtener las publicaciones del usuario
$sql_posts = "SELECT Id_publicacion, Contenido, Fecha_Publicacion, Imagen_url, Video_url FROM Publicaciones WHERE Id_usuario = ? ORDER BY Fecha_Publicacion DESC";
$stmt_posts = $conn->prepare($sql_posts);

if ($stmt_posts === false) {
    die("Error en la preparación de la consulta de publicaciones: " . $conn->error);
}

$stmt_posts->bind_param("i", $id_usuario);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

// Contar seguidores y seguidos (esto requerirá modificar la estructura de la base de datos si aún no existe)
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

// Contar total de publicaciones
$sql_total_posts = "SELECT COUNT(*) as total FROM publicaciones WHERE Id_usuario = ?";
$stmt_total_posts = $conn->prepare($sql_total_posts);
$stmt_total_posts->bind_param("i", $id_usuario);
$stmt_total_posts->execute();
$result_total_posts = $stmt_total_posts->get_result();
$total_posts = $result_total_posts->fetch_assoc()['total'];

$stmt_posts->close();
$conn->close();

// Formatear fecha de registro
$fecha_registro = new DateTime($usuario['Fecha_registro']);
$fecha_formateada = $fecha_registro->format('F Y');

// Verificar si existe una foto de perfil, si no, usar la imagen por defecto
$foto_perfil = !empty($usuario['Foto_Perfil']) ? $usuario['Foto_Perfil'] : 'uploads/default.jpg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo $usuario['Nombre']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1DA1F2;
            --secondary-color: #14171A;
            --background-color: #15202B;
            --text-color: #ffffff;
            --border-color: #38444D;
            --hover-color: #192734;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Barra de navegación */
        .nav-bar {
            display: flex;
            padding: 10px 20px;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }

        .back-button {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            margin-right: 20px;
            font-size: 20px;
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-info h1 {
            font-size: 20px;
            font-weight: bold;
        }

        .profile-info span {
            font-size: 14px;
            color: #8899A6;
        }


/* Modificaciones para el header del perfil */
.profile-header {
    position: relative;
    display: flex;
    align-items: center;
    padding: 20px;
    margin-bottom: 20px;
}

.profile-image {
    position: static; /* Cambiar de absolute a static */
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--background-color);
    object-fit: cover;
    background-color: var(--background-color);
    margin-right: 20px; /* Espacio entre la imagen y la info */
}

.profile-info-container {
    flex-grow: 1;
}

.profile-actions {
    display: flex;
    margin-top: 10px;
    justify-content: flex-start; /* Alinear a la izquierda */
    padding: 0;
    margin-bottom: 0;
}

.edit-button, .follow-button {
    margin-top: 10px;
    display: inline-block;
}

/* Ajustes en la info del perfil */
.profile-data {
    padding: 0 20px 20px;
    border-bottom: 1px solid var(--border-color);
}

/* Ajustar el espacio para las pestañas */
.profile-tabs {
    margin-top: 0;
}
        .follow-button:hover {
            background-color: #e6e6e6;
        }

        .edit-button {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid var(--text-color);
            border-radius: 30px;
            padding: 8px 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .edit-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Información del perfil */
        .profile-data {
            padding: 0 20px;
        }

        .user-name {
            font-size: 20px;
            font-weight: bold;
        }

        .user-handle {
            font-size: 15px;
            color: #8899A6;
            margin-bottom: 10px;
        }

        .user-bio {
            margin-bottom: 15px;
            font-size: 15px;
        }

        .user-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 15px;
            color: #8899A6;
        }

        .user-meta i {
            margin-right: 5px;
        }

        .user-meta .join-date {
            display: flex;
            align-items: center;
        }

        .user-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 15px;
        }

        .stat-number {
            font-weight: bold;
            color: var(--text-color);
        }

        .stat-label {
            color: #8899A6;
        }

        /* Navegación de pestañas */
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-top: 20px;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 15px 0;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            position: relative;
        }

        .tab.active {
            color: var(--primary-color);
        }

        .tab.active::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background-color: var(--primary-color);
            border-radius: 2px 2px 0 0;
        }

        .tab:hover {
            background-color: var(--hover-color);
        }

        /* Contenido de las publicaciones */
        .tab-content {
            padding: 20px;
        }

        .post {
            border-bottom: 1px solid var(--border-color);
            padding: 15px 0;
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .post-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .post-name {
            font-weight: bold;
        }

        .post-username, .post-date {
            font-size: 14px;
            color: #8899A6;
        }

        .post-content {
            margin-bottom: 10px;
        }

        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 15px;
            margin-bottom: 10px;
        }

        .post-video {
            width: 100%;
            max-height: 400px;
            border-radius: 15px;
            margin-bottom: 10px;
        }

        .post-actions {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }

        .post-action {
            display: flex;
            align-items: center;
            color: #8899A6;
            cursor: pointer;
        }

        .post-action i {
            margin-right: 5px;
        }

        .post-action:hover {
            color: var(--primary-color);
        }

        .no-posts {
            text-align: center;
            padding: 50px 0;
            color: #8899A6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .profile-image {
                width: 100px;
                height: 100px;
                bottom: -50px;
            }
            
            .profile-actions {
                margin-bottom: 60px;
            }
            
            .user-meta, .user-stats {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Barra de navegación -->
        <div class="nav-bar">
            <a href="feed.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="profile-info">
                <h1><?php echo $usuario['Nombre'] . ' ' . $usuario['Apellido']; ?></h1>
                <span><?php echo $total_posts; ?> publicaciones</span>
            </div>
        </div>

        <!-- Cabecera del perfil -->
        <div class="profile-header">
    <img src="<?php echo $foto_perfil; ?>" alt="Foto de perfil" class="profile-image">
    
    <div class="profile-info-container">
        <h2 class="user-name"><?php echo $usuario['Nombre'] . ' ' . $usuario['Apellido']; ?></h2>
        <p class="user-handle">@<?php echo strtolower($usuario['Nombre'] . $usuario['Apellido']); ?></p>
        
        <p class="user-bio"><?php echo $usuario['Biografia']; ?></p>
        
        <?php if ($id_usuario == $_SESSION['Id_usuario']): ?>
            <a href="editar_perfil.php" class="edit-button">Editar perfil</a>
        <?php else: ?>
            <button class="follow-button">Seguir</button>
        <?php endif; ?>
    </div>
</div>

        <!-- Información del perfil -->
        <div class="profile-data">
    <div class="user-meta">
        <div class="location">
            <i class="fas fa-map-marker-alt"></i> Las Vegas, Nevada
        </div>
        <div class="join-date">
            <i class="far fa-calendar-alt"></i> Se unió en <?php echo $fecha_formateada; ?>
        </div>
    </div>
    
    <div class="user-stats">
        <div class="following">
            <span class="stat-number"><?php echo $seguidos; ?></span>
            <span class="stat-label">Siguiendo</span>
        </div>
        <div class="followers">
            <span class="stat-number"><?php echo $seguidores; ?></span>
            <span class="stat-label">Seguidores</span>
        </div>
    </div>
</div>
        <!-- Navegación de pestañas -->
        <div class="profile-tabs">
            <div class="tab active" data-tab="posts">Publicaciones</div>
            <div class="tab" data-tab="media">Media</div>
            <div class="tab" data-tab="likes">Me gusta</div>
        </div>

        <!-- Contenido de las publicaciones -->
        <div class="tab-content" id="posts-content">
            <?php if ($result_posts->num_rows > 0): ?>
                <?php while ($post = $result_posts->fetch_assoc()): ?>
                    <div class="post">
                        <div class="post-header">
                            <img src="<?php echo $foto_perfil; ?>" alt="Avatar" class="post-avatar">
                            <div>
                                <span class="post-name"><?php echo $usuario['Nombre'] . ' ' . $usuario['Apellido']; ?></span>
                                <span class="post-username">@<?php echo strtolower($usuario['Nombre'] . $usuario['Apellido']); ?></span>
                                <span class="post-date"> · <?php echo date("d M Y", strtotime($post['Fecha_Publicacion'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <p><?php echo htmlspecialchars($post['Contenido']); ?></p>
                            
                            <?php if (!empty($post['Imagen_url'])): ?>
                                <img src="<?php echo htmlspecialchars($post['Imagen_url']); ?>" alt="Imagen de publicación" class="post-image">
                            <?php endif; ?>
                            
                            <?php if (!empty($post['Video_url'])): ?>
                                <video controls class="post-video">
                                    <source src="<?php echo htmlspecialchars($post['Video_url']); ?>" type="video/mp4">
                                    Tu navegador no soporta videos HTML5.
                                </video>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-actions">
                            <div class="post-action">
                                <i class="far fa-comment"></i>
                                <span>0</span>
                            </div>
                            <div class="post-action">
                                <i class="fas fa-retweet"></i>
                                <span>0</span>
                            </div>
                            <div class="post-action">
                                <i class="far fa-heart"></i>
                                <span>0</span>
                            </div>
                            <div class="post-action">
                                <i class="far fa-share-square"></i>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-posts">
                    <p>No hay publicaciones para mostrar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Funcionalidad para las pestañas
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remover clase activa de todas las pestañas
                    tabs.forEach(t => t.classList.remove('active'));
                    
                    // Añadir clase activa a la pestaña clickeada
                    this.classList.add('active');
                    
                    // Aquí se podría implementar la carga de contenido dinámico
                    // según la pestaña seleccionada (posts, replies, media, likes)
                    
                    // Por ahora solo cambiamos el título
                    const tabName = this.getAttribute('data-tab');
                    console.log(`Tab ${tabName} selected`);
                });
            });
        });
    </script>
</body>
</html>
