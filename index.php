<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BANCO PATITO S.A. DE C.V.</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="landing-body">
    <div class="landing-container">
        <div class="logo"><i class="fas fa-landmark"></i></div>
        <h1>BANCO PATITO S.A. DE C.V.</h1>
        <p class="subtitulo">Sistema de Banca en Línea</p>

        <div class="landing-buttons">
            <a href="login_vulnerable.php" class="btn-landing btn-vulnerable">
                <i class="fas fa-unlock"></i> Login Vulnerable (Fase 1)
            </a>
            <a href="login_seguro.php" class="btn-landing btn-seguro">
                <i class="fas fa-shield-halved"></i> Login Seguro (Fase 2)
            </a>
        </div>

        <div class="advertencia">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>SOLO PARA ENTORNO ACADÉMICO CONTROLADO.</strong><br>
            Este sistema es parte del laboratorio "Base de Datos Vulnerable y Segura con Simulación de Ataques Web".
            No utilizar en producción.
        </div>
    </div>
</body>
</html>
