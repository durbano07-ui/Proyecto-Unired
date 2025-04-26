<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';

// Consulta para obtener publicaciones populares (con más likes)
$sql_publicaciones = "SELECT p.Id_publicacion, p.Contenido, u.Nombre, u.Foto_Perfil, p.Fecha_Publicacion, p.Imagen_url, p.Video_url, p.Id_usuario,
                    (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion) AS likes_count,
                    (SELECT COUNT(*) FROM comentarios WHERE Id_publicacion = p.Id_publicacion) AS comments_count,
                    (SELECT COUNT(*) FROM likes WHERE Id_publicacion = p.Id_publicacion AND Id_usuario = ?) AS user_liked
                FROM publicaciones p 
                JOIN usuarios u ON p.Id_usuario = u.Id_usuario";

// Aplicar búsqueda y filtros si existen
if (!empty($busqueda)) {
    if ($filtro == 'usuarios') {
        // Búsqueda de usuarios
        $sql_usuarios = "SELECT Id_usuario, Nombre, Apellido, Foto_Perfil, Biografia FROM usuarios 
                         WHERE Nombre LIKE ? OR Apellido LIKE ? OR CONCAT(Nombre, ' ', Apellido) LIKE ?";
        $stmt_usuarios = $conn->prepare($sql_usuarios);
        $param = "%$busqueda%";
        $stmt_usuarios->bind_param("sss", $param, $param, $param);
        $stmt_usuarios->execute();
        $resultado_usuarios = $stmt_usuarios->get_result();
    } else {
        // Búsqueda en publicaciones
        $sql_publicaciones .= " WHERE p.Contenido LIKE ?";
        $param = "%$busqueda%";
        $stmt_publicaciones = $conn->prepare($sql_publicaciones . " ORDER BY likes_count DESC, p.Fecha_Publicacion DESC");
        $stmt_publicaciones->bind_param("is", $_SESSION['Id_usuario'], $param);
    }
} else {
    // Sin búsqueda, mostrar publicaciones populares
    if ($filtro == 'tendencias') {
        // Publicaciones con más likes en la última semana
        $sql_publicaciones .= " WHERE p.Fecha_Publicacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($filtro == 'recientes') {
        // Publicaciones más recientes
        $sql_publicaciones .= " ORDER BY p.Fecha_Publicacion DESC";
    } else {
        // Por defecto, mostrar las más populares
        $sql_publicaciones .= " ORDER BY likes_count DESC, p.Fecha_Publicacion DESC";
    }
    
    $stmt_publicaciones = $conn->prepare($sql_publicaciones);
    $stmt_publicaciones->bind_param("i", $_SESSION['Id_usuario']);
}

// Ejecutar la consulta de publicaciones si no estamos buscando sólo usuarios
if ($filtro != 'usuarios' || empty($busqueda)) {
    $stmt_publicaciones->execute();
    $resultado_publicaciones = $stmt_publicaciones->get_result();
}

// Obtener hashtags populares
$sql_hashtags = "SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(Contenido, '#', -1), ' ', 1) AS hashtag, 
                COUNT(*) AS recuento
                FROM publicaciones 
                WHERE Contenido LIKE '%#%'
                GROUP BY hashtag
                ORDER BY recuento DESC
                LIMIT 10";
$resultado_hashtags = $conn->query($sql_hashtags);

// Obtener usuarios sugeridos (aquellos que no sigue el usuario actual)
$sql_sugeridos = "SELECT u.Id_usuario, u.Nombre, u.Foto_Perfil
                FROM usuarios u
                WHERE u.Id_usuario != ?
                AND u.Id_usuario NOT IN (
                    SELECT Id_usuario_seguido 
                    FROM seguidores 
                    WHERE Id_usuario = ?
                )
                ORDER BY RAND()
                LIMIT 5";
