<?php 
session_start();
$conn = new mysqli("localhost", "root", "", "UniredBd");

if (!isset($_SESSION["id_usuario"])){
    die("Debes iniciar sesión\n");
}
$id_usuario = $_SESSION["id_usuario"];
$sql = "SELECT Nombre, Foto_Perfil, Biografia FROM Usuarios WHERE Id_usuarios = ?";
$stmt = $conn->prepare($sql);
$stmt = bind_param("1", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .container { width: 50%; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 5px rgba(0, 0, 0, 0.1); }
        img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; display: block; margin: 10px auto; }
        input, textarea { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ccc; border-radius: 5px; }
        .btn { background: blue; color: white; padding: 10px; border: none; cursor: pointer; margin-top: 10px; display: block; width: 100%; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Perfil</h2>
        <img src="uploads"/<?php echo $usuario['Foto_Perfil']; ?> alt = "Foto de perfil">
        <form action="guardar_perfil.php" method="POST" enctype="multipart/form-data">
            <label>Foto de perfil:</label>
            <input type="file" name="foto_perfil">
            <label>Biografía:</label>
            <textarea name="biografia" rows="3"><?php echo $usuario['Biografia']; ?></textarea>
            <button class="btn" type="submit">Guardar Cambios</button>
        </form>
    </div>

</body>
</html>