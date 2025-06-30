
<?php
if (isset($_POST['image']) && isset($_POST['plantilla_id'])) {
    $id = (int) $_POST['plantilla_id'];
    $data = $_POST['image'];
    $data = str_replace('data:image/png;base64,', '', $data);
    $data = str_replace(' ', '+', $data);
    $decoded = base64_decode($data);

    $dir = __DIR__ . '/previews';
    if (!file_exists($dir)) mkdir($dir, 0777, true);

    file_put_contents($dir . "/plantilla_" . $id . ".png", $decoded);
}
?>
