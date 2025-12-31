<?php
require_once '../../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Rutas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Gestor de Repartos</a>
            <div class="d-flex align-items-center">
                <a href="historial.php" class="nav-link text-white me-3">Historial Pedidos</a>
                <a href="historial_rutas.php" class="nav-link text-white me-3 active">Historial Rutas</a>
                <span class="navbar-text me-3">Hola, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <a href="../logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Historial de Rutas</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </div>
        <div id="historial-rutas-lista" class="list-group">
            <div class="list-group-item text-center">
                <div class="spinner-border text-primary mt-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="text-muted mt-2">Cargando historial de rutas...</p>
            </div>
        </div>
    </main>

    <script src="assets/js/historial_rutas.js"></script>
</body>
</html>