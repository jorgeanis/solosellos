<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel - SoloSellos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            display: flex;
            background-color: #f1f2f7;
        }
        .sidebar {
            width: 230px;
            background: #2c3e50;
            color: #ecf0f1;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
        }
        .sidebar h2 {
            text-align: center;
            padding: 20px 0;
            font-size: 20px;
            margin: 0;
            background: #1abc9c;
        }
        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            border-bottom: 1px solid #34495e;
        }
        .sidebar a i {
            margin-right: 8px;
        }
        .sidebar a:hover {
            background: #16a085;
        }

        .main {
            margin-left: 230px;
            padding: 20px;
            flex-grow: 1;
        }

        header {
            background: #1abc9c;
            color: white;
            padding: 15px 20px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h2 {
            margin-top: 0;
            font-size: 22px;
            color: #2c3e50;
            border-bottom: 2px solid #1abc9c;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        table th, table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        input, select, textarea, button {
            padding: 8px;
            font-family: inherit;
            margin: 5px 0;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            background: #1abc9c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background: #16a085;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>
<div class="sidebar">
    <h2><i class="fa-solid fa-stamp"></i> SoloSellos</h2>
    <a href="dashboard.php"><i class="fa-solid fa-gauge"></i> Inicio</a>
    <a href="modelos.php"><i class="fa-solid fa-layer-group"></i> Modelos</a>
    <a href="plantillas.php"><i class="fa-solid fa-palette"></i> Plantillas</a>
    <a href="pedidos.php"><i class="fa-solid fa-box"></i> Pedidos</a>
    <a href="personalizar.php"><i class="fa-solid fa-wand-magic-sparkles"></i> Personalizar</a>
    <?php if ($_SESSION['user']['email'] == 'admin@solosellos.com'): ?>
        <a href="clientes.php"><i class="fa-solid fa-users-gear"></i> Admins</a>
    <?php endif; ?>
    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Cerrar sesión</a>
</div>
<div class="main">
<header>
    Bienvenido, <?= $_SESSION['user']['name'] ?> 
</header>
