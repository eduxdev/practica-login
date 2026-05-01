<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

function obtener_id_cliente($conexion, $correo) {
    $stmt = mysqli_prepare($conexion, "SELECT id_cliente FROM clientes WHERE correo = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $correo);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? (int)$row['id_cliente'] : null;
}

function verificar_rol($roles_permitidos) {
    $rol = $_SESSION['rol'] ?? '';
    if (!in_array($rol, $roles_permitidos)) {
        header("Location: dashboard.php");
        exit();
    }
}
?>
