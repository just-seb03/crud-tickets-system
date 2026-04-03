<?php

function procesarYGuardarImagenBase64($base64String, $fotoAnterior = 'default.png') {
    if (empty($base64String)) {
        return $fotoAnterior; 
    }

    $targetDir = __DIR__ . '/../resources/user_img/';
    
    if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
        $data = substr($base64String, strpos($base64String, ',') + 1);
        $type = strtolower($type[1]); 

        if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
             throw new Exception('Tipo de imagen no válido');
        }

        $data = base64_decode($data);

        if ($data === false) {
             throw new Exception('La decodificación de base64 falló');
        }
    } else {
         throw new Exception('Datos de imagen no válidos');
    }

    $fileName = uniqid('img_') . '.' . $type;
    $filePath = $targetDir . $fileName;

    if(file_put_contents($filePath, $data)) {
        if ($fotoAnterior != 'default.png' && file_exists($targetDir . $fotoAnterior)) {
            @unlink($targetDir . $fotoAnterior); 
        }
        return $fileName;
    } else {
        throw new Exception('No se pudo guardar la imagen en el servidor');
    }
}
?>