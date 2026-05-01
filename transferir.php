<?php
include('config/auth.php');
include('config/conexion.php');

$rol = $_SESSION['rol'] ?? '';
$correo = $_SESSION['correo'] ?? '';

$id_cliente = null;
if ($rol === 'cliente') {
    $id_cliente = obtener_id_cliente($conexion, $correo);
}

$mis_cuentas = [];
if ($id_cliente) {
    $res = mysqli_query($conexion, "SELECT id_cuenta, numero_cuenta, tipo_cuenta, saldo FROM cuentas WHERE id_cliente = $id_cliente AND estado = 'activa'");
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $mis_cuentas[] = $row; } }
} elseif ($rol === 'cajero' || $rol === 'admin') {
    $res = mysqli_query($conexion, "SELECT c.id_cuenta, c.numero_cuenta, c.tipo_cuenta, c.saldo, cl.nombre, cl.apellido_paterno FROM cuentas c LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente WHERE c.estado = 'activa' ORDER BY c.numero_cuenta");
    if ($res) { while ($row = mysqli_fetch_assoc($res)) { $mis_cuentas[] = $row; } }
}

$mensaje = '';
$tipo_mensaje = '';
$transferencia_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cuenta_origen_id = (int)($_POST['cuenta_origen'] ?? 0);
    $cuenta_destino_num = trim($_POST['cuenta_destino'] ?? '');
    $monto = floatval($_POST['monto'] ?? 0);
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($cuenta_origen_id <= 0 || empty($cuenta_destino_num) || $monto <= 0) {
        $mensaje = 'Todos los campos son obligatorios y el monto debe ser mayor a 0.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar cuenta origen
        if ($rol === 'cliente' && $id_cliente) {
            $stmt = mysqli_prepare($conexion, "SELECT id_cuenta, numero_cuenta, saldo FROM cuentas WHERE id_cuenta = ? AND id_cliente = ? AND estado = 'activa'");
            mysqli_stmt_bind_param($stmt, "ii", $cuenta_origen_id, $id_cliente);
        } else {
            $stmt = mysqli_prepare($conexion, "SELECT id_cuenta, numero_cuenta, saldo FROM cuentas WHERE id_cuenta = ? AND estado = 'activa'");
            mysqli_stmt_bind_param($stmt, "i", $cuenta_origen_id);
        }
        mysqli_stmt_execute($stmt);
        $res_origen = mysqli_stmt_get_result($stmt);
        $cuenta_origen = mysqli_fetch_assoc($res_origen);
        mysqli_stmt_close($stmt);

        if (!$cuenta_origen) {
            $mensaje = 'La cuenta de origen no es válida.';
            $tipo_mensaje = 'error';
        } elseif ($cuenta_origen['saldo'] < $monto) {
            $mensaje = 'Saldo insuficiente. Saldo disponible: $' . number_format($cuenta_origen['saldo'], 2);
            $tipo_mensaje = 'error';
        } else {
            // Verificar cuenta destino
            $stmt = mysqli_prepare($conexion, "SELECT id_cuenta, numero_cuenta FROM cuentas WHERE numero_cuenta = ? AND estado = 'activa'");
            mysqli_stmt_bind_param($stmt, "s", $cuenta_destino_num);
            mysqli_stmt_execute($stmt);
            $res_destino = mysqli_stmt_get_result($stmt);
            $cuenta_destino = mysqli_fetch_assoc($res_destino);
            mysqli_stmt_close($stmt);

            if (!$cuenta_destino) {
                $mensaje = 'La cuenta destino no existe o no está activa.';
                $tipo_mensaje = 'error';
            } elseif ($cuenta_destino['id_cuenta'] === $cuenta_origen['id_cuenta']) {
                $mensaje = 'No puedes transferir a la misma cuenta.';
                $tipo_mensaje = 'error';
            } else {
                mysqli_begin_transaction($conexion);
                try {
                    // Descontar de origen
                    $stmt = mysqli_prepare($conexion, "UPDATE cuentas SET saldo = saldo - ? WHERE id_cuenta = ?");
                    mysqli_stmt_bind_param($stmt, "di", $monto, $cuenta_origen['id_cuenta']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Agregar a destino
                    $stmt = mysqli_prepare($conexion, "UPDATE cuentas SET saldo = saldo + ? WHERE id_cuenta = ?");
                    mysqli_stmt_bind_param($stmt, "di", $monto, $cuenta_destino['id_cuenta']);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    $desc_origen = !empty($descripcion) ? $descripcion : "Transferencia a cuenta " . $cuenta_destino['numero_cuenta'];
                    $desc_destino = !empty($descripcion) ? $descripcion : "Transferencia de cuenta " . $cuenta_origen['numero_cuenta'];

                    // Registrar transacción origen (retiro)
                    $stmt = mysqli_prepare($conexion, "INSERT INTO transacciones (id_cuenta, tipo_transaccion, monto, fecha_transaccion, descripcion) VALUES (?, 'transferencia', ?, NOW(), ?)");
                    mysqli_stmt_bind_param($stmt, "ids", $cuenta_origen['id_cuenta'], $monto, $desc_origen);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Registrar transacción destino (depósito)
                    $stmt = mysqli_prepare($conexion, "INSERT INTO transacciones (id_cuenta, tipo_transaccion, monto, fecha_transaccion, descripcion) VALUES (?, 'transferencia', ?, NOW(), ?)");
                    mysqli_stmt_bind_param($stmt, "ids", $cuenta_destino['id_cuenta'], $monto, $desc_destino);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    mysqli_commit($conexion);
                    $transferencia_ok = true;
                    $mensaje = "Transferencia exitosa de $" . number_format($monto, 2) . " de cuenta " . $cuenta_origen['numero_cuenta'] . " a cuenta " . $cuenta_destino['numero_cuenta'];
                    $tipo_mensaje = 'success';

                    // Recargar saldos
                    $mis_cuentas = [];
                    if ($id_cliente) {
                        $res = mysqli_query($conexion, "SELECT id_cuenta, numero_cuenta, tipo_cuenta, saldo FROM cuentas WHERE id_cliente = $id_cliente AND estado = 'activa'");
                        if ($res) { while ($row = mysqli_fetch_assoc($res)) { $mis_cuentas[] = $row; } }
                    } elseif ($rol === 'cajero' || $rol === 'admin') {
                        $res = mysqli_query($conexion, "SELECT c.id_cuenta, c.numero_cuenta, c.tipo_cuenta, c.saldo, cl.nombre, cl.apellido_paterno FROM cuentas c LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente WHERE c.estado = 'activa' ORDER BY c.numero_cuenta");
                        if ($res) { while ($row = mysqli_fetch_assoc($res)) { $mis_cuentas[] = $row; } }
                    }
                } catch (Exception $e) {
                    mysqli_rollback($conexion);
                    $mensaje = 'Error al realizar la transferencia: ' . $e->getMessage();
                    $tipo_mensaje = 'error';
                }
            }
        }
    }
}

mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferir - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-paper-plane"></i> Transferir Fondos</h1>
            <p>Envía dinero a otra cuenta del banco</p>
        </div>

        <?php if (!empty($mensaje)): ?>
        <div class="dashboard-section" style="padding:15px 20px;">
            <div class="<?php echo $tipo_mensaje === 'success' ? 'info-alert' : ($tipo_mensaje === 'error' ? 'error-alert' : 'warning-alert'); ?>" style="margin:0;">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $mensaje; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($rol === 'cliente' && !$id_cliente): ?>
        <div class="dashboard-section" style="padding:15px 20px;">
            <div class="warning-alert" style="margin:0;">
                <i class="fas fa-exclamation-triangle"></i> No se encontró un perfil de cliente vinculado. No puedes realizar transferencias.
            </div>
        </div>
        <?php elseif (empty($mis_cuentas)): ?>
        <div class="dashboard-section" style="padding:15px 20px;">
            <div class="warning-alert" style="margin:0;">
                <i class="fas fa-exclamation-triangle"></i> No tienes cuentas activas para realizar transferencias.
            </div>
        </div>
        <?php else: ?>

        <div class="hash-grid">
            <!-- Formulario de transferencia -->
            <div class="dashboard-section">
                <h2><i class="fas fa-paper-plane"></i> Nueva Transferencia</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="cuenta_origen"><i class="fas fa-wallet"></i> Cuenta Origen</label>
                        <select name="cuenta_origen" id="cuenta_origen" class="form-select" required>
                            <option value="">Selecciona una cuenta</option>
                            <?php foreach ($mis_cuentas as $c): ?>
                            <option value="<?php echo $c['id_cuenta']; ?>">
                                <?php echo $c['numero_cuenta'] . ' (' . $c['tipo_cuenta'] . ') - $' . number_format($c['saldo'], 2);
                                if (isset($c['nombre'])) echo ' - ' . $c['nombre'] . ' ' . $c['apellido_paterno'];
                                ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cuenta_destino"><i class="fas fa-user"></i> Cuenta Destino (número)</label>
                        <input type="text" name="cuenta_destino" id="cuenta_destino" placeholder="Ej: 1000000000123" required>
                    </div>
                    <div class="form-group">
                        <label for="monto"><i class="fas fa-dollar-sign"></i> Monto</label>
                        <input type="number" name="monto" id="monto" placeholder="0.00" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion"><i class="fas fa-comment"></i> Descripción (opcional)</label>
                        <input type="text" name="descripcion" id="descripcion" placeholder="Concepto de la transferencia">
                    </div>
                    <button type="submit" class="btn-action btn-blue">
                        <i class="fas fa-paper-plane"></i> Realizar Transferencia
                    </button>
                </form>
            </div>

            <!-- Mis cuentas -->
            <div class="dashboard-section">
                <h2><i class="fas fa-wallet"></i> <?php echo $rol === 'cliente' ? 'Mis Cuentas' : 'Cuentas Disponibles'; ?></h2>
                <?php foreach ($mis_cuentas as $c): ?>
                <div class="cuenta-card">
                    <div class="cuenta-card-header">
                        <span class="badge-tipo"><?php echo $c['tipo_cuenta']; ?></span>
                        <?php if (isset($c['nombre'])): ?>
                        <small style="color:#64748b;"><?php echo $c['nombre'] . ' ' . $c['apellido_paterno']; ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="cuenta-numero"><?php echo $c['numero_cuenta']; ?></div>
                    <div class="cuenta-saldo">$<?php echo number_format($c['saldo'], 2); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php endif; ?>
    </main>
</body>
</html>
