<?php
session_start();
$conn = new mysqli("localhost", "root", "", "UniredBd");

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

if (!isset($_SESSION["Id_usuario"])) {
    die("Debes iniciar sesión\n");
}

$id_usuario = $_SESSION["Id_usuario"];  // Asegurarse de que se use 'Id_usuario'

// Preparamos la consulta para obtener los datos del usuario
$sql = "SELECT Nombre, Foto_Perfil, Biografia FROM Usuarios WHERE Id_usuario = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$stmt->bind_param("i", $id_usuario);  // Usar 'i' para entero
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

// Obtener las publicaciones del usuario
$sql_posts = "SELECT Contenido, Fecha_Publicacion, Imagen_url, Video_url FROM Publicaciones WHERE Id_usuario = ? ORDER BY Fecha_Publicacion DESC";
$stmt_posts = $conn->prepare($sql_posts);

if ($stmt_posts === false) {
    die("Error en la preparación de la consulta de publicaciones: " . $conn->error);
}

$stmt_posts->bind_param("i", $id_usuario);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();
$stmt_posts->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Usuario</title>
    <style>
        body { 
    font-family: 'Arial', sans-serif; 
    background: #f4f6f9; 
    margin: 0; 
    padding: 0; 
    color: #333;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    height: 100vh;
    background: linear-gradient(135deg, #a1c4fd, #c2e9fb); /* Fondo degradado atractivo */
    padding-top: 50px; /* Espacio para que no quede pegado al borde superior */
}

.container { 
    width: 80%; 
    max-width: 900px; 
    margin: 50px auto; 
    background: #fff; 
    padding: 40px; 
    border-radius: 15px; 
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1); 
    transition: transform 0.3s ease-in-out;
}

.container:hover {
    transform: translateY(-10px);
}

h2 { 
    font-size: 32px; 
    color: #3b5998; 
    text-align: center;
    margin-bottom: 20px;
    font-weight: bold;
}

.profile-header { 
    text-align: center; 
    margin-bottom: 40px; 
    position: relative;
}

.profile-header img { 
    width: 180px; 
    height: 180px; 
    border-radius: 50%; 
    object-fit: cover; 
    border: 6px solid #3b5998; 
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s ease-in-out;
}

.profile-header img:hover {
    transform: scale(1.1);
}

.profile-header h3 { 
    font-size: 26px; 
    color: #3b5998; 
    margin-top: 20px; 
    font-weight: bold;
    letter-spacing: 1px;
}

.bio { 
    font-size: 18px; 
    color: #555;
    padding: 25px;
    border-radius: 8px;
    background-color: #f9f9f9;
    margin-bottom: 40px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); 
    transition: background-color 0.3s ease;
}

.bio:hover {
    background-color: #e9f1fb;
}

.bio h4 {
    font-size: 24px;
    color: #3b5998;
    margin-bottom: 15px;
    font-weight: bold;
}

.posts-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.post { 
    background: #fff; 
    padding: 25px; 
    border-radius: 8px; 
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.post:hover {
    transform: translateY(-5px);
}

.post h5 { 
    color: #3b5998; 
    font-size: 20px; 
    margin-bottom: 12px;
    font-weight: bold;
}

.post p { 
    font-size: 16px; 
    color: #555;
    line-height: 1.6;
}

.post .date { 
    font-size: 14px; 
    color: #888; 
    text-align: right;
    margin-top: 12px;
    font-style: italic;
}

.container-footer {
    text-align: center;
    margin-top: 30px;
    color: #888;
    font-size: 14px;
}

.container-footer a {
    text-decoration: none;
    color: #3b5998;
    font-weight: bold;
    margin-top: 15px;
    display: inline-block;
}

.container-footer a:hover {
    text-decoration: underline;
}

/* Estilo para las publicaciones con imagen */
.post img { 
    margin-top: 20px;
    border-radius: 8px;
    max-width: 100%;
    height: auto;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.post img:hover {
    transform: scale(1.05);
}

    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
        <img src="<?php echo htmlspecialchars($usuario['Foto_Perfil']); ?>" alt="Foto Perfil" style="max-width: 100%; height: auto;">
            <h3><?php echo $usuario['Nombre']; ?></h3>
        </div>

        <div class="bio">
            <h4>Biografía</h4>
            <p><?php echo $usuario['Biografia']; ?></p>
        </div>

        <h2>Publicaciones</h2>
        
        <?php if ($result_posts->num_rows > 0): ?>
            <?php while ($post = $result_posts->fetch_assoc()): ?>
                <div class="post">
                    <h5>Contenido:</h5>
                    <p><?php echo htmlspecialchars($post['Contenido']); ?></p>

                    <!-- Mostrar imagen si la URL no está vacía -->
                    <?php if (!empty($post['Imagen_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['Imagen_url']); ?>" alt="Imagen de publicación" style="max-width: 100%; height: auto;">
                    <?php else: ?>
                        <p>No hay imagen para esta publicación.</p>
                    <?php endif; ?>

                    <!-- Mostrar video si la URL no está vacía -->
                    <?php if (!empty($post['Video_url'])): ?>
                        <video controls style="max-width: 100%; height: auto;">
                            <source src="uploads/<?php echo htmlspecialchars($post['Video_url']); ?>" type="video/mp4">
                            Tu navegador no soporta el elemento de video.
                        </video>
                    <?php else: ?>
                        <p>No hay video para esta publicación.</p>
                    <?php endif; ?>
                    
                    <div class="date">Publicado el: <?php echo date("d/m/Y", strtotime($post['Fecha_Publicacion'])); ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No tienes publicaciones aún.</p>
        <?php endif; ?>

        <div class="container-footer">
            <p>Powered by Unired. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
