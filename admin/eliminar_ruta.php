<?php
// admin/eliminar_ruta.php
require_once 'includes/auth.php';
require_once 'includes/db.php'; // Usa la conexión unificada $pdo

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de ruta inválido.");
}

$ruta_id = (int)$_GET['id'];

try {
    // La eliminación en cascada debería encargarse de los pedidos si configuramos FK,
    // pero como 'pedidos' y 'rutas' están recién unificadas, asegurémonos manualmente o confiemos en el script SQL.
    // El script 'migracion_unificacion.sql' no definió explícitamente FK ON DELETE CASCADE entre pedidos y rutas (solo entre pedidos y usuarios).
    
    // Opción A: Eliminar pedidos asociados primero (si queremos borrar el historial de esos pedidos en la app de rutas)
    // O Opción B: Desvincularlos (set ruta_id = NULL).
    // Generalmente, si borras la hoja de ruta, borras el registro logístico.
    
    $pdo->beginTransaction();

    // 1. Eliminar pedidos asociados a esta ruta (limpieza)
    $stmt_pedidos = $pdo->prepare("DELETE FROM pedidos WHERE ruta_id = ?");
    $stmt_pedidos->execute([$ruta_id]);

    // 2. Eliminar la ruta
    $stmt_ruta = $pdo->prepare("DELETE FROM rutas WHERE id = ?");
    $stmt_ruta->execute([$ruta_id]);

    $pdo->commit();

    // Redirigir de vuelta a la pestaña de rutas
    header("Location: pedidos.php?tab=rutas&msg=ruta_eliminada");
    exit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error al eliminar la ruta: " . $e->getMessage());
}
?>
