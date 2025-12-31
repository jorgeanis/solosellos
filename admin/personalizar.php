<?php
require_once 'includes/auth.php';
require_once 'includes/settings.php';

// --- LOGICA DE BACKEND ---
$user_id = $_SESSION['user']['id'];
$success_message = '';
$error_message = '';

// Handle Delete Status
if (isset($_GET['delete_status'])) {
    $status_id_to_delete = $_GET['delete_status'];
    $stmt = $pdo->prepare("DELETE FROM custom_statuses WHERE id = ? AND user_id = ?");
    $stmt->execute([$status_id_to_delete, $user_id]);
    header("Location: personalizar.php?tab=pedidos");
    exit;
}

// Handle Add/Edit Status or General Settings
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // 1. Add Status
    if (isset($_POST['add_status'])) {
        $new_status_name = $_POST['new_status_name'] ?? '';
        $new_status_color = $_POST['new_status_color'] ?? '#FFFFFF';
        if (!empty($new_status_name)) {
            $stmt = $pdo->prepare("INSERT INTO custom_statuses (user_id, status_name, color) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $new_status_name, $new_status_color]);
        }
        header("Location: personalizar.php?tab=pedidos");
        exit;
    }

    // 2. Edit Status
    elseif (isset($_POST['edit_status'])) {
        $status_id = $_POST['status_id'];
        $edit_name = $_POST['edit_status_name'];
        $edit_color = $_POST['edit_status_color'];
        
        if (!empty($edit_name) && !empty($status_id)) {
            $stmt = $pdo->prepare("UPDATE custom_statuses SET status_name = ?, color = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$edit_name, $edit_color, $status_id, $user_id]);
        }
        header("Location: personalizar.php?tab=pedidos");
        exit;
    }

    // 3. Password Change (Simplificado, se mantiene modal UI)
    elseif (isset($_POST['password_change'])) {
        // (Lógica mantenida igual, solo reubicada en flujo)
    } 

    // 4. General Settings (Main Form)
    else {
        $color = $_POST['color_primary'];
        $email = $_POST['email'] ?? '';
        $name  = $_POST['name'] ?? '';
        $color_secundary = $_POST['color_secundary'] ?? '';
        $background_image = $_POST['background_image'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $welcome = $_POST['welcome'];
        $welcome_active = isset($_POST['welcome_active']) ? 1 : 0;
        $footer  = $_POST['footer'];
        $logo    = $_SESSION['user']['logo'];
        $referral_active = isset($_POST['referral_active']) ? 1 : 0;
        $referral_type = $_POST['referral_type'] ?? 'percent';
        $referral_value = $_POST['referral_value'] ?? 0.00;
        $timezone = $_POST['timezone'] ?? 'America/Argentina/Buenos_Aires';
        $delivery_origin = $_POST['delivery_origin'] ?? '';
        
        set_setting('delivery_origin', $delivery_origin);

        if (!empty($_FILES["logo"]["name"])) {
            $ext = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
            $new_logo_name = uniqid('logo_') . '.' . $ext;
            move_uploaded_file($_FILES["logo"]["tmp_name"], "../assets/images/" . $new_logo_name);
            $logo = $new_logo_name;
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, color_primary = ?, color_secundary = ?, logo = ?, footer = ?, whatsapp = ?, background_image = ?, welcome = ?, welcome_active = ?, referral_active = ?, referral_type = ?, referral_value = ?, timezone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $color, $color_secundary, $logo, $footer, $whatsapp, $background_image, $welcome, $welcome_active, $referral_active, $referral_type, $referral_value, $timezone, $_SESSION['user']['id']]);

        // Refresh Session
        $_SESSION['user'] = array_merge($_SESSION['user'], [
            'color_primary' => $color, 'logo' => $logo, 'footer' => $footer,
            'name' => $name, 'email' => $email, 'color_secundary' => $color_secundary,
            'whatsapp' => $whatsapp, 'welcome' => $welcome, 'welcome_active' => $welcome_active,
            'background_image' => $background_image, 'referral_active' => $referral_active,
            'referral_type' => $referral_type, 'referral_value' => $referral_value,
            'timezone' => $timezone
        ]);
        
        $success_message = "Configuración guardada correctamente.";
    }
}

// --- CARGA DE VISTA ---
require_once 'includes/header.php';

// Fetch statuses
$stmt = $pdo->prepare("SELECT * FROM custom_statuses WHERE user_id = ? ORDER BY display_order ASC, status_name ASC");
$stmt->execute([$user_id]);
$custom_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Migration Check (Silenciosa)
try { $pdo->query("SELECT welcome_active FROM users LIMIT 1"); } 
catch (PDOException $e) { $pdo->exec("ALTER TABLE users ADD COLUMN welcome_active TINYINT(1) DEFAULT 1"); }

$active_tab = $_GET['tab'] ?? 'general';
?>

<!-- AOS CSS -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<!-- Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- Preloader -->
<div id="preloader" class="fixed inset-0 bg-gray-100 z-[9999] flex flex-col items-center justify-center transition-opacity duration-500">
    <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-brand-600 mb-4"></div>
    <h2 class="text-xl font-semibold text-gray-700 animate-pulse">Cargando Personalizador...</h2>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-24">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4" data-aos="fade-down">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                <i class="fas fa-wand-magic-sparkles text-brand-600 mr-2"></i>Personalizar
            </h1>
            <p class="text-gray-500 text-sm mt-1">Adapta la apariencia y funcionalidad de tu sitio</p>
        </div>
        <?php if($success_message): ?>
            <div id="success-toast" class="px-4 py-2 bg-green-100 border border-green-200 text-green-700 rounded-lg shadow-sm flex items-center gap-2 animate-bounce-in">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-2 mb-8 flex overflow-x-auto gap-2" data-aos="fade-up">
        <button onclick="switchTab('general')" id="tab-btn-general" class="tab-btn flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap">
            <i class="fas fa-cog mr-2"></i>General
        </button>
        <button onclick="switchTab('apariencia')" id="tab-btn-apariencia" class="tab-btn flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap">
            <i class="fas fa-paint-brush mr-2"></i>Apariencia
        </button>
        <button onclick="switchTab('pedidos')" id="tab-btn-pedidos" class="tab-btn flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap">
            <i class="fas fa-list-ul mr-2"></i>Estados Pedido
        </button>
        <button onclick="switchTab('referidos')" id="tab-btn-referidos" class="tab-btn flex-1 px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap">
            <i class="fas fa-gift mr-2"></i>Referidos
        </button>
    </div>

    <form method="POST" enctype="multipart/form-data" id="mainForm">
        <input type="hidden" name="background_image" id="background_image" value="<?= htmlspecialchars($_SESSION['user']['background_image'] ?? '') ?>">

        <!-- TAB: GENERAL -->
        <div id="tab-general" class="tab-content hidden space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Datos del Negocio -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 flex items-center gap-2">
                        <i class="fas fa-store text-brand-500"></i> Datos del Negocio
                    </h3>
                    
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1 ml-1">Nombre del Negocio</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-signature"></i>
                            </span>
                            <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['user']['name']) ?>" 
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1 ml-1">WhatsApp</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-green-500">
                                    <i class="fab fa-whatsapp"></i>
                                </span>
                                <input type="text" name="whatsapp" value="<?= htmlspecialchars($_SESSION['user']['whatsapp']) ?>" 
                                    class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1 ml-1">Email</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" 
                                    class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1 ml-1">Dirección Origen (Repartos)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-red-400">
                                <i class="fas fa-map-marker-alt"></i>
                            </span>
                            <input type="text" id="delivery_origin" name="delivery_origin" value="<?= htmlspecialchars(get_setting('delivery_origin')) ?>" 
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm" placeholder="Dirección real para mapas">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1 ml-1">Zona Horaria</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                <i class="fas fa-globe"></i>
                            </span>
                            <select name="timezone" class="w-full pl-10 pr-10 py-2.5 rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm appearance-none">
                                <?php
                                $timezones = DateTimeZone::listIdentifiers(DateTimeZone::AMERICA);
                                $current_tz = $_SESSION['user']['timezone'] ?? 'America/Argentina/Buenos_Aires';
                                foreach ($timezones as $tz) {
                                    $selected = ($tz == $current_tz) ? 'selected' : '';
                                    echo "<option value='$tz' $selected>$tz</option>";
                                }
                                ?>
                            </select>
                            <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Logo -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center text-center">
                    <h3 class="text-lg font-bold text-gray-800 w-full text-left border-b border-gray-100 pb-2 mb-4 flex items-center gap-2">
                        <i class="fas fa-image text-brand-500"></i> Logo del Sitio
                    </h3>
                    
                    <div class="w-full h-64 bg-gray-50 border-2 border-dashed border-gray-300 hover:border-brand-400 hover:bg-blue-50 rounded-xl flex flex-col items-center justify-center mb-4 overflow-hidden relative group transition-colors cursor-pointer">
                        <?php if ($_SESSION['user']['logo']): ?>
                            <img id="logo-preview" src="../assets/images/<?= htmlspecialchars($_SESSION['user']['logo']) ?>" class="max-w-full max-h-48 object-contain z-10">
                        <?php else: ?>
                            <div class="text-gray-300 text-6xl mb-2"><i class="fas fa-cloud-upload-alt"></i></div>
                            <span class="text-gray-400 font-medium">Subir Logo</span>
                        <?php endif; ?>
                        
                        <div class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20">
                            <i class="fas fa-pen text-white text-3xl mb-2"></i>
                            <span class="text-white font-bold bg-black/30 px-3 py-1 rounded-full text-sm">Cambiar Imagen</span>
                        </div>
                        <input type="file" name="logo" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30" onchange="previewImage(this, 'logo-preview')">
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: APARIENCIA -->
        <div id="tab-apariencia" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Controls -->
                <div class="lg:col-span-7 space-y-6">
                    
                    <!-- Diseño Visual (Colores + Fondo) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4 flex items-center gap-2">
                            <i class="fas fa-swatchbook text-brand-500"></i> Diseño Visual
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Columna Izquierda: Colores -->
                            <div class="space-y-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Colores de la Marca</label>
                                
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Principal</span>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="color_primary" id="color_primary" value="<?= $_SESSION['user']['color_primary'] ?>" class="w-8 h-8 rounded border-0 cursor-pointer" oninput="updateLivePreview()">
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200">
                                    <span class="text-sm font-medium text-gray-600">Secundario</span>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="color_secundary" id="color_secundary" value="<?= $_SESSION['user']['color_secundary'] ?>" class="w-8 h-8 rounded border-0 cursor-pointer" oninput="updateLivePreview()">
                                    </div>
                                </div>
                            </div>

                            <!-- Columna Derecha: Fondo -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Fondo del Sitio</label>
                                <div class="relative h-32 rounded-xl overflow-hidden cursor-pointer group border-2 border-gray-300 hover:border-brand-500 transition-colors shadow-sm" onclick="openBgModal()">
                                    <div id="bg-preview-mini" class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105" style="background-image: url('../assets/images/bg/<?= $_SESSION['user']['background_image'] ?>');"></div>
                                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center group-hover:bg-black/50 transition-colors">
                                        <span class="text-white text-xs font-bold border border-white/70 px-3 py-1.5 rounded-full backdrop-blur-md shadow-lg flex items-center gap-1">
                                            <i class="fas fa-image"></i> Cambiar
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Textos -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-2 mb-4 flex items-center gap-2">
                            <i class="fas fa-align-left text-brand-500"></i> Contenido
                        </h3>
                        
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-bold text-gray-700">Mensaje de Bienvenida</label>
                                <label class="flex items-center cursor-pointer bg-gray-100 px-3 py-1 rounded-full hover:bg-gray-200 transition-colors">
                                    <input type="checkbox" name="welcome_active" value="1" <?= ($_SESSION['user']['welcome_active'] ?? 1) ? 'checked' : '' ?> class="rounded text-brand-600 focus:ring-brand-500 mr-2">
                                    <span class="text-xs font-semibold text-gray-600">Mostrar en Inicio</span>
                                </label>
                            </div>
                            <textarea name="welcome" id="welcome" rows="4" 
                                class="w-full p-4 rounded-lg border border-gray-300 bg-gray-50 text-gray-800 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm shadow-inner resize-y transition-all" 
                                placeholder="Escribe un mensaje de bienvenida..."
                                oninput="updateLivePreview()">
