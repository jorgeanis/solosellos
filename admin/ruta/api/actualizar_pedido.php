<?php
// Archivo: api/actualizar_pedido.php

require_once '../../includes/auth.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = 1;
    $id = $_POST['edit_id'] ?? null;
    $direccion = trim($_POST['edit_direccion'] ?? '');
    $whatsapp = trim($_POST['edit_whatsapp'] ?? '');
    $otros_datos = trim($_POST['edit_otros_datos'] ?? '');
    $imagen_base64 = $_POST['edit_imagen_base64'] ?? '';

    if (empty($id) || empty($direccion) || empty($whatsapp)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
        exit;
    }

    $imagen_a_actualizar = null;

    if (!empty($imagen_base64)) {
        list($type, $data) = explode(';', $imagen_base64);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        $nuevo_nombre_archivo = uniqid('sello_', true) . '.png';
        $nueva_ruta = '../uploads/' . $nuevo_nombre_archivo;

        if (file_put_contents($nueva_ruta, $data)) {
            $imagen_a_actualizar = $nuevo_nombre_archivo;

            // Obtener el nombre de la imagen antigua para borrarla (asegurándonos que el pedido pertenece al usuario)
            $stmt_old = $pdo->prepare("SELECT imagen_sello FROM pedidos WHERE id = :id AND user_id = :user_id");
            $stmt_old->execute(['id' => $id, 'user_id' => $user_id]);
            $imagen_antigua = $stmt_old->fetchColumn();

            if ($imagen_antigua && file_exists('../uploads/' . $imagen_antigua)) {
                unlink('../uploads/' . $imagen_antigua);
            }
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Error al guardar la nueva imagen.']);
            exit;
        }
    }

    try {
        if ($imagen_a_actualizar) {
            $sql = "UPDATE pedidos SET direccion = :direccion, whatsapp = :whatsapp, otros_datos = :otros_datos, imagen_sello = :imagen_sello WHERE id = :id AND user_id = :user_id";
            $params = [
                ':direccion' => $direccion,
                ':whatsapp' => $whatsapp,
                ':otros_datos' => $otros_datos,
                ':imagen_sello' => $imagen_a_actualizar,
                ':id' => $id,
                ':user_id' => $user_id
            ];
        } else {
            $sql = "UPDATE pedidos SET direccion = :direccion, whatsapp = :whatsapp, otros_datos = :otros_datos WHERE id = :id AND user_id = :user_id";
            $params = [
                ':direccion' => $direccion,
                ':whatsapp' => $whatsapp,
                ':otros_datos' => $otros_datos,
                ':id' => $id,
                ':user_id' => $user_id
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Pedido actualizado correctamente.']);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado o no te pertenece.']);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la base de datos.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>