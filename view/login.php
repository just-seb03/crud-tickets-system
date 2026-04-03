<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso UV - Rectoria</title>
    <link rel="stylesheet" href="../resources/estilos.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../resources/logo.png" alt="Logo UV" class="main-logo">
            <h2>Universidad Veracruzana</h2>
            <p>Sistema de Gestión de Rectoría</p>
        </div>
        
        <form action="../controller/LoginController.php" method="POST" class="login-form">
            
            <div class="input-wrapper">
                <img src="../resources/mail_icon.png" alt="Email" class="input-icon">
                <input type="email" name="email" placeholder="Correo Institucional" required autocomplete="off">
            </div>

            <div class="input-wrapper">
                <img src="../resources/lock_icon.png" alt="Password" class="input-icon">
                <input type="password" name="password" placeholder="Contraseña" required>
            </div>

            <button type="submit" name="login" class="btn-login">
                INGRESAR
            </button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="registro.php" style="text-decoration: none; color: #6b8e76; font-weight: bold; font-size: 14px;">
                ¿No tienes cuenta? Regístrate como Cliente
            </a>
        </div>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="error-msg">⚠️ <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_GET['msg'])): ?>
            <div style="margin-top:20px; background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:10px; font-size:13px; border:1px solid #c8e6c9;">
                ✅ <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>
        
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> Universidad Veracruzana</p>
        </div>
    </div>
</body>
</html>