<?php
require_once "Database.php";
class Usuario {
    private $conn;

    public function __construct($database) {
        $this->conn = $database->getConnection(); // Obtener la conexión de la base de datos
    }

    public function registrarUsuario($nombre, $apellido, $email, $password, $biografia, $foto_perfil_url) {
        $stmt = $this->conn->prepare("INSERT INTO Usuarios (Nombre, Apellido, Email, Contraseña, Biografia, Foto_perfil) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nombre, $apellido, $email, $password, $biografia, $foto_perfil_url);
        return $stmt->execute();
    }

    public function iniciarSesion($email, $password) {
        $sql = "SELECT id, nombre, password FROM usuarios WHERE email = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
    
        if ($resultado->num_rows == 1) {
            $usuario = $resultado->fetch_assoc();
            if (password_verify($password, $usuario["password"])) {
                return true; // Credenciales correctas
            }
        }
        return false; // Credenciales incorrectas
    }    
}


?>
