<?php
session_start();
include_once '../sql/db.php';
$db = (new Database())->getConnection();

if (isset($_GET['historial'])) {
    $id_ticket = $_GET['historial'];
    
    $stmt = $db->prepare("CALL sp_ver_historial_ticket(?)");
    $stmt->execute([$id_ticket]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (empty($historial)) {
        $datos_ticket = ['id_ticket' => $id_ticket, 'estado_actual' => 'Desconocido'];
    } else {
        $datos_ticket = $historial[0]; 
    }

    include '../view/historial_ticket.php';
    exit(); 
}

if (isset($_POST['crear_ticket'])) {
    $id_cliente = $_SESSION['user_id'];
    $stmt = $db->prepare("INSERT INTO ticket (id_cliente, estado) VALUES (?, 'Abierto')");
    $stmt->execute([$id_cliente]);
    header("Location: ../view/dashboard_cliente.php?msg=TicketCreado");
}

if (isset($_POST['actualizar_estado'])) {
    $id = $_POST['id_ticket'];
    $estado = $_POST['estado'];
    $nota = $_POST['nota'];

    $stmt = $db->prepare("CALL sp_proc_actualizar_estado(?, ?, ?)");
    $stmt->execute([$id, $estado, $nota]);
    header("Location: ../view/dashboard_empleado.php?msg=Actualizado");
}

if (isset($_POST['tomar_ticket'])) {
    $id_ticket = $_POST['id_ticket'];
    $id_empleado = $_SESSION['user_id'];
    
    $stmt = $db->prepare("UPDATE ticket SET id_trabajador = ? WHERE id_ticket = ?");
    $stmt->execute([$id_empleado, $id_ticket]);
    
    $nota = "Ticket tomado por empleado " . $_SESSION['nombre'];
    $stmt2 = $db->prepare("CALL sp_proc_actualizar_estado(?, 'En Proceso', ?)");
    $stmt2->execute([$id_ticket, $nota]);

    header("Location: ../view/dashboard_empleado.php?msg=Tomado");
}

if (isset($_POST['reasignar'])) {
    $ticket = $_POST['id_ticket'];
    $empleado = $_POST['nuevo_trabajador'];
    $nota = "Reasignación administrativa";
    $stmt = $db->prepare("CALL sp_proc_reasignar_empleado(?, ?, ?)");
    $stmt->execute([$ticket, $empleado, $nota]);
    header("Location: ../view/dashboard_gerente.php?tab=tickets");
}
?>