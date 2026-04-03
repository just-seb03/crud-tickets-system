<?php
session_start();

if(!isset($_SESSION['rol'])) header("Location: ../view/login.php");

include_once '../sql/db.php';
$db = (new Database())->getConnection();

if (isset($_POST['crear'])) {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $pass = $_POST['password'];
    $sede = $_SESSION['sede']; 

    try {
        $stmt = $db->prepare("INSERT INTO cliente (nombre, correo, numero_telefono, id_sede, hash_contrasena) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nombre, $correo, $telefono, $sede, $pass]);
        
        header("Location: ../view/dashboard_gerente.php?tab=clientes&msg=ClienteCreado");
    } catch (Exception $e) {
        header("Location: ../view/dashboard_gerente.php?tab=clientes&error=ErrorAlCrear");
    }
}

if (isset($_POST['editar'])) {
    $id = $_POST['id_cliente'];
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];

    try {
        $stmt = $db->prepare("UPDATE cliente SET nombre = ?, correo = ?, numero_telefono = ? WHERE id_cliente = ?");
        $stmt->execute([$nombre, $correo, $telefono, $id]);
        
        header("Location: ../view/dashboard_gerente.php?tab=clientes&msg=ClienteActualizado");
    } catch (Exception $e) {
        header("Location: ../view/dashboard_gerente.php?tab=clientes&error=ErrorAlEditar");
    }
}

if (isset($_GET['eliminar']) && $_SESSION['rol'] == 'Gerente') {
    $id = $_GET['eliminar'];
    try {
        $db->prepare("DELETE FROM estado_ticket WHERE id_ticket IN (SELECT id_ticket FROM ticket WHERE id_cliente = ?)")->execute([$id]);
        $db->prepare("DELETE FROM ticket WHERE id_cliente = ?")->execute([$id]);
        
        $db->prepare("DELETE FROM cliente WHERE id_cliente = ?")->execute([$id]);
        
        header("Location: ../view/dashboard_gerente.php?tab=clientes&msg=ClienteEliminado");
    } catch (Exception $e) {
        header("Location: ../view/dashboard_gerente.php?tab=clientes&error=NoSePudoEliminar");
    }
}
?>