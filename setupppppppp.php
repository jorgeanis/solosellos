<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = $_POST["host"];
    $db = $_POST["db"];
    $user = $_POST["user"];
    $pass = $_POST["pass"];

    $conn = new mysqli($host, $user, $pass);

    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Crear base de datos si no existe
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $conn->select_db($db);

    // Ejecutar estructura SQL
    $sql = file_get_contents(__DIR__ . "/sql/estructura.sql");
    if (!$conn->multi_query($sql)) {
        die("Error ejecutando SQL: " . $conn->error);
    }

    // Crear archivo config.php
    $configContent = "<?php\n";
    $configContent .= "define('DB_HOST', '$host');\n";
    $configContent .= "define('DB_NAME', '$db');\n";
    $configContent .= "define('DB_USER', '$user');\n";
    $configContent .= "define('DB_PASS', '$pass');\n";
    file_put_contents(__DIR__ . "/config.php", $configContent);

    echo "<h2>✅ Instalación completada correctamente.</h2>";
    echo "<p><a href='admin/login.php'>Ir al panel de control</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Instalador - SoloSellos</title>
</head>
<body>
    <h2>Instalador de SoloSellos</h2>
    <form method="POST">
        <label>Servidor (ej: localhost):</label><br>
        <input type="text" name="host" value="localhost" required><br><br>

        <label>Nombre de la base de datos:</label><br>
        <input type="text" name="db" required><br><br>

        <label>Usuario MySQL:</label><br>
        <input type="text" name="user" required><br><br>

        <label>Contraseña MySQL:</label><br>
        <input type="password" name="pass"><br><br>

        <button type="submit">Instalar</button>
    </form>
</body>
</html>
