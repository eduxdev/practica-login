<?php
include('config/auth.php');
include('config/conexion.php');

$rol = $_SESSION['rol'] ?? '';
$id_cliente = null;
if ($rol === 'cliente') {
    $id_cliente = obtener_id_cliente($conexion, $_SESSION['correo'] ?? '');
}

$mensaje = '';
$tipo_mensaje = '';

// Crear cuenta (solo admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'admin' && isset($_POST['crear_cuenta'])) {
    $cl_id = (int)($_POST['id_cliente'] ?? 0);
    $tipo = $_POST['tipo_cuenta'] ?? '';
    $saldo = floatval($_POST['saldo'] ?? 0);

    if ($cl_id <= 0 || empty($tipo)) {
        $mensaje = 'Debe seleccionar un cliente y tipo de cuenta.';
        $tipo_mensaje = 'error';
    } else {
        // Generar número de cuenta único (10 dígitos)
        $numero = '10' . str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        $stmt = mysqli_prepare($conexion, "INSERT INTO cuentas (id_cliente, numero_cuenta, tipo_cuenta, saldo, fecha_apertura, estado) VALUES (?, ?, ?, ?, NOW(), 'activa')");
        mysqli_stmt_bind_param($stmt, "issd", $cl_id, $numero, $tipo, $saldo);

        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Cuenta $numero creada exitosamente.";
            $tipo_mensaje = 'success';
        } else {
            $mensaje = "Error: " . mysqli_error($conexion);
            $tipo_mensaje = 'error';
        }
        mysqli_stmt_close($stmt);
    }
}

// Listado de clientes para el formulario (solo admin)
$lista_clientes = [];
if ($rol === 'admin') {
    $res_cl = mysqli_query($conexion, "SELECT id_cliente, nombre, apellido_paterno, apellido_materno FROM clientes ORDER BY id_cliente DESC");
    if ($res_cl) { while ($row = mysqli_fetch_assoc($res_cl)) { $lista_clientes[] = $row; } }
}

$pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$buscar = $_GET['q'] ?? '';
$where_parts = [];
if ($rol === 'cliente') {
    $where_parts[] = $id_cliente ? "c.id_cliente = $id_cliente" : "1=0";
}
if (!empty($buscar)) {
    $q = mysqli_real_escape_string($conexion, $buscar);
    $where_parts[] = "(c.numero_cuenta LIKE '%$q%' OR cl.nombre LIKE '%$q%' OR cl.apellido_paterno LIKE '%$q%')";
}
$where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM cuentas c LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente $where");
$total = mysqli_fetch_assoc($total_res)['total'];
$total_paginas = ceil($total / $por_pagina);

$sql = "SELECT c.*, cl.nombre, cl.apellido_paterno, cl.apellido_materno
        FROM cuentas c
        LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
        $where ORDER BY c.id_cuenta DESC LIMIT $por_pagina OFFSET $offset";
$resultado = mysqli_query($conexion, $sql);
$cuentas = [];
if ($resultado) { while ($row = mysqli_fetch_assoc($resultado)) { $cuentas[] = $row; } }
mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $rol === 'cliente' ? 'Mis Cuentas' : 'Cuentas'; ?> - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-wallet"></i> <?php echo $rol === 'cliente' ? 'Mis Cuentas' : 'Cuentas'; ?></h1>
            <p><?php echo number_format($total); ?> registros</p>
        </div>

        <?php if (!empty($mensaje)): ?>
        <div class="dashboard-section" style="padding:15px 20px;">
            <div class="<?php echo $tipo_mensaje === 'success' ? 'info-alert' : 'error-alert'; ?>" style="margin:0;">
                <i class="fas fa-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $mensaje; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($rol === 'admin'): ?>
        <!-- Formulario para crear cuenta -->
        <div class="dashboard-section">
            <h2><i class="fas fa-plus-circle"></i> Crear Nueva Cuenta</h2>
            <form method="POST" class="form-inline-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="buscar_cliente"><i class="fas fa-user-tie"></i> Cliente</label>
                    <input type="text" id="buscar_cliente" placeholder="Escribe para buscar un cliente..." autocomplete="off" style="margin-bottom:6px;">
                    <select name="id_cliente" id="id_cliente" class="form-select" required>
                        <option value="">Selecciona un cliente</option>
                        <?php foreach ($lista_clientes as $cl): ?>
                        <option value="<?php echo $cl['id_cliente']; ?>">
                            <?php echo htmlspecialchars($cl['id_cliente'] . ' - ' . $cl['nombre'] . ' ' . $cl['apellido_paterno'] . ' ' . $cl['apellido_materno']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tipo_cuenta"><i class="fas fa-tag"></i> Tipo de Cuenta</label>
                    <select name="tipo_cuenta" id="tipo_cuenta" class="form-select" required>
                        <option value="ahorro">Ahorro</option>
                        <option value="debito">Débito</option>
                        <option value="nomina">Nómina</option>
                    </select>
                </div>
                <input type="hidden" name="saldo" value="0">
                <div class="form-group" style="align-self:end;">
                    <button type="submit" name="crear_cuenta" class="btn-action btn-blue">
                        <i class="fas fa-plus"></i> Crear Cuenta
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="dashboard-section">
            <form method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Buscar por número de cuenta<?php echo $rol !== 'cliente' ? ' o nombre del cliente' : ''; ?>..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if (!empty($buscar)): ?>
                    <a href="cuentas.php" class="btn-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Número de Cuenta</th>
                            <?php if ($rol !== 'cliente'): ?><th>Cliente</th><?php endif; ?>
                            <th>Tipo</th>
                            <th>Saldo</th>
                            <th>Fecha Apertura</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cuentas)): ?>
                            <?php foreach ($cuentas as $c): ?>
                            <tr>
                                <td><?php echo $c['id_cuenta']; ?></td>
                                <td><strong><?php echo htmlspecialchars($c['numero_cuenta']); ?></strong></td>
                                <?php if ($rol !== 'cliente'): ?>
                                <td><?php echo htmlspecialchars($c['nombre'] . ' ' . $c['apellido_paterno'] . ' ' . $c['apellido_materno']); ?></td>
                                <?php endif; ?>
                                <td><span class="badge-tipo"><?php echo htmlspecialchars($c['tipo_cuenta']); ?></span></td>
                                <td>$<?php echo number_format($c['saldo'], 2); ?></td>
                                <td><?php echo $c['fecha_apertura']; ?></td>
                                <td><span class="badge-estado badge-<?php echo $c['estado']; ?>"><?php echo htmlspecialchars($c['estado']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?php echo $rol !== 'cliente' ? 7 : 6; ?>" style="text-align:center;color:#64748b;">No se encontraron cuentas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?p=<?php echo $pagina - 1; ?>&q=<?php echo urlencode($buscar); ?>">&laquo; Anterior</a>
                <?php endif; ?>
                <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="?p=<?php echo $i; ?>&q=<?php echo urlencode($buscar); ?>" class="<?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?p=<?php echo $pagina + 1; ?>&q=<?php echo urlencode($buscar); ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
<?php if ($rol === 'admin'): ?>
<script>
document.getElementById('buscar_cliente')?.addEventListener('input', function() {
    const filtro = this.value.toLowerCase();
    const select = document.getElementById('id_cliente');
    const opciones = select.querySelectorAll('option');
    opciones.forEach(function(opt) {
        if (opt.value === '') return;
        opt.style.display = opt.textContent.toLowerCase().includes(filtro) ? '' : 'none';
    });
});
</script>
<?php endif; ?>
</body>
</html>
