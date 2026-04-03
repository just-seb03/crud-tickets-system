<?php
include_once '../sql/db.php';
$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT id_sede, nombre_sede FROM sede");
$stmt->execute();
$sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Universidad Veracruzana</title>
    <link rel="stylesheet" href="../resources/estilos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <style>
        .preview-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 20px auto;
        }
        .avatar-preview {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #6b8e76;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .btn-upload-label {
            display: block;
            text-align: center;
            background: #e0e0e0;
            padding: 8px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            width: 140px;
            margin: 0 auto 20px auto;
            color: #555;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-upload-label:hover { background: #d0d0d0; }
        
        select {
            width: 100%;
            padding: 14px 14px 14px 20px;
            border-radius: 50px;
            border: 2px solid #e0e0e0;
            background: #fff;
            font-size: 15px;
            outline: none;
            margin-bottom: 20px;
            appearance: none; 
            cursor: pointer;
            color: #555;
        }
        select:focus { border-color: #6b8e76; }
    </style>
</head>
<body>

<div class="login-container" style="max-width: 450px;">
    <div class="login-header">
        <h2>Crear Cuenta</h2>
        <p>Estudiantes y Clientes Externos</p>
    </div>
    
    <form action="../controller/LoginController.php" method="POST" class="login-form" id="formRegistro">
        
        <div class="preview-container">
            <img id="avatarPreview" src="../resources/user_img/default.png" class="avatar-preview">
        </div>
        
        <input type="file" id="imagenInput" class="inputfile" accept="image/*" style="display:none;">
        <label for="imagenInput" class="btn-upload-label">📷 Subir Foto</label>
        
        <input type="hidden" name="foto_base64" id="fotoBase64">
        <input type="hidden" name="registro_publico" value="1">

        <input type="text" name="nombre" placeholder="Nombre Completo" required>
        <input type="email" name="email" placeholder="Correo Electrónico" required>
        <input type="text" name="telefono" placeholder="Teléfono" required>

        <select name="id_sede" required>
            <option value="">Seleccione su Sede...</option>
            <?php foreach($sedes as $sede): ?>
                <option value="<?php echo $sede['id_sede']; ?>">
                    <?php echo $sede['nombre_sede']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="password" name="password" placeholder="Crear Contraseña" required>

        <button type="submit" class="btn-login" style="margin-top: 20px;">
            REGISTRARSE
        </button>
    </form>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="error-msg">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="login-footer">
        <p>¿Ya tienes cuenta?</p>
        <a href="login.php" style="color:#6b8e76; text-decoration:none; font-weight:bold;">Iniciar Sesión</a>
    </div>
</div>

<div class="modal-overlay" id="cropModal">
    <div class="modal-content">
        <h3 style="color:#333;">Ajustar Foto</h3>
        <p style="font-size:12px; color:#777; margin-bottom:10px;">Arrastra para recortar tu foto de perfil</p>
        
        <div class="img-container-crop">
            <img id="imageToCrop" src="" style="max-width: 100%;">
        </div>
        
        <div style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
            <button type="button" class="btn" id="btnCrop" style="background:#6b8e76; width:auto;">✓ Guardar Recorte</button>
            <button type="button" class="btn btn-danger" id="btnCancel" style="width:auto;">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    let cropper;
    const imagenInput = document.getElementById('imagenInput');
    const imageToCrop = document.getElementById('imageToCrop');
    const cropModal = document.getElementById('cropModal');
    const avatarPreview = document.getElementById('avatarPreview');
    const fotoBase64 = document.getElementById('fotoBase64');

    imagenInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];
            if(file.type.startsWith('image/')){
                 const reader = new FileReader();
                 reader.onload = function(event) {
                     imageToCrop.src = reader.result;
                     cropModal.style.display = 'flex'; 
                     
                     if(cropper) cropper.destroy();
                     cropper = new Cropper(imageToCrop, {
                         aspectRatio: 1, 
                         viewMode: 1,
                         minContainerWidth: 300,
                         minContainerHeight: 300
                     });
                 };
                 reader.readAsDataURL(file);
            }
        }
    });

    document.getElementById('btnCrop').addEventListener('click', function() {
        const canvas = cropper.getCroppedCanvas({
            width: 300, height: 300
        });
        
        const base64Image = canvas.toDataURL('image/jpeg', 0.85);
        fotoBase64.value = base64Image;
        
        avatarPreview.src = base64Image;
        
        cropModal.style.display = 'none';
    });

    document.getElementById('btnCancel').addEventListener('click', function() {
         cropModal.style.display = 'none';
         imagenInput.value = ''; 
         if(cropper) cropper.destroy();
    });
</script>

</body>
</html>