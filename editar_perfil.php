<?php
require_once "Database.php";
session_start();

if (!isset($_SESSION['Id_usuario'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Obtener los datos actuales del usuario
$sql = "SELECT Nombre, Apellido, Email, Biografia, Foto_Perfil FROM usuarios WHERE Id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['Id_usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$mensaje = "";
$tipo_mensaje = "";

// Procesar el formulario cuando se envía
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $biografia = trim($_POST['biografia']);
    
    // Validar que los campos no estén vacíos
    if (empty($nombre) || empty($apellido)) {
        $mensaje = "Los campos de nombre y apellido son obligatorios.";
        $tipo_mensaje = "error";
    } else {
        // Manejar la carga de una nueva imagen de perfil
        $foto_perfil = $usuario['Foto_Perfil']; // Mantener la foto actual por defecto
        
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == 0) {
            $directorio_destino = "uploads/";
            $nombre_archivo = time() . "_" . basename($_FILES['foto_perfil']['name']);
            $ruta_archivo = $directorio_destino . $nombre_archivo;
            $tipo_archivo = strtolower(pathinfo($ruta_archivo, PATHINFO_EXTENSION));
            
            // Validar que sea una imagen
            $es_imagen = getimagesize($_FILES['foto_perfil']['tmp_name']);
            
            if ($es_imagen !== false) {
                // Verificar tamaño (máximo 5MB)
                if ($_FILES['foto_perfil']['size'] <= 5000000) {
                    // Verificar formato (jpg, jpeg, png, gif)
                    if ($tipo_archivo == "jpg" || $tipo_archivo == "jpeg" || $tipo_archivo == "png" || $tipo_archivo == "gif") {
                        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta_archivo)) {
                            $foto_perfil = $ruta_archivo;
                            
                            // Eliminar la foto anterior si no es la predeterminada
                            if ($usuario['Foto_Perfil'] != 'uploads/default.jpg' && file_exists($usuario['Foto_Perfil'])) {
                                unlink($usuario['Foto_Perfil']);
                            }
                        } else {
                            $mensaje = "Error al subir la imagen.";
                            $tipo_mensaje = "error";
                        }
                    } else {
                        $mensaje = "Solo se permiten archivos JPG, JPEG, PNG y GIF.";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "La imagen no debe superar los 5MB.";
                    $tipo_mensaje = "error";
                }
            } else {
                $mensaje = "El archivo no es una imagen válida.";
                $tipo_mensaje = "error";
            }
        }
        
        // Si no hay errores, actualizar la información del usuario
        if (empty($mensaje)) {
            $sql_actualizar = "UPDATE usuarios SET Nombre = ?, Apellido = ?, Biografia = ?, Foto_Perfil = ? WHERE Id_usuario = ?";
            $stmt_actualizar = $conn->prepare($sql_actualizar);
            $stmt_actualizar->bind_param("ssssi", $nombre, $apellido, $biografia, $foto_perfil, $_SESSION['Id_usuario']);
            
            if ($stmt_actualizar->execute()) {
                // Actualizar la sesión con el nuevo nombre
                $_SESSION['nombre'] = $nombre;
                $_SESSION['foto_perfil'] = $foto_perfil;
                
                $mensaje = "Perfil actualizado correctamente.";
                $tipo_mensaje = "success";
                
                // Actualizar los datos del usuario para mostrar los cambios
                $usuario['Nombre'] = $nombre;
                $usuario['Apellido'] = $apellido;
                $usuario['Biografia'] = $biografia;
                $usuario['Foto_Perfil'] = $foto_perfil;
            } else {
                $mensaje = "Error al actualizar el perfil: " . $stmt_actualizar->error;
                $tipo_mensaje = "error";
            }
        }
    }
}

