<?php
include('config/auth.php');
verificar_rol(['admin']);
include('config/conexion.php');

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nuevo_usuario = trim($_POST['nuevo_usuario'] ?? '');
    $nuevo_correo = trim($_POST['nuevo_correo'] ?? '');
    $nuevo_password = $_POST['nuevo_password'] ?? '';
    $nuevo_rol = $_POST['nuevo_rol'] ?? 'cliente';
    $nuevo_estado = $_POST['nuevo_estado'] ?? 'activo';

    $roles_validos = ['admin', 'cajero', 'cliente'];
    $estados_validos = ['activo', 'bloqueado'];

    if (empty($nuevo_usuario) || empty($nuevo_correo) || empty($nuevo_password)) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif (strlen($nuevo_password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres.';
        $tipo_mensaje = 'error';
    } elseif (!in_array($nuevo_rol, $roles_validos) || !in_array($nuevo_estado, $estados_validos)) {
        $mensaje = 'Rol o estado inválido.';
        $tipo_mensaje = 'error';
    } else {
        $stmt_check = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE usuario = ? OR correo = ?");
        mysqli_stmt_bind_param($stmt_check, "ss", $nuevo_usuario, $nuevo_correo);
        mysqli_stmt_execute($stmt_check);
        $res_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($res_check) > 0) {
            $mensaje = 'El usuario o correo ya existe.';
            $tipo_mensaje = 'error';
        } else {
            $stmt_insert = mysqli_prepare($conexion, "INSERT INTO usuarios (usuario, correo, password, rol, estado) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_insert, "sssss", $nuevo_usuario, $nuevo_correo, $nuevo_password, $nuevo_rol, $nuevo_estado);

            if (mysqli_stmt_execute($stmt_insert)) {
                $mensaje = "Usuario '$nuevo_usuario' creado exitosamente.";
                $tipo_mensaje = 'exito';
            } else {
                $mensaje = 'Error al crear el usuario: ' . mysqli_error($conexion);
                $tipo_mensaje = 'error';
            }
            mysqli_stmt_close($stmt_insert);
        }
        mysqli_stmt_close($stmt_check);
    }
}

$pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$buscar = $_GET['q'] ?? '';
$where = '';
if (!empty($buscar)) {
    $q = mysqli_real_escape_string($conexion, $buscar);
    $where = "WHERE usuario LIKE '%$q%' OR correo LIKE '%$q%' OR rol LIKE '%$q%'";
}

$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios $where");
$total = mysqli_fetch_assoc($total_res)['total'];
$total_paginas = ceil($total / $por_pagina);

$sql = "SELECT id_usuario, usuario, correo, rol, estado FROM usuarios $where ORDER BY id_usuario LIMIT $por_pagina OFFSET $offset";
$resultado = mysqli_query($conexion, $sql);
$usuarios = [];
if ($resultado) { while ($row = mysqli_fetch_assoc($resultado)) { $usuarios[] = $row; } }

$conteo_roles = [];
$res_roles = mysqli_query($conexion, "SELECT rol, COUNT(*) as total FROM usuarios GROUP BY rol");
if ($res_roles) { while ($row = mysqli_fetch_assoc($res_roles)) { $conteo_roles[$row['rol']] = $row['total']; } }

$conteo_estados = [];
$res_estados = mysqli_query($conexion, "SELECT estado, COUNT(*) as total FROM usuarios GROUP BY estado");
if ($res_estados) { while ($row = mysqli_fetch_assoc($res_estados)) { $conteo_estados[$row['estado']] = $row['total']; } }

mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
            <div>
                <h1><i class="fas fa-users"></i> Usuarios del Sistema</h1>
                <p><?php echo number_format($total); ?> usuarios registrados</p>
            </div>
            <button onclick="document.getElementById('modal-crear').style.display='flex'" class="btn-action btn-blue" style="width:auto;padding:10px 20px;">
                <i class="fas fa-user-plus"></i> Crear Usuario
            </button>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo $tipo_mensaje === 'exito' ? 'success-alert' : 'error-alert'; ?>" style="margin-bottom:20px;">
                <i class="fas fa-<?php echo $tipo_mensaje === 'exito' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Modal Crear Usuario -->
        <div id="modal-crear" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <div class="modal-header">
                    <h2><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h2>
                    <button onclick="document.getElementById('modal-crear').style.display='none'" class="modal-close">&times;</button>
                </div>
                <form method="POST" action="usuarios.php">
                    <input type="hidden" name="crear_usuario" value="1">
                    <div class="form-group">
                        <label for="nuevo_usuario"><i class="fas fa-user"></i> Usuario</label>
                        <input type="text" name="nuevo_usuario" id="nuevo_usuario" placeholder="Nombre de usuario" required>
                    </div>
                    <div class="form-group">
                        <label for="nuevo_correo"><i class="fas fa-envelope"></i> Correo electrónico</label>
                        <input type="email" name="nuevo_correo" id="nuevo_correo" placeholder="correo@ejemplo.com" required>
                    </div>
                    <div class="form-group">
                        <label for="nuevo_password"><i class="fas fa-lock"></i> Contraseña</label>
                        <input type="password" name="nuevo_password" id="nuevo_password" placeholder="Mínimo 6 caracteres" required minlength="6">
                        <small style="color:var(--texto-claro);font-size:0.8em;margin-top:4px;display:block;">
                            <i class="fas fa-key"></i> Se almacenará en texto plano
                        </small>
                    </div>
                    <div class="form-inline-grid">
                        <div class="form-group">
                            <label for="nuevo_rol"><i class="fas fa-id-badge"></i> Rol</label>
                            <select name="nuevo_rol" id="nuevo_rol" class="form-select">
                                <option value="cliente">Cliente</option>
                                <option value="cajero">Cajero</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nuevo_estado"><i class="fas fa-toggle-on"></i> Estado</label>
                            <select name="nuevo_estado" id="nuevo_estado" class="form-select">
                                <option value="activo">Activo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-action btn-green" style="margin-top:10px;">
                        <i class="fas fa-save"></i> Crear Usuario
                    </button>
                </form>
            </div>
        </div>

        <!-- Resumen por rol y estado -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#fce7f3;color:#db2777;"><i class="fas fa-user-shield"></i></div>
                <h3>Administradores</h3>
                <div class="stat-number"><?php echo $conteo_roles['admin'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-cash-register"></i></div>
                <h3>Cajeros</h3>
                <div class="stat-number"><?php echo $conteo_roles['cajero'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-user"></i></div>
                <h3>Clientes</h3>
                <div class="stat-number"><?php echo $conteo_roles['cliente'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
                <h3>Activos</h3>
                <div class="stat-number"><?php echo $conteo_estados['activo'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-ban"></i></div>
                <h3>Bloqueados</h3>
                <div class="stat-number"><?php echo $conteo_estados['bloqueado'] ?? 0; ?></div>
            </div>
        </div>

        <div class="dashboard-section">
            <form method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Buscar por usuario, correo o rol..." value="<?php echo htmlspecialchars($buscar); ?>">
                <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                <?php if (!empty($buscar)): ?>
                    <a href="usuarios.php" class="btn-clear"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios)): ?>
                            <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo $u['id_usuario']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['usuario']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                <td>
                                    <?php
                                    $rol_icon = match($u['rol']) {
                                        'admin' => '<i class="fas fa-user-shield"></i>',
                                        'cajero' => '<i class="fas fa-cash-register"></i>',
                                        default => '<i class="fas fa-user"></i>',
                                    };
                                    $rol_class = match($u['rol']) {
                                        'admin' => 'background:#fce7f3;color:#db2777;',
                                        'cajero' => 'background:#dbeafe;color:#2563eb;',
                                        default => 'background:#dcfce7;color:#16a34a;',
                                    };
                                    ?>
                                    <span class="badge-estado" style="<?php echo $rol_class; ?>"><?php echo $rol_icon . ' ' . htmlspecialchars($u['rol']); ?></span>
                                </td>
                                <td><span class="badge-estado badge-<?php echo $u['estado']; ?>"><?php echo htmlspecialchars($u['estado']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center;color:#64748b;">No se encontraron usuarios.</td></tr>
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
