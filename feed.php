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
    
    // Determinar el tipo de publicación
    $tipo = isset($_POST['tipo']) && $_POST['tipo'] === 'evento' ? 'evento' : 'normal';
    
    // Procesar datos del evento si es necesario
    $evento_titulo = null;
    $evento_fecha = null;
    $evento_hora = null;
    $evento_ubicacion = null;
    $evento_descripcion = null;
    
    if ($tipo === 'evento') {
        $evento_titulo = isset($_POST['evento_titulo']) ? $_POST['evento_titulo'] : null;
        $evento_fecha = isset($_POST['evento_fecha']) ? $_POST['evento_fecha'] : null;
        $evento_hora = isset($_POST['evento_hora']) ? $_POST['evento_hora'] : null;
        $evento_ubicacion = isset($_POST['evento_ubicacion']) ? $_POST['evento_ubicacion'] : null;
        $evento_descripcion = isset($_POST['evento_descripcion']) ? $_POST['evento_descripcion'] : null;
    }

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

    // Insertar la publicación en la base de datos
    $sqlInsert = "INSERT INTO publicaciones 
                 (Id_usuario, Tipo, Contenido, Imagen_url, Video_url, 
                  evento_titulo, evento_fecha, evento_hora, evento_ubicacion, evento_descripcion, 
                  Fecha_Publicacion) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                 
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("isssssssss", 
        $usuarioId, $tipo, $contenido, $imagen_url, $video_url,
        $evento_titulo, $evento_fecha, $evento_hora, $evento_ubicacion, $evento_descripcion);

    if ($stmtInsert->execute()) {
        header("Location: feed.php"); // Redirigir para mostrar la nueva publicación
        exit();
    } else {
        echo "Error al crear la publicación: " . $conn->error;
    }
}

