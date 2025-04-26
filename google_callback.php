<?php
require_once "Database.php";
require_once "vendor/autoload.php";

session_start();

// Configuración para OAuth de Google
$client = new Google_Client();
$client->setClientId('992355462360-26cqqukout33it30h5l3biam5fnsaum7.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/PROYECTO%20Unired/PROYECTO%20RED%20SOCIAL/google_callback.php');
$client->addScope("email");
$client->addScope("profile");

// Verificar si hay un código de autorización
if (isset($_GET['code'])) {
    try {
        // Intercambia el código por un token de acceso
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Verifica si hay errores
        if (isset($token['error'])) {
            throw new Exception('Error en el token: ' . $token['error']);
        }
        
        $client->setAccessToken($token);
        
        // Obtén información del perfil
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;
        $picture = $google_account_info->picture;
        
        // Verifica si el usuario ya existe
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT Id_usuario, nombre, Foto_Perfil FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Usuario existente
            $user = $result->fetch_assoc();
            $_SESSION['Id_usuario'] = $user['Id_usuario'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['email'] = $email;
            $_SESSION['foto_perfil'] = $picture;
        } else {
            // Crear nuevo usuario
            $random_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
            
            $insert = $conn->prepare("INSERT INTO usuarios (nombre, email, Contraseña, Foto_Perfil, Fecha_Registro) VALUES (?, ?, ?, ?, NOW())");
            $insert->bind_param("ssss", $name, $email, $hashed_password, $picture);
            
            if ($insert->execute()) {
                $_SESSION['Id_usuario'] = $conn->insert_id;
                $_SESSION['nombre'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['foto_perfil'] = $picture;
            } else {
                throw new Exception("Error al crear el usuario: " . $insert->error);
            }
        }
        
        // Redirigir al feed
        header("Location: feed.php");
        exit();
        
    } catch (Exception $e) {
        // Error de autenticación, registra y redirige
        error_log("Error en Google OAuth: " . $e->getMessage());
        $_SESSION['error_message'] = "Error al iniciar sesión con Google: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
} else {
    // No hay código de autorización, redirigir a login
    header("Location: login.php");
    exit();
}
?>