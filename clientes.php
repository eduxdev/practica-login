<?php
include('config/auth.php');
verificar_rol(['admin', 'cajero']);
include('config/conexion.php');

$rol = $_SESSION['rol'] ?? '';
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $rol === 'admin' && isset($_POST['crear_cliente'])) {
    $id_usuario_vinc = (int)($_POST['id_usuario_vincular'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $ap_paterno = trim($_POST['apellido_paterno'] ?? '');
    $ap_materno = trim($_POST['apellido_materno'] ?? '');
    $curp = strtoupper(trim($_POST['curp'] ?? ''));
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if (empty($id_usuario_vinc)) {
        $mensaje = 'Debe seleccionar un usuario para registrar el cliente.';
        $tipo_mensaje = 'error';
    } elseif (empty($nombre) || empty($ap_paterno)) {
        $mensaje = 'El nombre y apellido paterno son obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        $stmt_u = mysqli_prepare($conexion, "SELECT correo FROM usuarios WHERE id_usuario = ? AND rol = 'cliente'");
        mysqli_stmt_bind_param($stmt_u, "i", $id_usuario_vinc);
        mysqli_stmt_execute($stmt_u);
        $res_u = mysqli_stmt_get_result($stmt_u);
        $usuario_data = mysqli_fetch_assoc($res_u);
        mysqli_stmt_close($stmt_u);

        if (!$usuario_data) {
            $mensaje = 'El usuario seleccionado no existe o no tiene rol de cliente.';
            $tipo_mensaje = 'error';
        } else {
            $correo = $usuario_data['correo'];
            $stmt = mysqli_prepare($conexion, "INSERT INTO clientes (nombre, apellido_paterno, apellido_materno, curp, telefono, correo, direccion, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "sssssss", $nombre, $ap_paterno, $ap_materno, $curp, $telefono, $correo, $direccion);

            if (mysqli_stmt_execute($stmt)) {
                $mensaje = "Cliente '$nombre $ap_paterno' registrado y vinculado al usuario con correo '$correo'.";
                $tipo_mensaje = 'success';
            } else {
                $mensaje = "Error: " . mysqli_error($conexion);
                $tipo_mensaje = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$usuarios_disponibles = [];
$res_udisp = mysqli_query($conexion, "SELECT u.id_usuario, u.usuario, u.correo FROM usuarios u WHERE u.rol = 'cliente' AND u.correo NOT IN (SELECT correo FROM clientes WHERE correo IS NOT NULL AND correo != '') ORDER BY u.usuario");
if ($res_udisp) { while ($row = mysqli_fetch_assoc($res_udisp)) { $usuarios_disponibles[] = $row; } }

$pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes");
$total = mysqli_fetch_assoc($total_res)['total'];
$total_paginas = ceil($total / $por_pagina);

$buscar = $_GET['q'] ?? '';
$where = '';
if (!empty($buscar)) {
    $q = mysqli_real_escape_string($conexion, $buscar);
    $where = "WHERE nombre LIKE '%$q%' OR apellido_paterno LIKE '%$q%' OR apellido_materno LIKE '%$q%' OR curp LIKE '%$q%' OR correo LIKE '%$q%'";
    $total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM clientes $where");
    $total = mysqli_fetch_assoc($total_res)['total'];
    $total_paginas = ceil($total / $por_pagina);
}

$sql = "SELECT * FROM clientes $where ORDER BY id_cliente DESC LIMIT $por_pagina OFFSET $offset";
$resultado = mysqli_query($conexion, $sql);
$clientes = [];
if ($resultado) { while ($row = mysqli_fetch_assoc($resultado)) { $clientes[] = $row; } }
mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-user-tie"></i> Clientes</h1>
            <p><?php echo number_format($total); ?> registros en total</p>
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
        <div class="dashboard-section">
            <h2><i class="fas fa-user-plus"></i> Registrar Nuevo Cliente</h2>

            <?php if (!empty($usuarios_disponibles)): ?>
            <form method="POST">
                <div class="form-group" style="margin-bottom:15px;">
                    <label for="id_usuario_vincular" style="font-weight:600;color:var(--azul-medio);font-size:0.9em;">
                        <i class="fas fa-user"></i> Seleccionar usuario *
                    </label>
                    <select name="id_usuario_vincular" id="id_usuario_vincular" class="form-select" required>
                        <option value="">— Seleccione un usuario —</option>
                        <?php foreach ($usuarios_disponibles as $ud): ?>
                            <option value="<?php echo $ud['id_usuario']; ?>">
                                <?php echo htmlspecialchars($ud['usuario']); ?> (<?php echo htmlspecialchars($ud['correo']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="display:block;margin-top:6px;color:var(--texto-claro);font-size:0.8em;">
                        <i class="fas fa-info-circle"></i> Solo usuarios con rol "cliente" sin cliente asociado. El correo se toma del usuario.
                    </small>
                </div>
                <div class="form-inline-grid">
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" name="nombre" id="nombre" placeholder="Nombre(s)" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido_paterno">Apellido Paterno *</label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno" placeholder="Apellido paterno" required>
                    </div>
                    <div class="form-group">
                        <label for="apellido_materno">Apellido Materno</label>
                        <input type="text" name="apellido_materno" id="apellido_materno" placeholder="Apellido materno">
                    </div>
                    <div class="form-group">
                        <label for="curp">CURP</label>
                        <input type="text" name="curp" id="curp" placeholder="18 caracteres" maxlength="18" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" placeholder="10 dígitos">
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <input type="text" name="direccion" id="direccion" placeholder="Calle, número, colonia, ciudad...">
                    </div>
                </div>
                <div class="form-group" style="margin-top:10px;">
                    <button type="submit" name="crear_cliente" class="btn-action btn-blue" style="max-width:300px;">
                        <i class="fas fa-user-plus"></i> Registrar Cliente
                    </button>
                </div>
            </form>
            <?php else: ?>
            <div class="info-alert" style="margin:0;">
                <i class="fas fa-info-circle"></i> No hay usuarios con rol "cliente" disponibles. Primero cree un usuario en la <a href="usuarios.php" style="font-weight:600;">sección de Usuarios</a>.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-section">
            <form method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Buscar por nombre, CURP o correo..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if (!empty($buscar)): ?>
                    <a href="clientes.php" class="btn-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido Paterno</th>
                            <th>Apellido Materno</th>
                            <th>CURP</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clientes)): ?>
                            <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><?php echo $c['id_cliente']; ?></td>
                                <td><?php echo htmlspecialchars($c['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($c['apellido_paterno']); ?></td>
                                <td><?php echo htmlspecialchars($c['apellido_materno']); ?></td>
                                <td><?php echo htmlspecialchars($c['curp']); ?></td>
                                <td><?php echo htmlspecialchars($c['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($c['correo']); ?></td>
                                <td><?php echo $c['fecha_registro']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center;color:#64748b;">No se encontraron clientes.</td></tr>
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
