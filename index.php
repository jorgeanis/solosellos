<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próximamente - SelloSmart</title>
    <style>
        :root {
          --primary: #25d366;
          --bg: #f9f9f9;
          --text: #333;
          --radius: 10px;
          --shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }
        .container {
            text-align: center;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 50px;
            max-width: 550px;
            margin: 20px;
            animation: fadeIn 0.6s ease-in-out;
            border-top: 5px solid var(--primary);
        }
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(-20px); }
          to { opacity: 1; transform: translateY(0); }
        }
        img {
            max-width: 180px;
            margin-bottom: 25px;
        }
        h1 {
            font-size: 2.2em;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        p {
            font-size: 1.1em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="assets/images/logo-login.png" alt="Logo SelloSmart">
        <h1>Sitio en Mantenimiento</h1>
        <p>Estamos realizando mejoras para brindarte una mejor experiencia. Volveremos a estar en línea muy pronto.</p>
    </div>
</body>
</html>