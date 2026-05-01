<?php
include('config/auth.php');
include('config/conexion.php');

$rol = $_SESSION['rol'] ?? '';
$correo = $_SESSION['correo'] ?? '';
$login_tipo = $_SESSION['login_tipo'] ?? 'N/A';

$id_cliente = null;
if ($rol === 'cliente') {
    $id_cliente = obtener_id_cliente($conexion, $correo);
}

if ($rol === 'admin') {
    $tablas = [
        'usuarios'       => ['icono' => 'fas fa-users',            'clase' => 'usuarios',       'label' => 'Usuarios'],
        'clientes'       => ['icono' => 'fas fa-user-tie',         'clase' => 'clientes',       'label' => 'Clientes'],
        'cuentas'        => ['icono' => 'fas fa-wallet',           'clase' => 'cuentas',        'label' => 'Cuentas'],
        'tarjetas'       => ['icono' => 'fas fa-credit-card',      'clase' => 'tarjetas',       'label' => 'Tarjetas'],
        'prestamos'      => ['icono' => 'fas fa-hand-holding-usd', 'clase' => 'prestamos',      'label' => 'Préstamos'],
        'transacciones'  => ['icono' => 'fas fa-exchange-alt',     'clase' => 'transacciones',  'label' => 'Transacciones'],
    ];
    $conteos = [];
    foreach ($tablas as $tabla => $info) {
        $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM $tabla");
        $conteos[$tabla] = $res ? mysqli_fetch_assoc($res)['total'] : 0;
    }
} elseif ($rol === 'cajero') {
    $tablas = [
        'clientes'       => ['icono' => 'fas fa-user-tie',         'clase' => 'clientes',       'label' => 'Clientes'],
        'cuentas'        => ['icono' => 'fas fa-wallet',           'clase' => 'cuentas',        'label' => 'Cuentas'],
        'tarjetas'       => ['icono' => 'fas fa-credit-card',      'clase' => 'tarjetas',       'label' => 'Tarjetas'],
        'transacciones'  => ['icono' => 'fas fa-exchange-alt',     'clase' => 'transacciones',  'label' => 'Transacciones'],
    ];
    $conteos = [];
    foreach ($tablas as $tabla => $info) {
        $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM $tabla");
        $conteos[$tabla] = $res ? mysqli_fetch_assoc($res)['total'] : 0;
    }
} else {
    $tablas = [];
    $conteos = [];
    if ($id_cliente) {
        $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM cuentas WHERE id_cliente = $id_cliente");
        $conteos['cuentas'] = $res ? mysqli_fetch_assoc($res)['total'] : 0;
        $tablas['cuentas'] = ['icono' => 'fas fa-wallet', 'clase' => 'cuentas', 'label' => 'Mis Cuentas'];

        $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM tarjetas WHERE id_cuenta IN (SELECT id_cuenta FROM cuentas WHERE id_cliente = $id_cliente)");
        $conteos['tarjetas'] = $res ? mysqli_fetch_assoc($res)['total'] : 0;
        $tablas['tarjetas'] = ['icono' => 'fas fa-credit-card', 'clase' => 'tarjetas', 'label' => 'Mis Tarjetas'];

        $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM prestamos WHERE id_cliente = $id_cliente");
        $conteos['prestamos'] = $res ? mysqli_fetch_assoc($res)['total'] : 0;
        $tablas['prestamos'] = ['icono' => 'fas fa-hand-holding-usd', 'clase' => 'prestamos', 'label' => 'Mis Préstamos'];

        $res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM transacciones WHERE id_cuenta IN (SELECT id_cuenta FROM cuentas WHERE id_cliente = $id_cliente)");
        $conteos['transacciones'] = $res ? mysqli_fetch_assoc($res)['total'] : 0;
        $tablas['transacciones'] = ['icono' => 'fas fa-exchange-alt', 'clase' => 'transacciones', 'label' => 'Mis Movimientos'];
    }
}

$ultimas_transacciones = [];
if ($rol === 'cliente' && $id_cliente) {
    $sql_trans = "SELECT t.id_transaccion, t.tipo_transaccion, t.monto, t.fecha_transaccion, t.descripcion, c.numero_cuenta
                  FROM transacciones t
                  LEFT JOIN cuentas c ON t.id_cuenta = c.id_cuenta
                  WHERE c.id_cliente = $id_cliente
                  ORDER BY t.fecha_transaccion DESC LIMIT 10";
} elseif ($rol === 'cliente' && !$id_cliente) {
    $sql_trans = null;
} else {
    $sql_trans = "SELECT t.id_transaccion, t.tipo_transaccion, t.monto, t.fecha_transaccion, t.descripcion, c.numero_cuenta
                  FROM transacciones t
                  LEFT JOIN cuentas c ON t.id_cuenta = c.id_cuenta
                  ORDER BY t.fecha_transaccion DESC LIMIT 10";
}
$res_trans = $sql_trans ? mysqli_query($conexion, $sql_trans) : null;
if ($res_trans) {
    while ($row = mysqli_fetch_assoc($res_trans)) {
        $ultimas_transacciones[] = $row;
    }
}

mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            <p>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>
                (<?php echo htmlspecialchars($rol); ?>)
                — Sesión vía
                <span style="color:<?php echo $login_tipo === 'seguro' ? '#16a34a' : '#dc2626'; ?>; font-weight:600;">
                    login <?php echo htmlspecialchars($login_tipo); ?>
                </span>
            </p>
        </div>

        <?php if ($rol === 'cliente' && !$id_cliente): ?>
            <div class="dashboard-section" style="padding:15px 20px;">
                <div class="warning-alert" style="margin:0;">
                    <i class="fas fa-exclamation-triangle"></i> No se encontró un perfil de cliente vinculado a tu correo. Contacta al administrador.
                </div>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <?php foreach ($tablas as $tabla => $info): ?>
            <div class="stat-card">
                <div class="stat-icon <?php echo $info['clase']; ?>">
                    <i class="<?php echo $info['icono']; ?>"></i>
                </div>
                <h3><?php echo $info['label']; ?></h3>
                <div class="stat-number"><?php echo number_format($conteos[$tabla] ?? 0); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-exchange-alt"></i> <?php echo $rol === 'cliente' ? 'Mis Últimos Movimientos' : 'Últimas Transacciones'; ?></h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cuenta</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ultimas_transacciones)): ?>
                            <?php foreach ($ultimas_transacciones as $t): ?>
                            <tr>
                                <td><?php echo $t['id_transaccion']; ?></td>
                                <td><?php echo htmlspecialchars($t['numero_cuenta'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($t['tipo_transaccion']); ?></td>
                                <td>$<?php echo number_format($t['monto'], 2); ?></td>
                                <td><?php echo $t['fecha_transaccion']; ?></td>
                                <td><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;color:#64748b;">No hay transacciones.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-section">
            <h2><i class="fas fa-info-circle"></i> Información del Laboratorio</h2>
            <p style="color:#64748b; line-height:1.6;">
                Este sistema es parte del laboratorio académico <strong>"Base de Datos Vulnerable y Segura con Simulación de Ataques Web"</strong>.
                La Fase 1 demuestra vulnerabilidades como SQL Injection y contraseñas en texto plano.
                La Fase 2 aplica defensas con Prepared Statements, bcrypt y límite de intentos.
            </p>
        </div>
    </main>
</body>
</html>
