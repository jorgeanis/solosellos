<?php
require_once "includes/db.php";
require_once "includes/auth.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nombre = $_POST['nombre'];
    $categoria = $_POST['categoria'];

    $stmt = $pdo->prepare("UPDATE templates SET 
        nombre = ?, 
        category_id = ?,
        fuente1 = ?, tamano1 = ?, x1 = ?, y1 = ?, bold1 = ?,
        fuente2 = ?, tamano2 = ?, x2 = ?, y2 = ?, bold2 = ?,
        fuente3 = ?, tamano3 = ?, x3 = ?, y3 = ?, bold3 = ?,
        fuente4 = ?, tamano4 = ?, x4 = ?, y4 = ?, bold4 = ?
        WHERE id = ? AND user_id = ?");
    
    $stmt->execute([
        $nombre,
        $categoria,
        $_POST['fuente1'], $_POST['tamano1'], $_POST['x1'], $_POST['y1'], isset($_POST['bold1']) ? 1 : 0,
        $_POST['fuente2'], $_POST['tamano2'], $_POST['x2'], $_POST['y2'], isset($_POST['bold2']) ? 1 : 0,
        $_POST['fuente3'], $_POST['tamano3'], $_POST['x3'], $_POST['y3'], isset($_POST['bold3']) ? 1 : 0,
        $_POST['fuente4'], $_POST['tamano4'], $_POST['x4'], $_POST['y4'], isset($_POST['bold4']) ? 1 : 0,
        $id,
        $_SESSION['user']['id']
    ]);

    header("Location: plantillas.php");
    exit;
}
?>
