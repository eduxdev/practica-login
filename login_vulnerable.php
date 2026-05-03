<?php
// ⚠️ ADVERTENCIA: CÓDIGO INTENCIONALMENTE VULNERABLE
// SOLO PARA ENTORNO ACADÉMICO CONTROLADO - NO USAR EN PRODUCCIÓN
// Este archivo demuestra una implementación INSEGURA de login para fines educativos.
// Vulnerabilidades presentes: SQL Injection, contraseñas en texto plano.

session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Desactivar excepciones de mysqli para que los errores SQL no detengan la ejecución
    mysqli_report(MYSQLI_REPORT_OFF);

    include('config/conexion.php');

    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Debe ingresar usuario y contraseña.';
    } else {
        // ⚠️ VULNERABLE: Consulta construida por concatenación directa
        // Permite SQL Injection, por ejemplo:  ' OR 1=1#  o  ' OR '1'='1' -- 
        $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";

        $resultado = mysqli_query($conexion, $sql);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $row = mysqli_fetch_assoc($resultado);

            if ($row['estado'] === 'bloqueado') {
                $error = 'Su cuenta está bloqueada. Contacte al administrador.';
            } else {
                $_SESSION['id_usuario'] = $row['id_usuario'];
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['correo'] = $row['correo'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['login_tipo'] = 'vulnerable';
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $error = 'Usuario o contraseña incorrectos.';
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
    <title>Login Vulnerable - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-body vulnerable">
    <div class="login-card">
        <div class="bank-header">
            <i class="fas fa-landmark"></i>
            <h1>BANCO PATITO S.A. DE C.V.</h1>
            <p>Banca en Línea</p>
            <span class="login-badge badge-vulnerable"><i class="fas fa-unlock"></i> FASE 1 - LOGIN VULNERABLE</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-alert"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="warning-alert">
            <i class="fas fa-exclamation-triangle"></i> Este login es intencionalmente vulnerable a SQL Injection.
            Solo para fines educativos.
        </div>

        <form method="POST" action="login_vulnerable.php">
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
            <button type="submit" class="btn-login vulnerable">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

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