$stmt_sugeridos = $conn->prepare($sql_sugeridos);
$stmt_sugeridos->bind_param("ii", $_SESSION['Id_usuario'], $_SESSION['Id_usuario']);
$stmt_sugeridos->execute();
$resultado_sugeridos = $stmt_sugeridos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar - Red Social</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --primary-color: rgb(94, 211, 166);
            --primary-light: rgba(94, 211, 166, 0.1);
            --primary-dark: rgb(75, 180, 140);
            --secondary-color: #2f3e47;
            --text-color: #333;
            --light-text: #8899a6;
            --background-color: #f8f8f8;
            --card-bg: #ffffff;
            --border-color: #e1e8ed;
        }

        /* Estructura principal de la página */
        .explore-container {
            display: grid;
            grid-template-columns: 280px 1fr 300px;
            min-height: 100vh;
        }

        /* Contenido principal */
        .main-content {
            padding: 0;
            border-left: 1px solid rgba(0,0,0,0.05);
            border-right: 1px solid rgba(0,0,0,0.05);
        }

        /* Encabezado de la página */
        .page-header {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-header h1 {
            font-size: 20px;
            margin: 0 0 15px 0;
            color: var(--secondary-color);
        }

        /* Barra de búsqueda */
        .search-box {
            display: flex;
            border: 1px solid var(--border-color);
            border-radius: 30px;
            overflow: hidden;
            background-color: var(--background-color);
            transition: all 0.3s ease;
        }

        .search-box:focus-within {
            box-shadow: 0 0 0 2px var(--primary-light);
            border-color: var(--primary-color);
        }

        .search-input {
            flex-grow: 1;
            border: none;
            padding: 10px 15px;
            font-size: 16px;
            background-color: transparent;
        }

        .search-input:focus {
            outline: none;
        }

        .search-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .search-button:hover {
            background-color: var(--primary-dark);
        }

        /* Filtros de búsqueda */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            padding: 0 20px;
            overflow-x: auto;
            white-space: nowrap;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .filter-tabs::-webkit-scrollbar {
            display: none;
        }

        .filter-tab {
            padding: 8px 16px;
            border-radius: 20px;
            background-color: white;
            border: 1px solid var(--border-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .filter-tab.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .filter-tab:hover:not(.active) {
            background-color: var(--primary-light);
            border-color: var(--primary-color);
        }

        /* Sección de resultados */
        .search-results {
            padding: 0 20px 20px;
        }

        /* Estilo para usuarios encontrados */
        .user-card {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .user-info {
            flex-grow: 1;
        }

        .user-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--secondary-color);
            margin: 0 0 5px 0;
        }

        .user-bio {
            font-size: 14px;
            color: var(--light-text);
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .follow-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 20px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .follow-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .follow-button.following {
            background-color: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        /* Mensajes cuando no hay resultados */
        .no-results {
            text-align: center;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-results i {
            font-size: 48px;
            color: var(--light-text);
            margin-bottom: 15px;
        }

        .no-results h3 {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .no-results p {
            color: var(--light-text);
        }

        /* Columna derecha con tendencias y sugerencias */
        .right-sidebar {
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            background-color: var(--background-color);
        }

        .sidebar-card {
            background-color: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .sidebar-card h2 {
            font-size: 18px;
            color: var(--secondary-color);
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        /* Estilos para hashtags populares */
        .trending-hashtags {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .trending-hashtags li {
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .trending-hashtags li:last-child {
            border-bottom: none;
        }

        .trending-hashtags a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .trending-hashtags a:hover {
            color: var(--primary-dark);
        }

        .trending-hashtags .count {
            color: var(--light-text);
            font-size: 12px;
            margin-left: 5px;
        }

        /* Usuarios sugeridos en el sidebar */
        .suggested-user {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .suggested-user:last-child {
            border-bottom: none;
        }

        .suggested-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .suggested-user .user-info {
            flex-grow: 1;
        }

        .suggested-user .user-name {
            font-size: 14px;
            margin: 0;
        }

        .suggested-user .follow-btn {
            background-color: var(--primary-light);
            color: var(--primary-color);
            border: none;
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .suggested-user .follow-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* Publicaciones en feed de exploración */
        .publicacion {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .publicacion:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Responsive */
        @media screen and (max-width: 1200px) {
            .explore-container {
                grid-template-columns: 80px 1fr 250px;
            }
        }

        @media screen and (max-width: 992px) {
            .explore-container {
                grid-template-columns: 80px 1fr;
            }
            
            .right-sidebar {
                display: none;
            }
        }

        @media screen and (max-width: 768px) {
            .explore-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .filter-tabs {
                padding: 0 10px;
            }
            
            .search-results {
                padding: 0 10px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="explore-container">
        <!-- Sidebar izquierdo -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-share-nodes"></i>
            </div>
            
            <ul class="nav-items">
                <li><a href="feed.php"><i class="fas fa-home"></i> <span>Inicio</span></a></li>
                <li><a href="explore.php" class="active"><i class="fas fa-search"></i> <span>Explorar</span></a></li>
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

        <!-- Contenido principal -->
        <div class="main-content">
            <div class="page-header">
                <h1>Explorar</h1>
                <form action="explore.php" method="get">
                    <div class="search-box">
                        <input type="text" name="busqueda" class="search-input" placeholder="Buscar usuarios, publicaciones o hashtags" value="<?php echo htmlspecialchars($busqueda); ?>">
                        <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>

            <div class="filter-tabs">
                <a href="explore.php?busqueda=<?php echo urlencode($busqueda); ?>&filtro=todos" class="filter-tab <?php echo $filtro == 'todos' || $filtro == '' ? 'active' : ''; ?>">
                    <i class="fas fa-globe"></i> Todo
                </a>
                <a href="explore.php?busqueda=<?php echo urlencode($busqueda); ?>&filtro=usuarios" class="filter-tab <?php echo $filtro == 'usuarios' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> Usuarios
                </a>
                <a href="explore.php?busqueda=<?php echo urlencode($busqueda); ?>&filtro=tendencias" class="filter-tab <?php echo $filtro == 'tendencias' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Tendencias
                </a>
                <a href="explore.php?busqueda=<?php echo urlencode($busqueda); ?>&filtro=recientes" class="filter-tab <?php echo $filtro == 'recientes' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Recientes
                </a>
                <?php if ($resultado_hashtags && $resultado_hashtags->num_rows > 0): ?>
                    <?php while ($hashtag = $resultado_hashtags->fetch_assoc()): ?>
                        <a href="explore.php?busqueda=%23<?php echo urlencode($hashtag['hashtag']); ?>" class="filter-tab">
                            <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($hashtag['hashtag']); ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

            <div class="search-results">
                <?php 
                // Mostrar usuarios si estamos en el filtro de usuarios o hay una búsqueda
                if (($filtro == 'usuarios' || !empty($busqueda)) && isset($resultado_usuarios) && $resultado_usuarios->num_rows > 0): ?>
                    <h2>Usuarios encontrados</h2>
                    <?php while ($usuario = $resultado_usuarios->fetch_assoc()): 
                        $foto_perfil = !empty($usuario['Foto_Perfil']) ? $usuario['Foto_Perfil'] : 'uploads/default.jpg';
                    ?>
                        <div class="user-card">
                            <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="<?php echo htmlspecialchars($usuario['Nombre']); ?>" class="user-avatar">
                            <div class="user-info">
                                <h3 class="user-name"><?php echo htmlspecialchars($usuario['Nombre'] . ' ' . $usuario['Apellido']); ?></h3>
                                <p class="user-bio"><?php echo htmlspecialchars($usuario['Biografia']); ?></p>
                            </div>
                            <a href="perfil.php?id=<?php echo $usuario['Id_usuario']; ?>" class="follow-button">Ver perfil</a>
                        </div>
                    <?php endwhile; ?>
                <?php elseif (isset($resultado_usuarios) && $resultado_usuarios->num_rows == 0 && $filtro == 'usuarios'): ?>
                    <div class="no-results">
                        <i class="fas fa-user-slash"></i>
                        <h3>No se encontraron usuarios</h3>
                        <p>Intenta con un término de búsqueda diferente.</p>
                    </div>
                <?php endif; ?>

                <?php 
                // Mostrar publicaciones si no estamos sólo en filtro de usuarios o no hay búsqueda
                if ($filtro != 'usuarios' || empty($busqueda)): 
                    if (isset($resultado_publicaciones) && $resultado_publicaciones->num_rows > 0): 
                ?>
                    <h2><?php echo empty($busqueda) ? 'Publicaciones populares' : 'Publicaciones encontradas'; ?></h2>
                    <?php while ($fila = $resultado_publicaciones->fetch_assoc()): 
                        $foto_perfil = !empty($fila['Foto_Perfil']) ? $fila['Foto_Perfil'] : 'uploads/default.jpg';
                    ?>
                        <div class='publicacion' id='post_<?php echo $fila['Id_publicacion']; ?>' onclick="window.location.href='detalle.php?id=<?php echo $fila['Id_publicacion']; ?>'">
                            <div class="post-content">
                                <div class="header">
                                    <div class="header-left">
                                        <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" class="foto-perfil">
                                        <div>
                                            <p><strong><a href="perfil.php?id=<?php echo $fila['Id_usuario']; ?>" onclick="event.stopPropagation();"><?php echo htmlspecialchars($fila['Nombre']); ?></a></strong></p>
                                            <small><?php echo date('j M Y · g:i A', strtotime($fila['Fecha_Publicacion'])); ?></small>
                                        </div>
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
                                    <i class="<?php echo ($fila['user_liked'] > 0) ? 'fas' : 'far'; ?> fa-heart"></i>
                                    <span class="likes-count" id="likes-count-<?php echo $fila['Id_publicacion']; ?>"><?php echo $fila['likes_count']; ?></span>
                                </button>

                                <button class="comment-btn" data-post-id="<?php echo $fila['Id_publicacion']; ?>" onclick="event.stopPropagation(); window.location.href='detalle.php?id=<?php echo $fila['Id_publicacion']; ?>#comments'">
                                    <i class="far fa-comment"></i> <span><?php echo $fila['comments_count']; ?></span>
                                </button>

                                <button class="share-btn" onclick="event.stopPropagation(); sharePost(<?php echo $fila['Id_publicacion']; ?>)">
                                    <i class="fas fa-share"></i> <span>Compartir</span>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php elseif (isset($resultado_publicaciones) && $resultado_publicaciones->num_rows == 0): ?>
                    <div class="no-results">
                        <i class="far fa-file-alt"></i>
                        <h3>No se encontraron publicaciones</h3>
                        <p>Intenta con un término de búsqueda diferente.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar derecho -->
        <div class="right-sidebar">
            <!-- Tarjeta de hashtags populares -->
            <div class="sidebar-card">
                <h2>Hashtags populares</h2>
                <?php if ($resultado_hashtags && $resultado_hashtags->num_rows > 0): ?>
                    <ul class="trending-hashtags">
                        <?php 
                        // Resetear el puntero del resultado
                        $resultado_hashtags->data_seek(0);
                        while ($hashtag = $resultado_hashtags->fetch_assoc()): ?>
                            <li>
                                <a href="explore.php?busqueda=%23<?php echo urlencode($hashtag['hashtag']); ?>">
                                    #<?php echo htmlspecialchars($hashtag['hashtag']); ?>
                                </a>
                                <span class="count"><?php echo $hashtag['recuento']; ?> publicaciones</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No hay hashtags populares en este momento.</p>
                <?php endif; ?>
            </div>

            <!-- Tarjeta de usuarios sugeridos -->
            <div class="sidebar-card">
                <h2>Usuarios sugeridos</h2>
                <?php if ($resultado_sugeridos && $resultado_sugeridos->num_rows > 0): ?>
                    <div class="suggested-users">
                        <?php while ($sugerido = $resultado_sugeridos->fetch_assoc()): 
                            $foto_perfil = !empty($sugerido['Foto_Perfil']) ? $sugerido['Foto_Perfil'] : 'uploads/default.jpg';
                        ?>
                            <div class="suggested-user">
                                <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="<?php echo htmlspecialchars($sugerido['Nombre']); ?>">
                                <div class="user-info">
                                    <h3 class="user-name"><?php echo htmlspecialchars($sugerido['Nombre']); ?></h3>
                                </div>
                                <button class="follow-btn" onclick="seguirUsuario(<?php echo $sugerido['Id_usuario']; ?>, this)">Seguir</button>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No hay sugerencias disponibles en este momento.</p>
                <?php endif; ?>
            </div>

            <!-- Tarjeta de ayuda -->
            <div class="sidebar-card">
                <h2>Explorar la red</h2>
                <p>Descubre contenido interesante, conecta con nuevos usuarios y mantente al día con las tendencias.</p>
                <ul>
                    <li>Usa los filtros para encontrar lo que buscas</li>
                    <li>Sigue hashtags para ver contenido relacionado</li>
                    <li>Explora perfiles de usuarios con intereses similares</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Función para seguir a un usuario
        function seguirUsuario(userId, button) {
            fetch('toggle_follow.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    action: 'follow'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.textContent = 'Siguiendo';
                    button.classList.add('following');
                    
                    // Opcional: Recargar la página después de un tiempo
                    // setTimeout(() => window.location.reload(), 2000);
                } else {
                    alert(data.message || 'Ha ocurrido un error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ha ocurrido un error al procesar la solicitud');
            });
        }
        
        // Función para dar/quitar like
        function toggleLike(postId, button) {
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
                    // Cambiar estado visual del botón
                    button.classList.toggle('liked');
                    
                    // Cambiar el icono
                    const icon = button.querySelector('i');
                    if (icon.classList.contains('far')) {
                        icon.classList.replace('far', 'fas');
                    } else {
                        icon.classList.replace('fas', 'far');
                    }
                    
                    // Actualizar contador
                    const likesCount = document.getElementById(`likes-count-${postId}`);
                    likesCount.textContent = data.likes;
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Función para compartir publicación
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
        
        // Detección de hashtags en la búsqueda
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('.search-input');
            
            searchInput.addEventListener('input', function() {
                const value = this.value;
                if (value.startsWith('#')) {
                    // Cambiar al filtro de hashtags automáticamente
                    document.querySelector('[data-filter="hashtags"]').click();
                }
            });
            
            // Función para manejar los formularios de búsqueda
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const url = new URL(this.href);
                    window.location.href = url.href;
                });
            });
        });
    </script>
</body>
</html>