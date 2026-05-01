<?php
include('config/auth.php');
verificar_rol(['admin']);
include('config/conexion.php');

$mensaje = '';
$hashes_generados = [];
$accion_realizada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generar_todos'])) {
        $res = mysqli_query($conexion, "SELECT id_usuario, usuario, password FROM usuarios");
        $actualizados = 0;

        while ($row = mysqli_fetch_assoc($res)) {
            if (!password_get_info($row['password'])['algo']) {
                $hash = password_hash($row['password'], PASSWORD_BCRYPT);
                $stmt = mysqli_prepare($conexion, "UPDATE usuarios SET password = ? WHERE id_usuario = ?");
                mysqli_stmt_bind_param($stmt, "si", $hash, $row['id_usuario']);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $hashes_generados[] = [
                    'usuario' => $row['usuario'],
                    'original' => $row['password'],
                    'hash' => $hash
                ];
                $actualizados++;
            }
        }

        $accion_realizada = true;
        $mensaje = $actualizados > 0
            ? "Se actualizaron $actualizados contraseñas a bcrypt."
            : "Todas las contraseñas ya están hasheadas.";

    } elseif (isset($_POST['crear_usuario'])) {
        $nuevo_usuario = $_POST['nuevo_usuario'] ?? '';
        $nuevo_password = $_POST['nuevo_password'] ?? '';
        $nuevo_correo = $_POST['nuevo_correo'] ?? '';
        $nuevo_rol = $_POST['nuevo_rol'] ?? 'cliente';

        if (!empty($nuevo_usuario) && !empty($nuevo_password)) {
            $hash = password_hash($nuevo_password, PASSWORD_BCRYPT);

            $stmt = mysqli_prepare($conexion, "INSERT INTO usuarios (usuario, password, correo, rol, estado) VALUES (?, ?, ?, ?, 'activo')");
            mysqli_stmt_bind_param($stmt, "ssss", $nuevo_usuario, $hash, $nuevo_correo, $nuevo_rol);

            if (mysqli_stmt_execute($stmt)) {
                $accion_realizada = true;
                $mensaje = "Usuario '$nuevo_usuario' creado con contraseña hasheada.";
                $hashes_generados[] = [
                    'usuario' => $nuevo_usuario,
                    'original' => $nuevo_password,
                    'hash' => $hash
                ];
            } else {
                $mensaje = "Error: " . mysqli_error($conexion);
            }
            mysqli_stmt_close($stmt);
        } else {
            $mensaje = "Debe ingresar usuario y contraseña.";
        }
    }
}

$pagina = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

$total_res = mysqli_query($conexion, "SELECT COUNT(*) as total FROM usuarios");
$total = mysqli_fetch_assoc($total_res)['total'];
$total_paginas = ceil($total / $por_pagina);

$usuarios_actuales = [];
$res = mysqli_query($conexion, "SELECT id_usuario, usuario, password, correo, rol, estado FROM usuarios ORDER BY id_usuario LIMIT $por_pagina OFFSET $offset");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $es_hash = (bool) password_get_info($row['password'])['algo'];
        $row['es_hash'] = $es_hash;
        $usuarios_actuales[] = $row;
    }
}

mysqli_close($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Hashes - BANCO PATITO</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'sidebar.php'; ?>

    <main class="dashboard-main">
        <div class="page-header">
            <h1><i class="fas fa-key"></i> Generador de Hashes bcrypt</h1>
            <p>Convierte contraseñas en texto plano a hashes bcrypt seguros</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="dashboard-section" style="padding:15px 20px;">
                <div class="<?php echo $accion_realizada ? 'info-alert' : 'error-alert'; ?>" style="margin:0;">
                    <?php echo $mensaje; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($hashes_generados)): ?>
            <div class="dashboard-section">
                <h2><i class="fas fa-check-circle"></i> Hashes Generados</h2>
                <div class="hash-result">
                    <?php foreach ($hashes_generados as $h): ?>
                    <div class="user-row">
                        <strong><?php echo htmlspecialchars($h['usuario']); ?></strong><br>
                        Original: <?php echo htmlspecialchars($h['original']); ?><br>
                        Hash: <?php echo htmlspecialchars($h['hash']); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="hash-grid">
            <!-- Hashear todas -->
            <div class="dashboard-section">
                <h2><i class="fas fa-sync-alt"></i> Hashear Contraseñas Existentes</h2>
                <p style="color:#64748b;margin-bottom:15px;font-size:0.9em;">Convierte todas las contraseñas en texto plano a bcrypt de una sola vez.</p>
                <form method="POST">
                    <button type="submit" name="generar_todos" class="btn-action btn-green">
                        <i class="fas fa-sync-alt"></i> Hashear Todas
                    </button>
                </form>
            </div>

            <!-- Crear nuevo usuario -->
            <div class="dashboard-section">
                <h2><i class="fas fa-user-plus"></i> Crear Nuevo Usuario</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="nuevo_usuario">Usuario</label>
                        <input type="text" name="nuevo_usuario" id="nuevo_usuario" placeholder="Nombre de usuario" required>
                    </div>
                    <div class="form-group">
                        <label for="nuevo_password">Contraseña</label>
                        <input type="text" name="nuevo_password" id="nuevo_password" placeholder="Se hasheará automáticamente" required>
                    </div>
                    <div class="form-group">
                        <label for="nuevo_correo">Correo</label>
                        <input type="email" name="nuevo_correo" id="nuevo_correo" placeholder="correo@ejemplo.com">
                    </div>
                    <div class="form-group">
                        <label for="nuevo_rol">Rol</label>
                        <select name="nuevo_rol" id="nuevo_rol" class="form-select">
                            <option value="admin">Administrador</option>
                            <option value="cajero">Cajero</option>
                            <option value="cliente" selected>Cliente</option>
                        </select>
                    </div>
                    <button type="submit" name="crear_usuario" class="btn-action btn-blue">
                        <i class="fas fa-user-plus"></i> Crear Usuario
                    </button>
                </form>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="dashboard-section">
            <h2><i class="fas fa-users"></i> Usuarios Actuales <small style="font-weight:400;color:#64748b;font-size:0.7em;">(<?php echo number_format($total); ?> registros)</small></h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Contraseña</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios_actuales)): ?>
                            <?php foreach ($usuarios_actuales as $u): ?>
                            <tr>
                                <td><?php echo $u['id_usuario']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['usuario']); ?></strong></td>
                                <td>
                                    <?php if ($u['es_hash']): ?>
                                        <span class="badge-estado badge-activo"><i class="fas fa-check-circle"></i> bcrypt</span>
                                    <?php else: ?>
                                        <span class="badge-estado badge-bloqueado"><i class="fas fa-exclamation-triangle"></i> Texto plano</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['correo']); ?></td>
                                <td><span class="badge-tipo"><?php echo htmlspecialchars($u['rol']); ?></span></td>
                                <td><span class="badge-estado badge-<?php echo $u['estado']; ?>"><?php echo htmlspecialchars($u['estado']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;color:#64748b;">No hay usuarios registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_paginas > 1): ?>
            <div class="pagination">
                <?php if ($pagina > 1): ?>
                    <a href="?p=<?php echo $pagina - 1; ?>">&laquo; Anterior</a>
                <?php endif; ?>
                <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <a href="?p=<?php echo $i; ?>" class="<?php echo $i === $pagina ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($pagina < $total_paginas): ?>
                    <a href="?p=<?php echo $pagina + 1; ?>">Siguiente &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