// Obtener las publicaciones
$sql = "SELECT p.Id_publicacion, p.Contenido, p.Tipo, p.evento_titulo, p.evento_fecha, 
               p.evento_hora, p.evento_ubicacion, p.evento_descripcion, 
               u.Nombre, u.Foto_Perfil, p.Fecha_Publicacion, p.Imagen_url, p.Video_url, p.Id_usuario,
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
    <style>
        /* Estilos para la sección de botones de carga */
        .upload-options {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .file-label, .evento-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-label {
            background-color: #f0f2f5;
            color: #1da1f2;
        }

        .evento-label {
            background-color: #f0f2f5;
            color: #5e8dd3;
            border: none;
        }

        .file-label:hover, .evento-label:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .file-label i, .evento-label i {
            font-size: 18px;
        }

        /* Estilos para el formulario de evento */
        #evento-form {
            background-color: #f8f9fa;
            border: 1px solid #e1e8ed;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .evento-field {
            margin-bottom: 12px;
        }

        .evento-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #2f3e47;
            font-size: 14px;
        }

        .evento-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
        }

        .evento-textarea {
            width: 100%;
            height: 80px;
            padding: 10px;
            border: 1px solid #e1e8ed;
            border-radius: 5px;
            font-size: 14px;
            resize: vertical;
        }

        .evento-row {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
        }

        .evento-row .evento-field {
            flex: 1;
            margin-bottom: 0;
        }

        .cancel-evento-btn {
            background-color: #f0f2f5;
            color: #657786;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .cancel-evento-btn:hover {
            background-color: #e1e8ed;
        }

        /* Botón de publicar cuando es un evento */
        .publicar-evento {
            background-color: #5e8dd3 !important;
        }

        /* Estilos para publicaciones de tipo evento */
        .publicacion-evento {
            background-color: #f8f9fa;
            border-left: 4px solid #5e8dd3;
        }

        .evento-container {
            background-color: #f0f7ff;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(94, 141, 211, 0.2);
            position: relative;
        }

        .evento-badge {
            position: absolute;
            top: -10px;
            right: 15px;
            background-color: #5e8dd3;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .evento-titulo {
            font-size: 20px;
            margin: 0 0 15px 0;
            color: #2f3e47;
            font-weight: 600;
        }

        .evento-detalles {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .evento-fecha-hora, .evento-ubicacion {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #657786;
            font-size: 14px;
        }

        .evento-fecha-hora i, .evento-ubicacion i {
            color: #5e8dd3;
            width: 16px;
            text-align: center;
        }

        .evento-descripcion {
            font-size: 15px;
            line-height: 1.5;
            color: #2f3e47;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border: 1px solid rgba(94, 141, 211, 0.1);
        }

        .evento-participar {
            display: block;
            background-color: #5e8dd3;
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            text-align: center;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }

        .evento-participar:hover {
            background-color: #4a79be;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(94, 141, 211, 0.3);
        }

        .evento-participar i {
            margin-right: 5px;
        }

        /* Estilos para mensajes de error y validación */
        .error-message {
            background-color: #ffeaea;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeIn 0.3s ease-in-out;
        }

        .error-message i {
            font-size: 18px;
        }

        .campo-requerido {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 1px rgba(231, 76, 60, 0.3) !important;
            background-color: #fff5f5 !important;
        }

        .campo-requerido:focus {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.3) !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
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
    <form action="feed.php" method="post" enctype="multipart/form-data" id="publish-form">
        <textarea name="contenido" placeholder="Escribe tu publicación..." id="contenido-text"></textarea>

        <div class="upload-options">
            <label for="imagenUpload" class="file-label">
                <i class="fas fa-image"></i>
            </label>
            <input type="file" id="imagenUpload" name="imagen" accept="image/*" style="display: none;">

            <label for="videoUpload" class="file-label">
                <i class="fas fa-video"></i>
            </label>
            <input type="file" id="videoUpload" name="video" accept="video/*" style="display: none;">
            
            <!-- Botón para crear evento -->
            <button type="button" id="eventoBtn" class="evento-label">
                <i class="fas fa-calendar-alt"></i>
            </button>
        </div>

        <!-- Formulario de evento (inicialmente oculto) -->
        <div id="evento-form" style="display: none;">
            <input type="hidden" name="tipo" value="normal" id="tipo-publicacion">
            
            <div class="evento-field">
                <label for="evento_titulo">Título del evento:</label>
                <input type="text" id="evento_titulo" name="evento_titulo" placeholder="Título del evento" class="evento-input">
            </div>
            
            <div class="evento-row">
                <div class="evento-field">
                    <label for="evento_fecha">Fecha:</label>
                    <input type="date" id="evento_fecha" name="evento_fecha" class="evento-input">
                </div>
                
                <div class="evento-field">
                    <label for="evento_hora">Hora:</label>
                    <input type="time" id="evento_hora" name="evento_hora" class="evento-input">
                </div>
            </div>
            
            <div class="evento-field">
                <label for="evento_ubicacion">Ubicación:</label>
                <input type="text" id="evento_ubicacion" name="evento_ubicacion" placeholder="Ubicación del evento" class="evento-input">
            </div>
            
            <div class="evento-field">
                <label for="evento_descripcion">Descripción:</label>
                <textarea id="evento_descripcion" name="evento_descripcion" placeholder="Describe los detalles del evento" class="evento-textarea"></textarea>
            </div>
            
            <button type="button" id="cancelEventoBtn" class="cancel-evento-btn">Cancelar</button>
        </div>

        <button type="submit" id="submitBtn">Publicar</button>
    </form>

    <h3>Publicaciones</h3>
    <div id="feed">
        <?php while ($fila = $resultado->fetch_assoc()) {
            $foto_perfil = isset($fila['Foto_Perfil']) && !empty($fila['Foto_Perfil']) ? $fila['Foto_Perfil'] : 'uploads/default.jpg';
            $es_evento = ($fila['Tipo'] === 'evento');
        ?>
        <div class='publicacion <?php echo $es_evento ? 'publicacion-evento' : ''; ?>' id='post_<?php echo $fila['Id_publicacion']; ?>'>
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

<!-- Script específico para hacer funcionar el botón de eventos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventoBtn = document.getElementById('eventoBtn');
    const eventoForm = document.getElementById('evento-form');
    const tipoInput = document.getElementById('tipo-publicacion');
    const submitBtn = document.getElementById('submitBtn');
    const cancelBtn = document.getElementById('cancelEventoBtn');
    
    function mostrarFormularioEvento() {
        eventoForm.style.display = 'block';
        tipoInput.value = 'evento';  // Cambia esto a 'evento'
        submitBtn.textContent = 'Publicar evento';
        submitBtn.classList.add('publicar-evento');
    }
    
    function ocultarFormularioEvento() {
        eventoForm.style.display = 'none';
        tipoInput.value = 'normal';
        submitBtn.textContent = 'Publicar';
        submitBtn.classList.remove('publicar-evento');
    }
    
    if (eventoBtn) {
        eventoBtn.addEventListener('click', function() {
            if (eventoForm.style.display === 'none' || eventoForm.style.display === '') {
                mostrarFormularioEvento();
            } else {
                ocultarFormularioEvento();
            }
        });
    }
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', ocultarFormularioEvento);
    }
    
    const form = document.getElementById('publish-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            // Validar solo si es de tipo evento
            if (tipoInput.value === 'evento') {
                const titulo = document.getElementById('evento_titulo').value.trim();
                const fecha = document.getElementById('evento_fecha').value.trim();
                
                // Campos obligatorios
                let camposValidos = true;
                
                // Validar título
                if (!titulo) {
                    document.getElementById('evento_titulo').classList.add('campo-requerido');
                    camposValidos = false;
                }
                
                // Validar fecha
                if (!fecha) {
                    document.getElementById('evento_fecha').classList.add('campo-requerido');
                    camposValidos = false;
                }
                
                if (!camposValidos) {
                    event.preventDefault();
                    
                    // Mostrar mensaje de error
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Para crear un evento, necesitas al menos indicar un título y una fecha.';
                    
                    // Eliminar mensaje anterior si existe
                    const oldError = document.querySelector('.error-message');
                    if (oldError) {
                        oldError.remove();
                    }
                    
                    // Insertar mensaje antes del botón de envío
                    form.insertBefore(errorMsg, submitBtn);
                    
                    // Scroll hacia el mensaje de error
                    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    return false;
                }
            }
        });
    }
    
    // Quitar resaltado de error al escribir
    document.querySelectorAll('.evento-input').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('campo-requerido');
        });
    });
});
    // Configurar evento para el botón de cancelar
    if (cancelBtn) {
        cancelBtn.addEventListener('click', ocultarFormularioEvento);
    }
    
    // Validación del formulario
    const form = document.getElementById('publish-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            // Validar solo si es de tipo evento
            if (tipoInput.value === 'evento') {
                const titulo = document.getElementById('evento_titulo').value.trim();
                const fecha = document.getElementById('evento_fecha').value.trim();
                
                if (!titulo || !fecha) {
                    event.preventDefault();
                    
                    // Mostrar mensaje de error
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'error-message';
                    errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> Para crear un evento, necesitas al menos indicar un título y una fecha.';
                    
                    // Eliminar mensaje anterior si existe
                    const oldError = document.querySelector('.error-message');
                    if (oldError) {
                        oldError.remove();
                    }
                    
                    // Insertar mensaje antes del botón de envío
                    form.insertBefore(errorMsg, submitBtn);
                    
                    // Resaltar campos obligatorios
                    if (!titulo) {
                        document.getElementById('evento_titulo').classList.add('campo-requerido');
                    }
                    if (!fecha) {
                        document.getElementById('evento_fecha').classList.add('campo-requerido');
                    }
                    
                    // Scroll hacia el mensaje de error
                    errorMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    return false;
                }
            }
        });
    }
    
    // Quitar resaltado de error al escribir
    document.querySelectorAll('.evento-input').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('campo-requerido');
        });
    });
});
</script>

<!-- Las funciones para toggleLike, toggleCommentBox, etc. están en script.js -->
</body>
</html>
