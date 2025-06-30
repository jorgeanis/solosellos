<?php
session_start();
include("db.php");

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Obtener plantillas con su categoría
$query = "SELECT templates.*, template_categories.name AS category_name 
          FROM templates 
          LEFT JOIN template_categories ON templates.category_id = template_categories.id";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Plantillas</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            background: #f2f2f2;
        }
        header {
            background: #333;
            color: white;
            padding: 10px 20px;
            font-size: 20px;
        }
        .container {
            padding: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .card img {
            width: 100%;
            height: 180px;
            object-fit: contain;
            background: #eee;
        }
        .card-body {
            padding: 15px;
            flex-grow: 1;
        }
        .card-body h3 {
            margin: 0 0 10px;
            font-size: 18px;
        }
        .category {
            font-size: 14px;
            color: #777;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            padding: 10px 15px 15px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-edit {
            background: #3498db;
            color: white;
        }
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        .top-button {
            margin-bottom: 20px;
        }
        .top-button a {
            background: #2ecc71;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <header>Panel de Plantillas</header>
    <div class="container">
        <div class="top-button">
            <a href="nueva_plantilla.php">+ Nueva Plantilla</a>
        </div>
        <div class="grid">
        <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="card">
                <img src="previews/<?php echo $row['preview']; ?>" alt="Vista previa">
                <div class="card-body">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <div class="category"><?php echo htmlspecialchars($row['category_name']); ?></div>
                </div>
                <div class="card-footer">
                    <a class="btn btn-edit" href="editar_plantilla.php?id=<?php echo $row['id']; ?>">Editar</a>
                    <a class="btn btn-delete" href="eliminar_plantilla.php?id=<?php echo $row['id']; ?>" onclick="return confirm('¿Estás seguro de eliminar esta plantilla?');">Eliminar</a>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
