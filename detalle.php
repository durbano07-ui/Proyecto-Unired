
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
$sql = "SELECT p.Id_publicacion, p.Contenido, u.Nombre, p.Fecha_Publicacion, p.Imagen_url, p.Video_url 
        FROM publicaciones p 
        JOIN usuarios u ON p.Id_usuario = u.Id_usuario 
        WHERE p.Id_publicacion = ?";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Error al preparar la consulta: ' . $conn->error);
}

$stmt->bind_param("i", $postId);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();
} else {
    echo "No se encontraron detalles para esta publicación.";
    exit();
}

// Obtener los comentarios de la publicación
$sqlComentarios = "SELECT c.Contenido_C, u.Nombre, c.Fecha_Comentario
                   FROM comentarios c
                   JOIN usuarios u ON c.Id_usuario = u.Id_usuario
                   WHERE c.Id_publicacion = ?
                   ORDER BY c.Fecha_Comentario DESC";

$stmtComentarios = $conn->prepare($sqlComentarios);
if ($stmtComentarios === false) {
    die('Error al preparar la consulta de comentarios: ' . $conn->error);
}

$stmtComentarios->bind_param("i", $postId);
$stmtComentarios->execute();
$comentariosResultado = $stmtComentarios->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de la Publicación</title>
</head>
<body>
    <h1>Detalles de la Publicación</h1>

    <div class="publicacion">
        <h2><?php echo htmlspecialchars($fila['Contenido']); ?></h2>
        <p><strong><?php echo htmlspecialchars($fila['Nombre']); ?></strong> - <?php echo $fila['Fecha_Publicacion']; ?></p>
        
        <?php if (!empty($fila['Imagen_url'])) { ?>
            <img src="<?php echo htmlspecialchars($fila['Imagen_url']); ?>" alt="Imagen de la publicación">
        <?php } ?>

        <?php if (!empty($fila['Video_url'])) { ?>
            <video controls>
                <source src="<?php echo htmlspecialchars($fila['Video_url']); ?>" type="video/mp4">
            </video>
        <?php } ?>
    </div>

    <h3>Comentarios:</h3>
    <div class="comentarios">
        <?php
        if ($comentariosResultado->num_rows > 0) {
            while ($comentario = $comentariosResultado->fetch_assoc()) {
                echo "<div class='comentario'>";
                echo "<p><strong>" . htmlspecialchars($comentario['Nombre']) . "</strong> - " . $comentario['Fecha_Comentario'] . "</p>";
                echo "<p>" . htmlspecialchars($comentario['Contenido_C']) . "</p>";
                echo "</div>";
            }
        } else {
            echo "<p>No hay comentarios aún.</p>";
        }
        ?>
    </div>

    <a href="feed.php">Volver al Feed</a>
</body>
</html>
