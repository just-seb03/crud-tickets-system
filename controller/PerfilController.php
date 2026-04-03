<?php
session_start();
include_once '../sql/db.php';
include_once 'upload_helper.php';

if (!isset($_SESSION['user_id'])) header("Location: ../view/login.php");

$db = (new Database())->getConnection();

if (isset($_POST['actualizar_foto'])) {
    $id_usuario = $_SESSION['user_id'];
    $rol = $_SESSION['rol'];
    $fotoBase64 = $_POST['foto_base64'];
    $fotoActual = $_SESSION['foto'];

    try {
        $nuevoNombre = procesarYGuardarImagenBase64($fotoBase64, $fotoActual);

        $tabla = '';
        $campoID = '';
        
        switch(strtolower($rol)) {
            case 'gerente':  $tabla = 'gerente';  $campoID = 'id_gerente'; break;
            case 'empleado': $tabla = 'empleado'; $campoID = 'id_empleado'; break;
            case 'cliente':  $tabla = 'cliente';  $campoID = 'id_cliente'; break;
        }

        $sql = "UPDATE $tabla SET foto_perfil = ? WHERE $campoID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$nuevoNombre, $id_usuario]);

        $_SESSION['foto'] = $nuevoNombre;

        header("Location: ../view/dashboard_{$tabla}.php?msg=Foto actualizada");

    } catch (Exception $e) {
        $tabla = strtolower($rol);
        header("Location: ../view/dashboard_{$tabla}.php?error=" . urlencode($e->getMessage()));
    }
}
?>