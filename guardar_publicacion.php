<?php
require_once "Database.php";
session_start();

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$postId = $data['id_publicacion'];
$userId = $_SESSION['Id_usuario'];

$sql = "SELECT Contenido, Imagen_url, Video_url FROM publicaciones WHERE Id_publicacion = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $post = $result->fetch_assoc();

    $sqlInsert = "INSERT INTO publicaciones (Id_usuario, Contenido, Imagen_url, Video_url, Fecha_Publicacion) 
                  VALUES (?, ?, ?, ?, NOW())";
    $stmtInsert = $conn->prepare($sqlInsert);
    $stmtInsert->bind_param("isss", $userId, $post['Contenido'], $post['Imagen_url'], $post['Video_url']);
    
    if ($stmtInsert->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
} else {
    echo json_encode(["success" => false]);
}


