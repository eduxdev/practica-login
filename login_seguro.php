<?php
// ✅ LOGIN SEGURO - Fase 2 del laboratorio
// Implementa: Prepared Statements, password_verify (bcrypt), límite de intentos

session_start();

$error = '';
$exito = '';
$bloqueado = false;
$modo = $_GET['modo'] ?? 'login';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('config/conexion.php');
    $accion = $_POST['accion'] ?? 'login';

    if ($accion === 'registro') {
        $usuario = trim($_POST['reg_usuario'] ?? '');
        $correo = trim($_POST['reg_correo'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $password2 = $_POST['reg_password2'] ?? '';
        $modo = 'registro';

        if (empty($usuario) || empty($password) || empty($correo)) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'La contraseña debe incluir al menos una letra mayúscula.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'La contraseña debe incluir al menos una letra minúscula.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'La contraseña debe incluir al menos un número.';
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $error = 'La contraseña debe incluir al menos un carácter especial (!@#$%&*).';
        } elseif ($password !== $password2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            $stmt = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE usuario = ?");
            mysqli_stmt_bind_param($stmt, "s", $usuario);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($resultado) > 0) {
                $error = 'Ese nombre de usuario ya existe.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $rol = 'cliente';
                $estado = 'activo';
                $stmt2 = mysqli_prepare($conexion, "INSERT INTO usuarios (usuario, password, correo, rol, estado) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt2, "sssss", $usuario, $hash, $correo, $rol, $estado);

                if (mysqli_stmt_execute($stmt2)) {
                    $exito = "Cuenta creada exitosamente. Tu contraseña fue hasheada con bcrypt. ¡Ahora inicia sesión!";
                    $modo = 'login';
                } else {
                    $error = 'Error al crear la cuenta: ' . mysqli_error($conexion);
                }
                mysqli_stmt_close($stmt2);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_close($conexion);

    } elseif ($accion === 'login' && !$bloqueado) {
        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($usuario) || empty($password)) {
            $error = 'Debe ingresar usuario y contraseña.';
        } else {
            $stmt = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE usuario = ?");
            mysqli_stmt_bind_param($stmt, "s", $usuario);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);

            if ($resultado && mysqli_num_rows($resultado) > 0) {
                $row = mysqli_fetch_assoc($resultado);

                if ($row['estado'] === 'bloqueado') {
                    $error = 'Su cuenta está bloqueada. Contacte al administrador.';
                } elseif (password_verify($password, $row['password'])) {
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
        }
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

        <?php if (!empty($exito)): ?>
            <div class="info-alert"><i class="fas fa-check-circle"></i> <?php echo $exito; ?></div>
        <?php endif; ?>

        <div class="login-tabs">
            <a href="?modo=login" class="login-tab <?php echo $modo === 'login' ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </a>
            <a href="?modo=registro" class="login-tab <?php echo $modo === 'registro' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus"></i> Crear Cuenta
            </a>
        </div>

        <?php if ($modo === 'login'): ?>
            <div class="info-alert">
                <i class="fas fa-shield-halved"></i> Login protegido con Prepared Statements, bcrypt y límite de intentos.
            </div>

            <form method="POST" action="https://192.168.1.5/control/login_seguro.php" <?php echo $bloqueado ? 'style="opacity:0.5;pointer-events:none;"' : ''; ?>>
                <input type="hidden" name="accion" value="login">
                <div class="form-group">
                    <label for="usuario"><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" name="usuario" id="usuario" placeholder="Ingrese su usuario" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="password" placeholder="Ingrese su contraseña" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login seguro" <?php echo $bloqueado ? 'disabled' : ''; ?>>
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>
        <?php else: ?>
            <div class="info-alert">
                <i class="fas fa-key"></i> Tu contraseña será hasheada con <strong>bcrypt</strong> antes de guardarse.
            </div>

            <form method="POST" action="https://192.168.1.5/control/login_seguro.php?modo=registro">
                <input type="hidden" name="accion" value="registro">
                <div class="form-group">
                    <label for="reg_usuario"><i class="fas fa-user"></i> Usuario</label>
                    <input type="text" name="reg_usuario" id="reg_usuario" placeholder="Elige un nombre de usuario" required>
                </div>
                <div class="form-group">
                    <label for="reg_correo"><i class="fas fa-envelope"></i> Correo</label>
                    <input type="email" name="reg_correo" id="reg_correo" placeholder="tucorreo@ejemplo.com" required>
                </div>
                <div class="form-group">
                    <label for="reg_password"><i class="fas fa-lock"></i> Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="reg_password" id="reg_password" placeholder="Mín. 8 chars, A-Z, a-z, 0-9, !@#$%" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('reg_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="password-rules">
                    <small><i class="fas fa-info-circle"></i> Mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial</small>
                </div>
                <div class="form-group">
                    <label for="reg_password2"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="reg_password2" id="reg_password2" placeholder="Repite tu contraseña" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('reg_password2', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-login seguro">
                    <i class="fas fa-user-plus"></i> Crear Cuenta
                </button>
            </form>
        <?php endif; ?>

        <div class="login-footer">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
        </div>
    </div>
    <script>
    function togglePassword(inputId, btn) {
        var input = document.getElementById(inputId);
        var icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
    </script>
</body>
</html>