// Cambiar contraseña
if (isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    // Verificar que la nueva contraseña cumpla con los requisitos
    if (strlen($nueva_password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[A-Z]/', $nueva_password)) {
        $mensaje = "La contraseña debe contener al menos una letra mayúscula.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[a-z]/', $nueva_password)) {
        $mensaje = "La contraseña debe contener al menos una letra minúscula.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[0-9]/', $nueva_password)) {
        $mensaje = "La contraseña debe contener al menos un número.";
        $tipo_mensaje = "error";
    } elseif (!preg_match('/[\W_]/', $nueva_password)) {
        $mensaje = "La contraseña debe contener al menos un símbolo (como @, #, $, etc.).";
        $tipo_mensaje = "error";
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje = "Las contraseñas no coinciden.";
        $tipo_mensaje = "error";
    } else {
        // Verificar la contraseña actual
        $sql_password = "SELECT Contraseña FROM usuarios WHERE Id_usuario = ?";
        $stmt_password = $conn->prepare($sql_password);
        $stmt_password->bind_param("i", $_SESSION['Id_usuario']);
        $stmt_password->execute();
        $resultado_password = $stmt_password->get_result();
        $usuario_password = $resultado_password->fetch_assoc();
        
        if (password_verify($password_actual, $usuario_password['Contraseña'])) {
            // Encriptar la nueva contraseña
            $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
            
            // Actualizar la contraseña
            $sql_update_password = "UPDATE usuarios SET Contraseña = ? WHERE Id_usuario = ?";
            $stmt_update_password = $conn->prepare($sql_update_password);
            $stmt_update_password->bind_param("si", $hashed_password, $_SESSION['Id_usuario']);
            
            if ($stmt_update_password->execute()) {
                $mensaje = "Contraseña actualizada correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar la contraseña: " . $stmt_update_password->error;
                $tipo_mensaje = "error";
            }
        } else {
            $mensaje = "La contraseña actual es incorrecta.";
            $tipo_mensaje = "error";
        }
    }
}

// Foto de perfil actual o predeterminada
$foto_perfil = !empty($usuario['Foto_Perfil']) ? $usuario['Foto_Perfil'] : 'uploads/default.jpg';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
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
            --success-color: #4CAF50;
            --error-color: #F44336;
        }

        .edit-profile-container {
            margin: 0 auto;
            max-width: 700px;
            padding: 20px;
            margin-left: 280px;
            transition: all 0.3s ease;
        }

        .edit-profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .back-button {
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

        .back-button:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateX(-3px);
        }

        .edit-profile-header h1 {
            font-size: 24px;
            margin: 0;
            color: var(--secondary-color);
        }

        .edit-profile-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
        }

        .edit-profile-card h2 {
            font-size: 18px;
            color: var(--secondary-color);
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-photo-section {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .current-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-right: 20px;
        }

        .photo-upload-container {
            flex-grow: 1;
        }

        .custom-file-upload {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary-light);
            color: var(--primary-color);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }

        .custom-file-upload:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .photo-hint {
            font-size: 12px;
            color: var(--light-text);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .submit-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
        }

        .submit-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(94, 211, 166, 0.3);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        .tab-container {
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
            padding-top: 20px;
        }

        .tab-content.active {
            display: block;
        }

        @media screen and (max-width: 1200px) {
            .edit-profile-container {
                margin-left: 100px;
                max-width: calc(100% - 120px);
            }
        }

        @media screen and (max-width: 768px) {
            .edit-profile-container {
                margin-left: 80px;
                max-width: calc(100% - 100px);
                padding: 10px;
            }

            .edit-profile-card {
                padding: 15px;
            }

            .profile-photo-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .current-photo {
                margin-bottom: 15px;
                margin-right: 0;
            }
        }

        @media screen and (max-width: 576px) {
            .edit-profile-container {
                margin-left: 70px;
                max-width: calc(100% - 80px);
                padding: 10px;
            }

            .tabs {
                flex-direction: column;
            }

            .tab {
                padding: 10px;
                border-bottom: none;
                border-left: 3px solid transparent;
            }

            .tab.active {
                border-bottom-color: transparent;
                border-left-color: var(--primary-color);
                background-color: var(--primary-light);
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

    <div class="edit-profile-container">
        <div class="edit-profile-header">
            <a href="perfil.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Editar Perfil</h1>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo $tipo_mensaje === 'success' ? 'alert-success' : 'alert-error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="profile-info">Información del Perfil</div>
                <div class="tab" data-tab="change-password">Cambiar Contraseña</div>
                <div class="tab" data-tab="privacy">Privacidad</div>
            </div>

            <div id="profile-info" class="tab-content active">
                <div class="edit-profile-card">
                    <h2>Información Personal</h2>
                    
                    <form action="editar_perfil.php" method="post" enctype="multipart/form-data">
                        <div class="profile-photo-section">
                            <img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" class="current-photo">
                            
                            <div class="photo-upload-container">
                                <label for="foto_perfil" class="custom-file-upload">
                                    <i class="fas fa-camera"></i> Cambiar foto
                                </label>
                                <input type="file" id="foto_perfil" name="foto_perfil" style="display: none;" accept="image/*">
                                <div class="photo-hint">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['Nombre']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario['Apellido']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Correo Electrónico</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['Email']); ?>" readonly>
                            <small class="form-text">El correo electrónico no se puede cambiar.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="biografia">Biografía</label>
                            <textarea id="biografia" name="biografia" class="form-control"><?php echo htmlspecialchars($usuario['Biografia']); ?></textarea>
                        </div>
                        
                        <button type="submit" class="submit-button">Guardar Cambios</button>
                    </form>
                </div>
            </div>

            <div id="change-password" class="tab-content">
                <div class="edit-profile-card">
                    <h2>Cambiar Contraseña</h2>
                    
                    <form action="editar_perfil.php" method="post">
                        <div class="form-group">
                            <label for="password_actual">Contraseña Actual</label>
                            <input type="password" id="password_actual" name="password_actual" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="nueva_password">Nueva Contraseña</label>
                            <input type="password" id="nueva_password" name="nueva_password" class="form-control" required>
                            <small class="form-text">La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmar_password">Confirmar Nueva Contraseña</label>
                            <input type="password" id="confirmar_password" name="confirmar_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="cambiar_password" class="submit-button">Cambiar Contraseña</button>
                    </form>
                </div>
            </div>

            <div id="privacy" class="tab-content">
                <div class="edit-profile-card">
                    <h2>Configuración de Privacidad</h2>
                    
                    <form action="editar_perfil.php" method="post">
                        <div class="form-group">
                            <label for="perfil_publico">Visibilidad del Perfil</label>
                            <div class="form-check">
                                <input type="checkbox" id="perfil_publico" name="perfil_publico" class="form-check-input" checked>
                                <label for="perfil_publico" class="form-check-label">Perfil público (cualquiera puede ver tus publicaciones)</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="mostrar_email">Correo Electrónico</label>
                            <div class="form-check">
                                <input type="checkbox" id="mostrar_email" name="mostrar_email" class="form-check-input">
                                <label for="mostrar_email" class="form-check-label">Mostrar mi correo electrónico en mi perfil</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notificaciones_email">Notificaciones por Email</label>
                            <div class="form-check">
                                <input type="checkbox" id="notificaciones_email" name="notificaciones_email" class="form-check-input" checked>
                                <label for="notificaciones_email" class="form-check-label">Recibir notificaciones por email</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notificaciones_actividad">Notificaciones de Actividad</label>
                            <div class="form-check">
                                <input type="checkbox" id="notificaciones_actividad" name="notificaciones_actividad" class="form-check-input" checked>
                                <label for="notificaciones_actividad" class="form-check-label">Notificarme cuando alguien comenta en mis publicaciones</label>
                            </div>
                        </div>
                        
                        <button type="submit" name="guardar_privacidad" class="submit-button">Guardar Configuración</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar el nombre del archivo seleccionado
            const fileInput = document.getElementById('foto_perfil');
            const fileLabel = document.querySelector('.custom-file-upload');
            
            fileInput.addEventListener('change', function() {
                if (fileInput.files.length > 0) {
                    fileLabel.innerHTML = '<i class="fas fa-camera"></i> ' + fileInput.files[0].name;
                } else {
                    fileLabel.innerHTML = '<i class="fas fa-camera"></i> Cambiar foto';
                }
            });
            
            // Funcionalidad para las pestañas
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remover la clase active de todas las pestañas y contenidos
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Añadir la clase active a la pestaña seleccionada y su contenido
                    this.classList.add('active');
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Ocultar alerta después de 5 segundos
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 1s';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            }
            
            // Validación de formulario para cambiar contraseña
            const changePasswordForm = document.querySelector('form[name="cambiar_password"]');
            if (changePasswordForm) {
                changePasswordForm.addEventListener('submit', function(event) {
                    const newPassword = document.getElementById('nueva_password').value;
                    const confirmPassword = document.getElementById('confirmar_password').value;
                    
                    if (newPassword !== confirmPassword) {
                        event.preventDefault();
                        alert('Las contraseñas no coinciden');
                    }
                });
            }
        });
    </script>
</body>
</html>