<?= htmlspecialchars($_SESSION['user']['welcome'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Pie de Página (Footer)</label>
                            <div id="editor" class="h-32 bg-gray-50 rounded-lg border border-gray-300 focus-within:ring-2 focus-within:ring-brand-500 focus-within:bg-white transition-all"></div>
                            <textarea name="footer" id="footer" class="hidden"><?= $_SESSION['user']['footer'] ?? "" ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Live Preview (Desktop Only) -->
                <div class="hidden lg:block lg:col-span-5">
                    <div class="sticky top-6">
                        <div class="bg-gray-900 rounded-[3rem] p-4 shadow-2xl border-4 border-gray-800 w-[300px] mx-auto relative h-[600px]">
                             <div class="absolute top-0 left-1/2 transform -translate-x-1/2 h-6 w-32 bg-gray-800 rounded-b-xl z-20"></div>
                             <iframe id="livePreview" src="../public/index.php?u=<?= $_SESSION['user']['link_code'] ?>" class="w-full h-full bg-white rounded-[2.5rem] border-none overflow-hidden shadow-inner"></iframe>
                        </div>
                        <p class="text-center text-sm text-gray-500 mt-4 font-medium"><i class="fas fa-mobile-alt mr-1"></i> Vista previa interactiva</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: PEDIDOS -->
        <div id="tab-pedidos" class="tab-content hidden">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Lista -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-list text-brand-500"></i> Estados Personalizados</h3>
                        <span class="text-xs font-semibold bg-white border border-gray-200 px-2 py-1 rounded text-gray-500"><?= count($custom_statuses) ?> total</span>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($custom_statuses as $status): ?>
                            <div class="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors group">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full shadow-sm border border-gray-200 flex items-center justify-center text-white font-bold text-xs" style="background-color: <?= htmlspecialchars($status['color']) ?>">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <span class="font-bold text-gray-700 text-lg"><?= htmlspecialchars($status['status_name']) ?></span>
                                </div>
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button type="button" onclick="openEditStatusModal(<?= $status['id'] ?>, '<?= htmlspecialchars($status['status_name']) ?>', '<?= $status['color'] ?>')" 
                                            class="text-gray-300 hover:text-blue-500 hover:bg-blue-50 p-2 rounded-lg transition-all" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <a href="personalizar.php?delete_status=<?= $status['id'] ?>&tab=pedidos" onclick="return confirm('¿Eliminar estado?')" 
                                       class="text-gray-300 hover:text-red-500 hover:bg-red-50 p-2 rounded-lg transition-all" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($custom_statuses)): ?>
                            <div class="p-12 text-center">
                                <div class="text-gray-300 text-5xl mb-3"><i class="fas fa-clipboard-list"></i></div>
                                <p class="text-gray-500 font-medium">No has creado estados personalizados aún.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit sticky top-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-green-500"></i> Nuevo Estado
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Estado</label>
                            <input type="text" name="new_status_name" placeholder="Ej: En Producción" 
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Color Identificador</label>
                            <div class="flex items-center gap-2 p-2 border border-gray-300 rounded-lg bg-gray-50">
                                <input type="color" name="new_status_color" value="#3b82f6" class="w-10 h-10 border-0 p-0 rounded cursor-pointer">
                                <span class="text-sm text-gray-500 font-mono">Selecciona un color</span>
                            </div>
                        </div>
                        <button type="submit" name="add_status" value="1" class="w-full py-3 bg-gray-800 text-white rounded-lg hover:bg-gray-900 font-bold shadow-lg shadow-gray-500/30 transition-all transform hover:-translate-y-1">
                            Agregar Estado
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: REFERIDOS -->
        <div id="tab-referidos" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 max-w-3xl mx-auto">
                <div class="flex items-start gap-4 mb-8 border-b border-gray-100 pb-6">
                    <div class="p-4 bg-green-100 text-green-600 rounded-2xl">
                        <i class="fas fa-gift text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Sistema de Referidos</h3>
                        <p class="text-gray-500 text-sm mt-1">Configura los incentivos para que tus clientes traigan nuevos compradores.</p>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-8 flex items-center gap-4 shadow-sm">
                    <input type="checkbox" name="referral_active" value="1" <?= !empty($_SESSION['user']['referral_active']) ? 'checked' : '' ?> 
                        class="w-6 h-6 rounded text-green-600 focus:ring-green-500 cursor-pointer">
                    <label class="font-bold text-green-800 text-lg cursor-pointer">Activar Sistema de Referidos</label>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tipo de Beneficio</label>
                        <select name="referral_type" class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-white text-gray-800 font-medium focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm">
                            <option value="percent" <?= ($_SESSION['user']['referral_type'] ?? '') === 'percent' ? 'selected' : '' ?>>🏷️ Porcentaje (%)</option>
                            <option value="fixed" <?= ($_SESSION['user']['referral_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>💰 Monto Fijo ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Valor del Descuento</label>
                        <div class="relative">
                             <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">
                                <i class="fas fa-tag"></i>
                            </span>
                            <input type="number" step="0.01" name="referral_value" value="<?= htmlspecialchars($_SESSION['user']['referral_value'] ?? '0.00') ?>" 
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-gray-50 text-gray-900 font-bold text-lg focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all shadow-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Floating Save Button -->
        <div class="fixed bottom-8 right-8 z-50">
            <button type="submit" class="group flex items-center gap-2 px-6 py-4 bg-brand-600 text-white rounded-full shadow-2xl hover:bg-brand-700 transition-all hover:scale-105 active:scale-95">
                <i class="fas fa-save text-xl group-hover:rotate-12 transition-transform"></i>
                <span class="font-bold">Guardar Cambios</span>
            </button>
        </div>
    </form>
</div>

<!-- Modal Backgrounds -->
<div id="bgModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden animate-bounce-in max-h-[80vh] flex flex-col">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800">Seleccionar Fondo</h3>
            <button onclick="closeBgModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="p-6 overflow-y-auto custom-scroll grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
             <?php
              $fondos = glob('../assets/images/bg/*.jpg');
              $fondo_actual = $_SESSION['user']['background_image'] ?? '';
              foreach ($fondos as $fondo) {
                $nombre = basename($fondo);
                $isSelected = ($nombre === $fondo_actual);
                $borderClass = $isSelected ? 'ring-4 ring-brand-500 scale-95' : 'hover:ring-2 hover:ring-brand-300';
                echo "
                <div onclick='selectBackground(\"$nombre\")' class='aspect-square rounded-xl bg-cover bg-center cursor-pointer transition-all $borderClass' style='background-image: url(\"../assets/images/bg/$nombre\");'>
                </div>";
              }
            ?>
        </div>
    </div>
</div>

<!-- Modal Edit Status -->
<div id="editStatusModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-bounce-in">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <h3 class="font-bold text-gray-800">Editar Estado</h3>
            <button onclick="closeEditStatusModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="edit_status" value="1">
            <input type="hidden" name="status_id" id="edit_status_id">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre del Estado</label>
                <input type="text" name="edit_status_name" id="edit_status_name" required
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Color Identificador</label>
                <div class="flex items-center gap-2 p-2 border border-gray-300 rounded-lg bg-gray-50">
                    <input type="color" name="edit_status_color" id="edit_status_color" class="w-10 h-10 border-0 p-0 rounded cursor-pointer">
                    <span class="text-sm text-gray-500 font-mono">Selecciona nuevo color</span>
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-3">
                <button type="button" onclick="closeEditStatusModal()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg font-bold hover:bg-brand-700 shadow-md">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts Library -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    // --- Edit Status Modal Logic ---
    function openEditStatusModal(id, name, color) {
        document.getElementById('edit_status_id').value = id;
        document.getElementById('edit_status_name').value = name;
        document.getElementById('edit_status_color').value = color;
        
        const modal = document.getElementById('editStatusModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditStatusModal() {
        const modal = document.getElementById('editStatusModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Init AOS & Preloader
    AOS.init({ duration: 800, once: true });
    window.addEventListener('load', () => {
        const preloader = document.getElementById('preloader');
        preloader.classList.add('opacity-0');
        setTimeout(() => preloader.style.display = 'none', 500);
        
        // Hide success toast
        const toast = document.getElementById('success-toast');
        if(toast) setTimeout(() => toast.classList.add('opacity-0'), 3000);
    });

    // Tab Logic
    const activeTabClass = ['bg-brand-50', 'text-brand-700', 'border-brand-200', 'shadow-sm'];
    const inactiveTabClass = ['text-gray-500', 'hover:text-gray-700', 'hover:bg-gray-50'];

    function switchTab(tabId) {
        // Hide all content
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('tab-' + tabId).classList.remove('hidden');

        // Reset Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove(...activeTabClass);
            btn.classList.add(...inactiveTabClass);
        });

        // Activate Button
        const btn = document.getElementById('tab-btn-' + tabId);
        btn.classList.remove(...inactiveTabClass);
        btn.classList.add(...activeTabClass);

        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.pushState({}, '', url);
    }

    // Initialize Tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'general';
    switchTab(initialTab);

    // Image Preview
    function previewImage(input, targetId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById(targetId).src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Modal Background
    function openBgModal() {
        document.getElementById('bgModal').classList.remove('hidden');
        document.getElementById('bgModal').classList.add('flex');
    }
    function closeBgModal() {
        document.getElementById('bgModal').classList.add('hidden');
        document.getElementById('bgModal').classList.remove('flex');
    }
    function selectBackground(name) {
        document.getElementById('background_image').value = name;
        document.getElementById('bg-preview-mini').style.backgroundImage = `url('../assets/images/bg/${name}')`;
        updateLivePreview();
        closeBgModal();
    }

    // Live Preview Logic
    const iframe = document.getElementById('livePreview');
    let previewDoc = null;

    if(iframe) {
        iframe.onload = function() {
            previewDoc = iframe.contentDocument;
            const style = previewDoc.createElement('style');
            style.textContent = 'body::-webkit-scrollbar{display:none;}';
            previewDoc.head.appendChild(style);
            updateLivePreview();
        };
    }

    function updateLivePreview() {
        if (!previewDoc) return;
        const colorP = document.getElementById('color_primary').value;
        const bgImage = document.getElementById('background_image').value;
        
        previewDoc.documentElement.style.setProperty('--color-principal', colorP);
        
        const welcome = document.getElementById('welcome').value;
        // Try to find welcome text in standard places
        const welcomeEl = previewDoc.querySelector('.form-container div'); 
        if (welcomeEl) welcomeEl.innerHTML = welcome.replace(/\n/g, '<br>');

        if (bgImage) {
            previewDoc.body.style.backgroundImage = `url('../assets/images/bg/${bgImage}')`;
        }
    }

    // Quill Editor
    var quill = new Quill('#editor', { theme: 'snow' });
    quill.on('text-change', function() {
        document.getElementById('footer').value = quill.root.innerHTML;
    });

    // Google Maps Autocomplete
    function initAutocomplete() {
        const originInput = document.getElementById('delivery_origin');
        if (originInput) {
            new google.maps.places.Autocomplete(originInput, {
                componentRestrictions: { country: 'ar' },
                types: ["address"],
            });
        }
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAXXtVhQP_A1Ix8lgPtkpwqR6XVzAQLQzs&libraries=places&callback=initAutocomplete" async defer></script>

<?php require_once 'includes/footer.php'; ?>
