<?php
if(!isset($_SESSION['user_id'])) header("Location: login.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial Ticket #<?php echo $datos_ticket['id_ticket']; ?></title>
    <link rel="stylesheet" href="../resources/estilos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .timeline { list-style: none; padding: 0; position: relative; }
        .timeline:before { content: ''; position: absolute; top: 0; bottom: 0; left: 20px; width: 2px; background: #ddd; }
        .timeline-item { margin-bottom: 20px; position: relative; padding-left: 50px; }
        .timeline-icon { position: absolute; left: 0; top: 0; width: 40px; height: 40px; border-radius: 50%; background: #6b8e76; color: white; display: flex; align-items: center; justify-content: center; z-index: 1; }
        .timeline-content { background: white; padding: 15px; border-radius: 10px; border: 1px solid #eee; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .timeline-date { font-size: 0.85em; color: #999; margin-bottom: 5px; display: block; }
        
        .info-card { background: #eef7f2; padding: 20px; border-radius: 15px; margin-bottom: 30px; border: 1px solid #6b8e76; display:flex; justify-content:space-between; align-items:center;}
    </style>
</head>
<body>
<div class="main-container">
    <div class="header">
        <h3>HISTORIAL TICKET #<?php echo $datos_ticket['id_ticket']; ?></h3>
        <a href="../view/dashboard_<?php echo strtolower($_SESSION['rol']); ?>.php" class="btn" style="width:auto; background:#777;">Volver</a>
    </div>

    <div class="content">
        <div class="info-card">
            <div>
                <h2 style="margin:0; color:#333;">Estado Actual: <span class="status-badge <?php echo $datos_ticket['estado_actual']=='Abierto'?'bg-abierto':($datos_ticket['estado_actual']=='En Proceso'?'bg-proceso':'bg-finalizado'); ?>"><?php echo $datos_ticket['estado_actual']; ?></span></h2>
                <p style="margin:5px 0 0 0; color:#555;">Cliente: <strong><?php echo $datos_ticket['nombre_cliente']; ?></strong></p>
                <p style="margin:0; color:#555;">Atendido por: <strong><?php echo $datos_ticket['nombre_empleado']; ?></strong></p>
            </div>
            <i class="fas fa-history fa-3x" style="color:#6b8e76; opacity:0.3;"></i>
        </div>

        <h3>Línea de Tiempo de Actividad</h3>
        
        <?php if(empty($historial)): ?>
            <p>No hay registros históricos para este ticket.</p>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach($historial as $evento): ?>
                    <li class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="timeline-content">
                            <span class="timeline-date">
                                <i class="far fa-clock"></i> <?php echo date("d/m/Y H:i A", strtotime($evento['fecha_estado'])); ?>
                            </span>
                            <p style="margin:0;"><?php echo nl2br($evento['nota']); ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
</body>
</html>