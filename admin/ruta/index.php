<?php
// Usar el sistema de autenticación de la aplicación principal "solosellos".
require_once '../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Repartos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <nav class="navbar navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="24" fill="currentColor" class="bi bi-signpost-split-fill" viewBox="0 0 16 16">
                    <path d="M7 16h2V6h5a1 1 0 0 0 .8-.4l.975-1.3a.5.5 0 0 0 0-.6L14.8 2.4A1 1 0 0 0 14 2H9v-.586a1 1 0 0 0-2 0V7H2a1 1 0 0 0-.8.4L.225 8.7a.5.5 0 0 0 0 .6l.975 1.3a1 1 0 0 0 .8.4H7v5Z"/>
                </svg>
                Gestor de Repartos
            </a>
            <div class="d-flex align-items-center">
                <a href="historial.php" class="nav-link text-white me-3">Historial Pedidos</a>
                <a href="historial_rutas.php" class="nav-link text-white me-3">Historial Rutas</a>
                <span class="navbar-text me-3">Hola, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <a href="../logout.php" class="btn btn-outline-light">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <!-- ########## Interfaz Principal de la Aplicación ########## -->
    <main class="container" id="app-container">
        <div class="row">
            <div class="col-md-5">
                <h3>Cargar Nuevo Pedido</h3>
                <form id="form-pedido">
                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección de Entrega</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" required>
                    </div>
                    <div class="mb-3">
                        <label for="whatsapp" class="form-label">WhatsApp del Cliente</label>
                        <div class="input-group">
                            <span class="input-group-text">+54</span>
                            <input type="tel" class="form-control" id="whatsapp" name="whatsapp" placeholder="91122334455" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="otros_datos" class="form-label">Otros Datos (Opcional)</label>
                        <textarea class="form-control" id="otros_datos" name="otros_datos" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Diseño del Sello</label>
                        <div id="paste-area" class="form-control text-center" style="min-height: 150px; border-style: dashed; padding: 1rem;">
                            <p class="text-muted my-4">Pega la imagen aquí (Ctrl+V)</p>
                        </div>
                        <input type="hidden" id="imagen_base64" name="imagen_base64">
                        <div id="preview-container" class="mt-2" style="display: none;">
                            <img id="preview-image" src="" alt="Vista previa del sello" class="img-fluid rounded">
                            <button type="button" id="remove-image" class="btn btn-sm btn-danger mt-1">Quitar Imagen</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Pedido</button>
                </form>
            </div>
            <div class="col-md-7">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Pedidos del Día</h3>
                    <a href="hoja_de_ruta.php" class="btn btn-primary">Generar Hoja de Ruta</a>
                </div>
                <div id="lista-pedidos">
                    <div class="text-center mt-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
                        <p class="text-muted mt-2">Cargando pedidos...</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-edit-pedido">
                        <input type="hidden" id="edit_id" name="edit_id">
                        <div class="mb-3">
                            <label for="edit_direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="edit_direccion" name="edit_direccion" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_whatsapp" class="form-label">WhatsApp</label>
                            <input type="tel" class="form-control" id="edit_whatsapp" name="edit_whatsapp" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_otros_datos" class="form-label">Otros Datos</label>
                            <textarea class="form-control" id="edit_otros_datos" name="edit_otros_datos" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cambiar Diseño del Sello (Opcional)</label>
                            <div id="edit_paste-area" class="form-control text-center" style="min-height: 120px; border-style: dashed; padding: 1rem;">
                                <p class="text-muted my-3">Pega la nueva imagen aquí (Ctrl+V)</p>
                            </div>
                            <input type="hidden" id="edit_imagen_base64" name="edit_imagen_base64">
                            <div id="edit_preview-container" class="mt-2 text-center">
                                <img id="edit_preview-image" src="" alt="Vista previa del sello" class="img-fluid rounded" style="max-height: 150px;">
                                <button type="button" id="edit_remove-image" class="btn btn-sm btn-danger mt-1" style="display: none;">Quitar/Cancelar Cambio</button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAXXtVhQP_A1Ix8lgPtkpwqR6XVzAQLQzs&libraries=places&callback=initAutocomplete" async defer></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
