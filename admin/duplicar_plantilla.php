<?php
require_once "includes/db.php";
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    
    $nuevo_id = $pdo->lastInsertId();
    header("Location: editar_plantilla.php?id=" . $nuevo_id);
    
    exit;
}

$id = (int)$_GET['id'];
$user_id = $_SESSION['user']['id'] ?? 0;

try {
    // Obtener la plantilla original
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $plantilla = $stmt->fetch();

    if (!$plantilla) {
        
    $nuevo_id = $pdo->lastInsertId();
    header("Location: editar_plantilla.php?id=" . $nuevo_id);
    
        exit;
    }

    // Insertar la copia
    $nuevoNombre = "Copia de " . $plantilla['nombre'];
    $stmt = $pdo->prepare("INSERT INTO templates (
        category_id, content, font_family, user_id, nombre,
        fuente_linea_1, fuente_linea_2, fuente_linea_3, fuente_linea_4,
        tamano_linea_1, tamano_linea_2, tamano_linea_3, tamano_linea_4,
        pos_x_linea_1, pos_y_linea_1, pos_x_linea_2, pos_y_linea_2,
        pos_x_linea_3, pos_y_linea_3, pos_x_linea_4, pos_y_linea_4,
        bold_linea_1, bold_linea_2, bold_linea_3, bold_linea_4,
        alineacion_linea_1, alineacion_linea_2, alineacion_linea_3, alineacion_linea_4,
        margen_top_linea_1, margen_top_linea_2, margen_top_linea_3, margen_top_linea_4,
        cursiva_linea_1, cursiva_linea_2, cursiva_linea_3, cursiva_linea_4
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?
    )");

    $stmt->execute([
        $plantilla['category_id'],
        $plantilla['content'],
        $plantilla['font_family'],
        $plantilla['user_id'],
        $nuevoNombre,
        $plantilla['fuente_linea_1'], $plantilla['fuente_linea_2'], $plantilla['fuente_linea_3'], $plantilla['fuente_linea_4'],
        $plantilla['tamano_linea_1'], $plantilla['tamano_linea_2'], $plantilla['tamano_linea_3'], $plantilla['tamano_linea_4'],
        $plantilla['pos_x_linea_1'], $plantilla['pos_y_linea_1'], $plantilla['pos_x_linea_2'], $plantilla['pos_y_linea_2'],
        $plantilla['pos_x_linea_3'], $plantilla['pos_y_linea_3'], $plantilla['pos_x_linea_4'], $plantilla['pos_y_linea_4'],
        $plantilla['bold_linea_1'], $plantilla['bold_linea_2'], $plantilla['bold_linea_3'], $plantilla['bold_linea_4'],
        $plantilla['alineacion_linea_1'], $plantilla['alineacion_linea_2'], $plantilla['alineacion_linea_3'], $plantilla['alineacion_linea_4'],
        $plantilla['margen_top_linea_1'], $plantilla['margen_top_linea_2'], $plantilla['margen_top_linea_3'], $plantilla['margen_top_linea_4'],
        $plantilla['cursiva_linea_1'], $plantilla['cursiva_linea_2'], $plantilla['cursiva_linea_3'], $plantilla['cursiva_linea_4']
    ]);

    
    $nuevo_id = $pdo->lastInsertId();
    header("Location: editar_plantilla.php?id=" . $nuevo_id);
    
    exit;
} catch (Exception $e) {
    echo "Error al duplicar plantilla: " . $e->getMessage();
    exit;
}
?>
