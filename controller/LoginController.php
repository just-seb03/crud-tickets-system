<?php
session_start();
include_once '../sql/db.php';
include_once 'upload_helper.php'; 

$db = (new Database())->getConnection();

if (isset($_POST['login'])) {
    $correo = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("CALL sp_validar_login(:c, :p)");
        $stmt->bindParam(':c', $correo);
        $stmt->bindParam(':p', $password);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['user_id'] = $row['id_usuario'];
            $_SESSION['nombre'] = $row['nombre_usuario'];
            $_SESSION['rol'] = $row['rol_usuario'];
            $_SESSION['sede'] = $row['id_sede'];
            $_SESSION['foto'] = $row['foto_perfil']; 

            if ($row['rol_usuario'] == 'Gerente') header("Location: ../view/dashboard_gerente.php");
            elseif ($row['rol_usuario'] == 'Empleado') header("Location: ../view/dashboard_empleado.php");
            else header("Location: ../view/dashboard_cliente.php");
        } else {
            header("Location: ../view/login.php?error=Credenciales incorrectas");
        }
    } catch (Exception $e) {
        header("Location: ../view/login.php?error=Error del servidor: " . $e->getMessage());
    }
}

if (isset($_POST['registro_publico'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $telefono = $_POST['telefono'];
    $sede = $_POST['id_sede'];
    $password = $_POST['password']; 
    $fotoBase64 = $_POST['foto_base64'];

    try {
        $check = $db->prepare("SELECT id_cliente FROM cliente WHERE correo = ?");
        $check->execute([$email]);
        
        if($check->rowCount() > 0) {
            header("Location: ../view/registro.php?error=El correo ya esta registrado");
        } else {
            $nombreFoto = 'default.png'; 
            if (!empty($fotoBase64)) {
                 $nombreFoto = procesarYGuardarImagenBase64($fotoBase64);
            }

            $stmt = $db->prepare("INSERT INTO cliente (nombre, correo, numero_telefono, id_sede, hash_contrasena, foto_perfil) VALUES (?, ?, ?, ?, ?, ?)");
            
            if($stmt->execute([$nombre, $email, $telefono, $sede, $password, $nombreFoto])) {
                
                $nuevo_id = $db->lastInsertId(); 
                
                $_SESSION['user_id'] = $nuevo_id;
                $_SESSION['nombre'] = $nombre;
                $_SESSION['rol'] = 'Cliente';
                $_SESSION['sede'] = $sede;
                $_SESSION['foto'] = $nombreFoto;

                header("Location: ../view/dashboard_cliente.php");
                
            } else {
                header("Location: ../view/registro.php?error=Error al registrar en BD");
            }
        }
    } catch (Exception $e) {
        header("Location: ../view/registro.php?error=Error: " . $e->getMessage());
    }
}

if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ../view/login.php");
}
?>