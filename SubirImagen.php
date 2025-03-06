<?php
class SubirImagen {
    public static function guardarImagen($archivo) {
        if (isset($archivo) && $archivo["error"] === UPLOAD_ERR_OK) {
            $directorio = "uploads/";
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }

            $nombreArchivo = time() . "_" . basename($archivo["name"]);
            $rutaArchivo = $directorio . $nombreArchivo;

            if (move_uploaded_file($archivo["tmp_name"], $rutaArchivo)) {
                return $rutaArchivo;
            } else {
                return null;
            }
        }
        return null;
    }
}
?>
