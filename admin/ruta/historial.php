<?php
session_start();

// Si el usuario no ha iniciado sesión, redirigirlo a la página de login.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pedidos Entregados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Gestor de Repartos</a>
            <div class="d-flex align-items-center">
                <a href="historial.php" class="nav-link text-white me-3 active">Historial Pedidos</a>
                <a href="historial_rutas.php" class="nav-link text-white me-3">Historial Rutas</a>
                <span class="navbar-text me-3">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="api/logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Historial de Entregas</h1>
            <a href="index.php" class="btn btn-secondary">Volver a Pedidos Pendientes</a>
        </div>

        <div id="historial-lista" class="row">
            <!-- El historial de pedidos se cargará aquí dinámicamente con JavaScript -->
            <div class="text-center mt-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando historial...</span>
                </div>
                <p class="text-muted mt-2">Cargando historial...</p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/historial.js"></script>
</body>
</html>
