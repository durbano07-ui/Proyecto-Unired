<?php
class SubirArchivo {
    private $directorioBase = "uploads/";
    
    public function __construct($directorio = null) {
        if ($directorio) {
            $this->directorioBase = $directorio;
        }
        
        if (!is_dir($this->directorioBase)) {
            mkdir($this->directorioBase, 0777, true);
        }
    }
    
    public function guardarArchivo($archivo) {
        if (isset($archivo) && $archivo["error"] === UPLOAD_ERR_OK) {
            $nombreArchivo = time() . "_" . basename($archivo["name"]);
            $rutaArchivo = $this->directorioBase . $nombreArchivo;

            if (move_uploaded_file($archivo["tmp_name"], $rutaArchivo)) {
                return $rutaArchivo;
            }
        }
        return null;
    }
    
    public function validarImagen($archivo) {
        // Implementar validación de tipo, tamaño, etc.
    }
}
?>