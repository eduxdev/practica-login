<?php
// ✅ LOGIN SEGURO - Fase 2 del laboratorio
// Implementa: Prepared Statements, password_verify (bcrypt), límite de intentos

session_start();

$error = '';
$bloqueado = false;

$max_intentos = 5;
$tiempo_bloqueo = 60;

if (!isset($_SESSION['intentos_fallidos'])) {
    $_SESSION['intentos_fallidos'] = 0;
    $_SESSION['ultimo_intento'] = 0;
}

if ($_SESSION['intentos_fallidos'] >= $max_intentos) {
    $tiempo_transcurrido = time() - $_SESSION['ultimo_intento'];
    if ($tiempo_transcurrido < $tiempo_bloqueo) {
        $segundos_restantes = $tiempo_bloqueo - $tiempo_transcurrido;
        $bloqueado = true;
        $error = "Demasiados intentos fallidos. Intente de nuevo en $segundos_restantes segundos.";
    } else {
        $_SESSION['intentos_fallidos'] = 0;
        $_SESSION['ultimo_intento'] = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    include('config/conexion.php');

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Debe ingresar usuario y contraseña.';
    } else {
        // ✅ SEGURO: Prepared Statements con mysqli
        $stmt = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE usuario = ?");
        mysqli_stmt_bind_param($stmt, "s", $usuario);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $row = mysqli_fetch_assoc($resultado);

            if ($row['estado'] === 'bloqueado') {
                $error = 'Su cuenta está bloqueada. Contacte al administrador.';
            } elseif (password_verify($password, $row['password'])) {
                // ✅ Contraseña verificada con bcrypt
                $_SESSION['id_usuario'] = $row['id_usuario'];
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['correo'] = $row['correo'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['login_tipo'] = 'seguro';
                $_SESSION['intentos_fallidos'] = 0;
                header("Location: dashboard.php");
                exit();
            } else {
                $_SESSION['intentos_fallidos']++;
                $_SESSION['ultimo_intento'] = time();
                $restantes = $max_intentos - $_SESSION['intentos_fallidos'];
                $error = "Contraseña incorrecta. Intentos restantes: $restantes";
            }
        } else {
            $_SESSION['intentos_fallidos']++;
            $_SESSION['ultimo_intento'] = time();
            $restantes = $max_intentos - $_SESSION['intentos_fallidos'];
            $error = "Usuario no encontrado. Intentos restantes: $restantes";
        }

        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Seguro - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-body seguro">
    <div class="login-card">
        <div class="bank-header">
            <i class="fas fa-landmark"></i>
            <h1>BANCO PATITO S.A. DE C.V.</h1>
            <p>Banca en Línea</p>
            <span class="login-badge badge-seguro"><i class="fas fa-shield-halved"></i> FASE 2 - LOGIN SEGURO</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="info-alert">
            <i class="fas fa-shield-halved"></i> Login protegido con Prepared Statements, bcrypt y límite de intentos.
        </div>

        <form method="POST" action="" <?php echo $bloqueado ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
            <div class="form-group">
                <label for="usuario"><i class="fas fa-user"></i> Usuario</label>
                <input type="text" name="usuario" id="usuario" placeholder="Ingrese su usuario" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" name="password" id="password" placeholder="Ingrese su contraseña" required>
            </div>
            <button type="submit" class="btn-login seguro" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
        </div>
    </div>
</body>
</html>
