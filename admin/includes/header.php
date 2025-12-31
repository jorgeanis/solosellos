<?php
// admin/includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel - SoloSellos</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        dark: {
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Scrollbar suave */
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Sidebar active state */
        .nav-item.active {
            background-color: #2563eb;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }
        .nav-item.active i { color: white; }
    </style>
</head>
<body class="bg-gray-200 text-gray-800 font-sans antialiased md:h-screen md:flex md:overflow-hidden">

    <!-- Mobile Header -->
    <div class="md:hidden fixed w-full bg-white z-50 flex justify-between items-center p-4 shadow-sm h-16 top-0 left-0">
        <span class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-stamp text-brand-600"></i> SoloSellos
        </span>
        <button id="mobile-menu-btn" class="text-gray-600 focus:outline-none p-2 rounded hover:bg-gray-200">
            <i class="fa-solid fa-bars text-xl"></i>
        </button>
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden glass transition-opacity opacity-0"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-dark-900 text-gray-400 flex flex-col transition-transform duration-300 transform -translate-x-full md:relative md:translate-x-0 md:flex shadow-xl border-r border-dark-800 h-screen md:h-auto">
        
        <!-- Logo Desktop -->
        <div class="h-16 flex items-center px-6 border-b border-gray-800 bg-dark-900">
            <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-stamp text-brand-500"></i> SoloSellos
            </h2>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto custom-scroll py-6 px-3 space-y-1">
            
            <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-2">Principal</p>

            <a href="dashboard.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge w-5 text-center transition-colors"></i> 
                <span class="font-medium">Inicio</span>
            </a>

            <a href="pedidos.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'pedidos.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-box w-5 text-center transition-colors"></i>
                <span class="font-medium">Pedidos</span>
            </a>

            <a href="plantillas.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'plantillas.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-palette w-5 text-center transition-colors"></i>
                <span class="font-medium">Plantillas</span>
            </a>
            
            <a href="modelos.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'modelos.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-layer-group w-5 text-center transition-colors"></i>
                <span class="font-medium">Modelos</span>
            </a>

            <a href="personalizar.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'personalizar.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-wand-magic-sparkles w-5 text-center transition-colors"></i>
                <span class="font-medium">Personalizar</span>
            </a>

            <?php if (isset($_SESSION['user']['email']) && $_SESSION['user']['email'] == 'admin@solosellos.com'): ?>
                <p class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 mt-6">Administración</p>
                
                <a href="clientes.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'clientes.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users-gear w-5 text-center transition-colors"></i>
                    <span class="font-medium">Admins</span>
                </a>
                
                <a href="configuracion.php" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all hover:bg-gray-800 hover:text-white <?= $current_page == 'configuracion.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear w-5 text-center transition-colors"></i>
                    <span class="font-medium">Configuración</span>
                </a>
            <?php endif; ?>

        </nav>

        <!-- User Footer -->
        <div class="p-4 border-t border-gray-800 bg-dark-900">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold text-sm">
                    <?= strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($_SESSION['user']['name'] ?? 'Usuario') ?></p>
                    <p class="text-xs text-gray-500 truncate">Plan: <?= htmlspecialchars($_SESSION['user']['plan'] ?? 'Gratis') ?></p>
                </div>
            </div>
            <a href="logout.php" class="block w-full text-center py-2 px-4 border border-gray-700 rounded-lg text-xs font-medium text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                <i class="fa-solid fa-right-from-bracket mr-1"></i> Cerrar sesión
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col min-h-screen md:h-screen md:overflow-hidden pt-16 md:pt-0 relative w-full">
        
        <!-- Top Bar Desktop (Optional breadcrumb or actions) -->
        <header class="hidden md:flex h-16 bg-white border-b border-gray-200 items-center justify-between px-6 shrink-0 z-10">
            <h1 class="text-xl font-bold text-gray-800">
                <?php 
                    $page_titles = [
                        'dashboard.php' => 'Panel de Control',
                        'pedidos.php' => 'Gestión de Pedidos',
                        'plantillas.php' => 'Mis Plantillas',
                        'modelos.php' => 'Catálogo de Modelos',
                        'personalizar.php' => 'Personalizador',
                        'configuracion.php' => 'Configuración del Sistema',
                        'clientes.php' => 'Usuarios Administradores'
                    ];
                    echo $page_titles[$current_page] ?? 'Panel';
                ?>
            </h1>
            <div class="flex items-center gap-4">
               <!-- Actions here if needed -->
               <div class="text-sm text-gray-500">
                    <i class="fa-regular fa-calendar mr-1"></i> <?= date('d/m/Y') ?>
               </div>
            </div>
        </header>

        <!-- Content Body (Scrollable) -->
        <main class="flex-1 md:overflow-x-hidden md:overflow-y-auto bg-gray-200 p-4 md:p-6 custom-scroll">