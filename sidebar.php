<?php
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}
$_sidebar_usuario = $_SESSION['usuario'] ?? '';
$_sidebar_rol = $_SESSION['rol'] ?? '';
$_sidebar_pagina = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="bank-sidebar">
    <div class="sidebar-header">
        <i class="fas fa-landmark"></i>
        <h2>BANCO PATITO</h2>
        <p>S.A. DE C.V.</p>
    </div>

    <div class="sidebar-user">
        <div class="avatar"><?php echo strtoupper(substr($_sidebar_usuario, 0, 2)); ?></div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_sidebar_usuario); ?></span>
            <small><?php echo htmlspecialchars($_sidebar_rol); ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <ul>
            <li><a href="dashboard.php" class="<?php echo $_sidebar_pagina === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
        </ul>

        <?php if ($_sidebar_rol === 'admin'): ?>
            <div class="nav-section">Administración</div>
            <ul>
                <li><a href="usuarios.php" class="<?php echo $_sidebar_pagina === 'usuarios' ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Usuarios</span></a></li>
                <li><a href="clientes.php" class="<?php echo $_sidebar_pagina === 'clientes' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> <span>Clientes</span></a></li>
            </ul>
            <div class="nav-section">Operaciones</div>
            <ul>
                <li><a href="cuentas.php" class="<?php echo $_sidebar_pagina === 'cuentas' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> <span>Cuentas</span></a></li>
                <li><a href="tarjetas.php" class="<?php echo $_sidebar_pagina === 'tarjetas' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> <span>Tarjetas</span></a></li>
                <li><a href="prestamos.php" class="<?php echo $_sidebar_pagina === 'prestamos' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> <span>Préstamos</span></a></li>
                <li><a href="transacciones.php" class="<?php echo $_sidebar_pagina === 'transacciones' ? 'active' : ''; ?>"><i class="fas fa-exchange-alt"></i> <span>Transacciones</span></a></li>
            </ul>
            <div class="nav-section">Herramientas</div>
            <ul>
                <li><a href="generar_hashes.php" class="<?php echo $_sidebar_pagina === 'generar_hashes' ? 'active' : ''; ?>"><i class="fas fa-key"></i> <span>Generar Hashes</span></a></li>
            </ul>

        <?php elseif ($_sidebar_rol === 'cajero'): ?>
            <div class="nav-section">Operaciones</div>
            <ul>
                <li><a href="clientes.php" class="<?php echo $_sidebar_pagina === 'clientes' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> <span>Clientes</span></a></li>
                <li><a href="cuentas.php" class="<?php echo $_sidebar_pagina === 'cuentas' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> <span>Cuentas</span></a></li>
                <li><a href="tarjetas.php" class="<?php echo $_sidebar_pagina === 'tarjetas' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> <span>Tarjetas</span></a></li>
                <li><a href="transferir.php" class="<?php echo $_sidebar_pagina === 'transferir' ? 'active' : ''; ?>"><i class="fas fa-paper-plane"></i> <span>Transferir</span></a></li>
                <li><a href="transacciones.php" class="<?php echo $_sidebar_pagina === 'transacciones' ? 'active' : ''; ?>"><i class="fas fa-exchange-alt"></i> <span>Transacciones</span></a></li>
            </ul>

        <?php else: ?>
            <div class="nav-section">Mi Banco</div>
            <ul>
                <li><a href="cuentas.php" class="<?php echo $_sidebar_pagina === 'cuentas' ? 'active' : ''; ?>"><i class="fas fa-wallet"></i> <span>Mis Cuentas</span></a></li>
                <li><a href="tarjetas.php" class="<?php echo $_sidebar_pagina === 'tarjetas' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> <span>Mis Tarjetas</span></a></li>
                <li><a href="transferir.php" class="<?php echo $_sidebar_pagina === 'transferir' ? 'active' : ''; ?>"><i class="fas fa-paper-plane"></i> <span>Transferir</span></a></li>
                <li><a href="prestamos.php" class="<?php echo $_sidebar_pagina === 'prestamos' ? 'active' : ''; ?>"><i class="fas fa-hand-holding-usd"></i> <span>Mis Préstamos</span></a></li>
                <li><a href="transacciones.php" class="<?php echo $_sidebar_pagina === 'transacciones' ? 'active' : ''; ?>"><i class="fas fa-exchange-alt"></i> <span>Mis Movimientos</span></a></li>
            </ul>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Cerrar Sesión</span></a>
    </div>
</aside>
