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
    $where_parts[] = $id_cliente ? "p.id_cliente = $id_cliente" : "1=0";
}
if (!empty($buscar)) {
    $q = mysqli_real_escape_string($conexion, $buscar);
    $where_parts[] = "(cl.nombre LIKE '%$q%' OR cl.apellido_paterno LIKE '%$q%' OR p.estado LIKE '%$q%')";
}
$where = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM prestamos p LEFT JOIN clientes cl ON p.id_cliente = cl.id_cliente $where");
$total = mysqli_fetch_assoc($total_res)['total'];
$total_paginas = ceil($total / $por_pagina);

$sql = "SELECT p.*, cl.nombre, cl.apellido_paterno, cl.apellido_materno
        FROM prestamos p
        LEFT JOIN clientes cl ON p.id_cliente = cl.id_cliente
        $where ORDER BY p.id_prestamo DESC LIMIT $por_pagina OFFSET $offset";
$resultado = mysqli_query($conexion, $sql);
$prestamos = [];
if ($resultado) { while ($row = mysqli_fetch_assoc($resultado)) { $prestamos[] = $row; } }
mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $rol === 'cliente' ? 'Mis Préstamos' : 'Préstamos'; ?> - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-hand-holding-usd"></i> <?php echo $rol === 'cliente' ? 'Mis Préstamos' : 'Préstamos'; ?></h1>
            <p><?php echo number_format($total); ?> registros</p>
        </div>

        <div class="dashboard-section">
            <form method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Buscar por <?php echo $rol !== 'cliente' ? 'nombre del cliente o ' : ''; ?>estado..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if (!empty($buscar)): ?>
                    <a href="prestamos.php" class="btn-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if ($rol !== 'cliente'): ?><th>Cliente</th><?php endif; ?>
                            <th>Monto</th>
                            <th>Tasa Interés</th>
                            <th>Plazo (meses)</th>
                            <th>Estado</th>
                            <th>Fecha Solicitud</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestamos)): ?>
                            <?php foreach ($prestamos as $p): ?>
                            <tr>
                                <td><?php echo $p['id_prestamo']; ?></td>
                                <?php if ($rol !== 'cliente'): ?>
                                <td><?php echo htmlspecialchars($p['nombre'] . ' ' . $p['apellido_paterno'] . ' ' . $p['apellido_materno']); ?></td>
                                <?php endif; ?>
                                <td>$<?php echo number_format($p['monto'], 2); ?></td>
                                <td><?php echo $p['tasa_interes']; ?>%</td>
                                <td><?php echo $p['plazo_meses']; ?></td>
                                <td><span class="badge-estado badge-<?php echo $p['estado']; ?>"><?php echo htmlspecialchars($p['estado']); ?></span></td>
                                <td><?php echo $p['fecha_solicitud']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?php echo $rol !== 'cliente' ? 7 : 6; ?>" style="text-align:center;color:#64748b;">No se encontraron préstamos.</td></tr>
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
