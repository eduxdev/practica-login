<?php
include('config/auth.php');
include('config/conexion.php');

$rol = $_SESSION['rol'] ?? '';
$id_cliente = null;
if ($rol === 'cliente') {
    $id_cliente = obtener_id_cliente($conexion, $_SESSION['correo'] ?? '');
}

$pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$buscar = $_GET['q'] ?? '';
$where_parts = [];
if ($rol === 'cliente') {
    $where_parts[] = $id_cliente ? "cu.id_cliente = $id_cliente" : "1=0";
}
if (!empty($buscar)) {
    $q = mysqli_real_escape_string($conexion, $buscar);
    $where_parts[] = "(cu.numero_cuenta LIKE '%$q%' OR t.tipo_transaccion LIKE '%$q%' OR t.descripcion LIKE '%$q%')";
}
$where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM transacciones t LEFT JOIN cuentas cu ON t.id_cuenta = cu.id_cuenta $where");
$total = mysqli_fetch_assoc($total_res)['total'];
$total_paginas = ceil($total / $por_pagina);

$sql = "SELECT t.*, cu.numero_cuenta
        FROM transacciones t
        LEFT JOIN cuentas cu ON t.id_cuenta = cu.id_cuenta
        $where ORDER BY t.id_transaccion DESC LIMIT $por_pagina OFFSET $offset";
$resultado = mysqli_query($conexion, $sql);
$transacciones = [];
if ($resultado) { while ($row = mysqli_fetch_assoc($resultado)) { $transacciones[] = $row; } }
mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $rol === 'cliente' ? 'Mis Movimientos' : 'Transacciones'; ?> - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-exchange-alt"></i> <?php echo $rol === 'cliente' ? 'Mis Movimientos' : 'Transacciones'; ?></h1>
            <p><?php echo number_format($total); ?> registros</p>
        </div>

        <div class="dashboard-section">
            <form method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Buscar por cuenta, tipo o descripción..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if (!empty($buscar)): ?>
                    <a href="transacciones.php" class="btn-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

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
                        <?php if (!empty($transacciones)): ?>
                            <?php foreach ($transacciones as $t): ?>
                            <tr>
                                <td><?php echo $t['id_transaccion']; ?></td>
                                <td><?php echo htmlspecialchars($t['numero_cuenta'] ?? 'N/A'); ?></td>
                                <td><span class="badge-tipo"><?php echo htmlspecialchars($t['tipo_transaccion']); ?></span></td>
                                <td>$<?php echo number_format($t['monto'], 2); ?></td>
                                <td><?php echo $t['fecha_transaccion']; ?></td>
                                <td><?php echo htmlspecialchars($t['descripcion'] ?? ''); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;color:#64748b;">No se encontraron transacciones.</td></tr>
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
</body>
</html>
