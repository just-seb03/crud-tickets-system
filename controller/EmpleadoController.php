<?php
session_start();
if($_SESSION['rol'] != 'Gerente') header("Location: ../view/login.php");
include_once '../sql/db.php';
$db = (new Database())->getConnection();

if (isset($_POST['crear'])) {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $pass = $_POST['password'];
    $sede = $_SESSION['sede'];
    $gerente = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("INSERT INTO empleado (nombre, correo, hash_contrasena, id_sede, id_gerente) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $correo, $pass, $sede, $gerente]);
        header("Location: ../view/dashboard_gerente.php?tab=empleados&msg=Creado");
    } catch (Exception $e) {
        header("Location: ../view/dashboard_gerente.php?tab=empleados&error=Error");
    }
}

if (isset($_POST['editar'])) {
    $id = $_POST['id_empleado'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    
    $stmt = $db->prepare("UPDATE empleado SET nombre = ?, correo = ? WHERE id_empleado = ?");
    $stmt->execute([$nombre, $correo, $id]);
    header("Location: ../view/dashboard_gerente.php?tab=empleados&msg=Actualizado");
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    $db->prepare("DELETE FROM empleado WHERE id_empleado = ?")->execute([$id]);
    header("Location: ../view/dashboard_gerente.php?tab=empleados&msg=Eliminado");
}
?>