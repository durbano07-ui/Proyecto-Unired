<?php
require_once "Database.php";
require_once "Post.php";

// Obtener la conexión a la base de datos
$database = new Database();
$conn = $database->getConnection();

// Instanciar la clase Post
$post = new Post($conn);

// Obtener las publicaciones (incluyendo las compartidas)
$sql = "SELECT p.Id_publicacion, p.Contenido, p.Imagen_url, p.Video_url, p.Fecha_Publicacion, u.Nombre AS Usuario
        FROM publicaciones p
        JOIN usuarios u ON p.Id_usuario = u.Id_usuario
        WHERE p.Id_usuario_compartido IS NULL
        ORDER BY p.Fecha_Publicacion DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Mostrar las publicaciones
while ($row = $result->fetch_assoc()) {
    echo "<div class='post'>";
    echo "<p><strong>" . $row['Usuario'] . "</strong> publicó:</p>";
    echo "<p>" . $row['Contenido'] . "</p>";
    if ($row['Imagen_url']) {
        echo "<img src='" . $row['Imagen_url'] . "' alt='Imagen de la publicación'>";
    }
    if ($row['Video_url']) {
        echo "<video controls><source src='" . $row['Video_url'] . "' type='video/mp4'></video>";
    }
    echo "<p><em>Publicado el: " . $row['Fecha_Publicacion'] . "</em></p>";

    // Mostrar comentarios
    $comentarios = $post->obtenerComentarios($row['Id_publicacion']);
    echo "<div class='comentarios'>";
    foreach ($comentarios as $comentario) {
        echo "<p><strong>" . $comentario['Nombre'] . ":</strong> " . $comentario['Comentario'] . "<br><em>Publicado el: " . $comentario['Fecha_Comentario'] . "</em></p>";
    }
    echo "</div>";

    // Formulario para agregar comentario
    echo "<form method='POST' action='agregar_comentario.php'>";
    echo "<input type='hidden' name='Id_publicacion' value='" . $row['Id_publicacion'] . "'>";
    echo "<input type='text' name='comentario' placeholder='Escribe tu comentario...' required>";
    echo "<button type='submit'>Comentar</button>";
    echo "</form>";

    // Botón para compartir la publicación
    echo "<form method='POST' action='compartir_publicacion.php'>";
    echo "<input type='hidden' name='Id_publicacion' value='" . $row['Id_publicacion'] . "'>";
    echo "<button type='submit'>Compartir</button>";
    echo "</form>";

    echo "</div>";
}
?>


