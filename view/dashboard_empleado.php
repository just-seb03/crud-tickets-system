<?php
session_start();
if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Empleado') header("Location: login.php");
include_once '../sql/db.php';
$db = (new Database())->getConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Empleado</title>
    <link rel="stylesheet" href="../resources/estilos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="main-container">
    <div class="header">
        <div style="display:flex; align-items:center; gap:15px;">
            <div style="position:relative; cursor:pointer;" onclick="abrirModalFoto()">
                <img src="../resources/user_img/<?php echo !empty($_SESSION['foto']) ? $_SESSION['foto'] : 'default.png'; ?>" class="profile-pic-header">
                <div style="position:absolute; bottom:0; right:0; background:#6b8e76; border-radius:50%; width:20px; height:20px; display:flex; justify-content:center; align-items:center; border:2px solid white;">
                    <i class="fas fa-camera" style="color:white; font-size:10px;"></i>
                </div>
            </div>
            <h3>EMPLEADO: <?php echo strtoupper($_SESSION['nombre']); ?></h3>
        </div>
        <a href="../controller/LoginController.php?logout=true" class="btn btn-danger" style="width:auto;">Salir</a>
    </div>

    <div class="content">
        <?php if(isset($_GET['msg'])): ?><p style="color:green;">✅ <?php echo $_GET['msg']; ?></p><?php endif; ?>
        <?php if(isset($_GET['error'])): ?><p style="color:red;">⚠️ <?php echo $_GET['error']; ?></p><?php endif; ?>

        <h3>Panel de Trabajo</h3>
        <table>
            <thead><tr><th>#</th><th>Cliente</th><th>Estado</th><th>Situación</th><th>Acción</th></tr></thead>
            <tbody>
                <?php
                $stmt = $db->prepare("CALL sp_dashboard_empleado(?)");
                $stmt->execute([$_SESSION['user_id']]);
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['id_ticket']; ?></td>
                        <td><?php echo $row['nombre_cliente']; ?></td>
                        <td><span class="status-badge <?php echo $row['estado']=='Abierto'?'bg-abierto':($row['estado']=='En Proceso'?'bg-proceso':'bg-finalizado'); ?>"><?php echo $row['estado']; ?></span></td>
                        <td><strong><?php echo $row['asignacion']; ?></strong></td>
                        <td>
                            <?php if($row['asignacion'] == 'DISPONIBLE'): ?>
                                <form action="../controller/TicketController.php" method="POST">
                                    <input type="hidden" name="id_ticket" value="<?php echo $row['id_ticket']; ?>">
                                    <button type="submit" name="tomar_ticket" class="btn" style="background:#5bc0de; width:auto;">✋ Tomar</button>
                                </form>
                            <?php elseif($row['asignacion'] == 'MÍO' && $row['estado'] != 'Finalizado'): ?>
                                <form action="../controller/TicketController.php" method="POST" style="display:flex; gap:5px;">
                                    <input type="hidden" name="id_ticket" value="<?php echo $row['id_ticket']; ?>">
                                    <input type="text" name="nota" placeholder="Nota..." required style="width:100px;">
                                    <select name="estado"><option value="En Proceso">Proceso</option><option value="Finalizado">Fin</option></select>
                                    <button type="submit" name="actualizar_estado" class="btn">Ok</button>
                                </form>
                            <?php else: ?>
                                <span style="color:green;">✔</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="modal-overlay" id="photoModal" style="z-index:2000;">
        <div class="modal-content">
            <h3>Cambiar Foto</h3>
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
                <label for="imagenInputUpdate" id="lblSelect">📷 Nueva Foto</label>
                <button type="button" class="btn" id="btnCropUpdate" style="display:none; width:auto; background:#6b8e76;">✓ Recortar</button>
                <button type="button" class="btn" id="btnSaveUpdate" style="display:none; width:auto; background:#5cb85c;">💾 Guardar</button>
                <button type="button" class="btn btn-danger" onclick="cerrarModalFoto()" style="width:auto;">Cancelar</button>
            </div>
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
    const btnSaveUpdate = document.getElementById('btnSaveUpdate');
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
        btnSaveUpdate.style.display = 'none';
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
        fotoBase64Update.value = canvas.toDataURL('image/jpeg', 0.8);
        avatarPreviewUpdate.src = fotoBase64Update.value;
        cropContainer.style.display = 'none';
        previewContainer.style.display = 'block';
        btnCropUpdate.style.display = 'none';
        btnSaveUpdate.style.display = 'inline-block';
        cropperUpdate.destroy();
    });

    btnSaveUpdate.addEventListener('click', function() { formUpdateFoto.submit(); });
</script>
</body>
</html>