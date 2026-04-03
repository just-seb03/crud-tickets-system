<?php
session_start();
if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Gerente') header("Location: login.php");
include_once '../sql/db.php';
$db = (new Database())->getConnection();
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'tickets';

$editData = null;
if(isset($_GET['editar_id'])) {
    $id = $_GET['editar_id'];
    if($tab == 'empleados') {
        $stmt = $db->prepare("SELECT * FROM empleado WHERE id_empleado = ?");
        $stmt->execute([$id]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($tab == 'clientes') {
        $stmt = $db->prepare("SELECT * FROM cliente WHERE id_cliente = ?");
        $stmt->execute([$id]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Gerente</title>
    <link rel="stylesheet" href="../resources/estilos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-pic-small { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; vertical-align: middle; margin-right: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<div class="main-container">
    
    <div class="header">
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="position:relative; cursor:pointer;" onclick="abrirModalFoto()">
                <img src="../resources/user_img/<?php echo !empty($_SESSION['foto']) ? $_SESSION['foto'] : 'default.png'; ?>" class="profile-pic-header" id="headerPic">
                <div style="position:absolute; bottom:0; right:0; background:#6b8e76; border-radius:50%; width:20px; height:20px; display:flex; justify-content:center; align-items:center; border:2px solid white;">
                    <i class="fas fa-camera" style="color:white; font-size:10px;"></i>
                </div>
            </div>
            <h3>GERENCIA: <?php echo strtoupper($_SESSION['nombre']); ?></h3>
        </div>
        <a href="../controller/LoginController.php?logout=true" class="btn btn-danger" style="width:auto;">Salir</a>
    </div>

    <div class="menu-bar">
        <a href="?tab=tickets" class="menu-item <?php echo $tab=='tickets'?'active':''; ?>">Tickets Sede</a>
        <a href="?tab=empleados" class="menu-item <?php echo $tab=='empleados'?'active':''; ?>">Empleados</a>
        <a href="?tab=clientes" class="menu-item <?php echo $tab=='clientes'?'active':''; ?>">Clientes</a>
    </div>

    <div class="content">
        <?php if(isset($_GET['msg'])): ?><p style="color:green; font-weight:bold;">✅ <?php echo htmlspecialchars($_GET['msg']); ?></p><?php endif; ?>
        <?php if(isset($_GET['error'])): ?><p style="color:red; font-weight:bold;">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></p><?php endif; ?>

        <?php if($tab == 'tickets'): ?>
            <h3>Gestión de Tickets</h3>
            <table>
                <thead><tr><th>ID</th><th>Cliente</th><th>Estado</th><th>Asignado A</th><th>Reasignar</th></tr></thead>
                <tbody>
                <?php
                $stmt = $db->prepare("CALL sp_gerente_ver_tickets_area(?)");
                $stmt->execute([$_SESSION['user_id']]);
                $tickets = $stmt->fetchAll();
                $stmt->closeCursor();

                $stmtE = $db->prepare("SELECT id_empleado, nombre FROM empleado WHERE id_sede = ?");
                $stmtE->execute([$_SESSION['sede']]);
                $empleados = $stmtE->fetchAll();

                if(count($tickets) > 0):
                    foreach($tickets as $t): ?>
                        <tr>
                            <td>
                                <a href="../controller/TicketController.php?historial=<?php echo $t['id_ticket']; ?>" style="text-decoration:none; color:#6b8e76; font-weight:bold;">
                                    #<?php echo $t['id_ticket']; ?> <i class="fas fa-search" style="font-size:12px;"></i>
                                </a>
                            </td>
                            <td><?php echo $t['nombre_cliente']; ?></td>
                            <td><span class="status-badge <?php echo $t['estado']=='Abierto'?'bg-abierto':($t['estado']=='En Proceso'?'bg-proceso':'bg-finalizado'); ?>"><?php echo $t['estado']; ?></span></td>
                            <td><?php echo $t['nombre_empleado']; ?></td>
                            <td>
                                <form action="../controller/TicketController.php" method="POST" style="display:flex; gap:5px;">
                                    <input type="hidden" name="id_ticket" value="<?php echo $t['id_ticket']; ?>">
                                    <select name="nuevo_trabajador" style="width:150px; margin:0;">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach($empleados as $e): ?>
                                            <option value="<?php echo $e['id_empleado']; ?>"><?php echo $e['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="reasignar" class="btn" style="padding:5px 10px; width:auto;">OK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#777;">No hay tickets en tu sede.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>

        <?php elseif($tab == 'empleados'): ?>
            <h3>Gestión de Empleados</h3>
            
            <div style="background:#f9f9f9; padding:20px; border-radius:15px; margin-bottom:20px; border:1px solid #ddd;">
                <strong><?php echo $editData ? 'Editar Empleado' : 'Registrar Nuevo Empleado'; ?>:</strong>
                
                <form action="../controller/EmpleadoController.php" method="POST" enctype="multipart/form-data" style="display:flex; gap:15px; align-items:center; flex-wrap:wrap; margin-top:10px;">
                    <?php if($editData): ?>
                        <input type="hidden" name="id_empleado" value="<?php echo $editData['id_empleado']; ?>">
                    <?php endif; ?>

                    <div style="flex:1; min-width:200px;">
                        <label style="font-size:12px; font-weight:bold; color:#555;">Foto (Opcional):</label>
                        <input type="file" name="foto" accept="image/*" style="padding:5px; background:white;">
                    </div>

                    <input type="text" name="nombre" placeholder="Nombre Completo" value="<?php echo $editData ? $editData['nombre'] : ''; ?>" required style="flex:1;">
                    <input type="email" name="correo" placeholder="Correo Electrónico" value="<?php echo $editData ? $editData['correo'] : ''; ?>" required style="flex:1;">
                    
                    <?php if(!$editData): ?>
                        <input type="password" name="password" placeholder="Contraseña" required style="flex:1;">
                    <?php endif; ?>

                    <div style="width:100%; text-align:right;">
                        <?php if($editData): ?>
                            <a href="?tab=empleados" class="btn btn-danger" style="width:auto; text-decoration:none; margin-right:10px;">Cancelar</a>
                        <?php endif; ?>
                        <button type="submit" name="<?php echo $editData ? 'editar' : 'crear'; ?>" class="btn" style="width:150px;">
                            <?php echo $editData ? 'Guardar Cambios' : 'Crear Empleado'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <table>
                <thead><tr><th>Empleado</th><th>Correo</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php
                $stmt = $db->prepare("CALL sp_gerente_ver_equipo(?)");
                $stmt->execute([$_SESSION['user_id']]);
                $lista = $stmt->fetchAll();
                $stmt->closeCursor();

                foreach($lista as $row): ?>
                    <tr>
                        <td>
                            <img src="../resources/user_img/<?php echo !empty($row['foto_perfil']) ? $row['foto_perfil'] : 'default.png'; ?>" class="profile-pic-small">
                            <?php echo $row['nombre']; ?>
                        </td>
                        <td><?php echo $row['correo']; ?></td>
                        <td>
                            <a href="?tab=empleados&editar_id=<?php echo $row['id_empleado']; ?>" class="btn" style="padding:5px 10px; text-decoration:none; background:#5bc0de;">Editar</a>
                            <a href="../controller/EmpleadoController.php?eliminar=<?php echo $row['id_empleado']; ?>" class="btn btn-danger" style="padding:5px 10px; text-decoration:none;" onclick="return confirm('¿Eliminar?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif($tab == 'clientes'): ?>
            <h3>Gestión de Clientes</h3>
            
            <?php if($editData): ?>
            <div style="background:#eef7f2; padding:20px; border-radius:15px; margin-bottom:20px; border:2px solid #6b8e76;">
                <strong style="display:block; margin-bottom:10px; font-size:1.1em; color:#6b8e76;">
                    ✏️ Editando Cliente: <?php echo $editData['nombre']; ?>
                </strong>
                
                <form action="../controller/ClienteController.php" method="POST">
                    <input type="hidden" name="id_cliente" value="<?php echo $editData['id_cliente']; ?>">
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" name="nombre" placeholder="Nombre" value="<?php echo $editData['nombre']; ?>" required style="flex:1;">
                        <input type="email" name="correo" placeholder="Correo" value="<?php echo $editData['correo']; ?>" required style="flex:1;">
                        <input type="text" name="telefono" placeholder="Teléfono" value="<?php echo $editData['numero_telefono']; ?>" style="flex:1;">
                    </div>
                    <div style="margin-top:10px; text-align:right;">
                        <a href="?tab=clientes" class="btn btn-danger" style="width:auto; text-decoration:none; margin-right:10px;">Cancelar</a>
                        <button type="submit" name="editar" class="btn" style="width:200px;">💾 Guardar Cambios</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <table>
                <thead><tr><th>Cliente</th><th>Correo</th><th>Teléfono</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php
                $stmt = $db->prepare("SELECT * FROM cliente WHERE id_sede = ?");
                $stmt->execute([$_SESSION['sede']]);
                $clientes = $stmt->fetchAll();
                $stmt->closeCursor();

                foreach($clientes as $row): ?>
                    <tr>
                        <td>
                            <img src="../resources/user_img/<?php echo !empty($row['foto_perfil']) ? $row['foto_perfil'] : 'default.png'; ?>" class="profile-pic-small">
                            <?php echo $row['nombre']; ?>
                        </td>
                        <td><?php echo $row['correo']; ?></td>
                        <td><?php echo $row['numero_telefono']; ?></td>
                        <td>
                            <a href="?tab=clientes&editar_id=<?php echo $row['id_cliente']; ?>" class="btn" style="padding:5px 10px; text-decoration:none; background:#5bc0de;">Editar</a>
                            <a href="../controller/ClienteController.php?eliminar=<?php echo $row['id_cliente']; ?>" class="btn btn-danger" style="padding:5px 10px; text-decoration:none;" onclick="return confirm('¿Eliminar?');">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="photoModal" style="z-index:2000;">
    <div class="modal-content">
        <h3>Cambiar Mi Foto de Perfil</h3>
        <form id="formUpdateFoto" action="../controller/PerfilController.php" method="POST">
            <input type="hidden" name="foto_base64" id="fotoBase64Update">
            <input type="hidden" name="actualizar_foto" value="1">
        </form>
        <div class="img-container-crop" id="cropContainer" style="display:none;"><img id="imageToCropUpdate" src=""></div>
        <div id="previewContainer" style="margin-bottom:20px;">
                <img id="avatarPreviewUpdate" src="../resources/user_img/<?php echo !empty($_SESSION['foto']) ? $_SESSION['foto'] : 'default.png'; ?>" style="width:150px; height:150px; border-radius:50%; border:3px solid #6b8e76; object-fit:cover;">
        </div>
        <div>
            <input type="file" id="imagenInputUpdate" class="inputfile" accept="image/*" />
            <label for="imagenInputUpdate" id="lblSelect">📷 Seleccionar Nueva</label>
            <button type="button" class="btn" id="btnCropUpdate" style="display:none; width:auto; background:#6b8e76;">✓ Recortar y Guardar</button>
            <button type="button" class="btn btn-danger" onclick="cerrarModalFoto()" style="width:auto;">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    const photoModal = document.getElementById('photoModal');
    const imagenInputUpdate = document.getElementById('imagenInputUpdate');
    const imageToCropUpdate = document.getElementById('imageToCropUpdate');
    const cropContainer = document.getElementById('cropContainer');
    const previewContainer = document.getElementById('previewContainer');
    const avatarPreviewUpdate = document.getElementById('avatarPreviewUpdate');
    const btnCropUpdate = document.getElementById('btnCropUpdate');
    const lblSelect = document.getElementById('lblSelect');
    const fotoBase64Update = document.getElementById('fotoBase64Update');
    const formUpdateFoto = document.getElementById('formUpdateFoto');
    let cropperUpdate;

    function abrirModalFoto() { photoModal.style.display = 'flex'; }
    function cerrarModalFoto() { photoModal.style.display = 'none'; resetModal(); }
    function resetModal() {
        if(cropperUpdate) cropperUpdate.destroy();
        imagenInputUpdate.value = '';
        cropContainer.style.display = 'none';
        previewContainer.style.display = 'block';
        btnCropUpdate.style.display = 'none';
        lblSelect.style.display = 'inline-block';
        avatarPreviewUpdate.src = "../resources/user_img/<?php echo !empty($_SESSION['foto']) ? $_SESSION['foto'] : 'default.png'; ?>";
    }

    imagenInputUpdate.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            if(file.type.startsWith('image/')){
                const reader = new FileReader();
                reader.onload = function(event) {
                    imageToCropUpdate.src = reader.result;
                    cropContainer.style.display = 'block';
                    previewContainer.style.display = 'none';
                    lblSelect.style.display = 'none';
                    btnCropUpdate.style.display = 'inline-block';
                    if(cropperUpdate) cropperUpdate.destroy();
                    cropperUpdate = new Cropper(imageToCropUpdate, { aspectRatio: 1, viewMode: 1, minContainerWidth: 300, minContainerHeight: 300 });
                };
                reader.readAsDataURL(file);
            }
        }
    });

    btnCropUpdate.addEventListener('click', function() {
        const canvas = cropperUpdate.getCroppedCanvas({ width: 300, height: 300 });
        fotoBase64Update.value = canvas.toDataURL('image/jpeg', 0.85);
        formUpdateFoto.submit();
    });
</script>
</body>
</html>