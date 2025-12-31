<?php
require_once '../admin/includes/db.php';
session_start();

if (!isset($_GET['u'])) {
    die('Enlace inválido');
}

$link_code = $_GET['u'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE link_code = ? AND active = 1");
$stmt->execute([$link_code]);
$user = $stmt->fetch();

if (!$user) {
    die("El enlace no es válido o el usuario fue desactivado.");
}

// --- LOGICA DE REFERIDOS ---
$referral_code = $_GET['ref'] ?? null;
$discount_data = null;

if ($referral_code && $user['referral_active']) {
    $stmt_ref = $pdo->prepare("SELECT id FROM orders WHERE order_code = ? AND user_id = ?");
    $stmt_ref->execute([$referral_code, $user['id']]);
    if ($stmt_ref->fetch()) {
        $_SESSION['referral_code'] = $referral_code;
        $_SESSION['referral_user_id'] = $user['id'];
    }
}

if (isset($_SESSION['referral_code']) && isset($_SESSION['referral_user_id']) && $_SESSION['referral_user_id'] == $user['id'] && $user['referral_active']) {
    $discount_data = [
        'type' => $user['referral_type'],
        'value' => $user['referral_value']
    ];
}

$user_id = $user['id'];
$step = $_GET['step'] ?? null;

if (!$step && isset($user['welcome_active']) && $user['welcome_active'] == 0) {
    $step = 1;
}

// --- LOGICA DE EDICIÓN DE PEDIDO ---
$editing_order_id = $_GET['order_id'] ?? null;
$edit_data = null;
$edit_styles = null;

if ($editing_order_id) {
    $stmt_edit = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt_edit->execute([$editing_order_id, $user_id]);
    $edit_data = $stmt_edit->fetch(PDO::FETCH_ASSOC);

    if (!$edit_data) die("Pedido no encontrado.");
    if (strtolower($edit_data['status']) !== 'pendiente') die("Solo se pueden editar pedidos 'Pendientes'.");

    $edit_styles = json_decode($edit_data['styles'], true);
    
    if (!$step) {
        $step = 2;
        $_GET['model_id'] = $edit_data['model_id'];
        $_GET['model_price'] = $edit_data['price'];
        $contenido = [
            'linea1' => $edit_data['text_line1'],
            'linea2' => $edit_data['text_line2'],
            'linea3' => $edit_data['text_line3'],
            'linea4' => $edit_data['text_line4']
        ];
    }
}

$stmt = $pdo->prepare("SELECT * FROM models WHERE user_id = ?");
$stmt->execute([$user_id]);
$models = $stmt->fetchAll();

require_once '../admin/includes/fonts.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido de Sello - <?= htmlspecialchars($user['name']) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '<?= htmlspecialchars($user['color_primary']) ?>',
                            600: '<?= htmlspecialchars($user['color_primary']) ?>', 
                            900: '<?= htmlspecialchars($user['color_secundary'] ?? $user['color_primary']) ?>',
                        }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>

    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Google Maps API (Una sola vez, al final mejor, pero lo dejamos aquí sin callback por ahora) -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAXXtVhQP_A1Ix8lgPtkpwqR6XVzAQLQzs&libraries=places"></script>
    
    <!-- Legacy CSS for Previews -->
    <link rel="stylesheet" href="../assets/css/plantilla-preview.css">
    
    <?php foreach ($todas_las_fuentes as $fuente): ?>
        <link href='https://fonts.googleapis.com/css2?family=<?= str_replace(' ', '+', $fuente) ?>&display=swap' rel='stylesheet'>
    <?php endforeach; ?>

    <style>
        body {
            background-color: #f3f4f6;
            <?php if($user['background_image']): ?>
            background-image: url('../assets/images/bg/<?= $user['background_image'] ?>');
            background-size: cover; background-position: center; background-attachment: fixed;
            <?php endif; ?>
        }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.5); }
        .plantilla-preview-container { box-sizing: content-box !important; }
        .preview-scale-wrapper { transform-origin: center center; }
        
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .animate-slide-up { animation: slideUp 0.3s ease-out forwards; }
    </style>
</head>
<body class="font-sans antialiased min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-3xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <?php if($user['logo']): ?>
                    <img src="../assets/images/<?= htmlspecialchars($user['logo']) ?>" class="h-10 w-auto object-contain">
                <?php endif; ?>
                <span class="text-lg font-bold text-gray-800 border-l border-gray-200 pl-3 leading-none"><?= htmlspecialchars($user['name']) ?></span>
            </div>
            <a href="https://wa.me/+549<?= preg_replace('/[^0-9]/i', '', $user['whatsapp']) ?>" target="_blank" class="text-green-600 transition-colors">
                <i class="fab fa-whatsapp text-2xl"></i>
            </a>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-lg mx-auto">
            
            <?php if (!$step): ?>
                <!-- PASO 0: BIENVENIDA -->
                <div class="glass-card rounded-3xl shadow-xl p-8 text-center" data-aos="zoom-in">
                    <div class="w-20 h-20 bg-brand-50 rounded-full flex items-center justify-center mx-auto mb-6"><i class="fas fa-stamp text-brand-600 text-3xl"></i></div>
                    <h1 class="text-2xl font-bold text-gray-800 mb-4">¡Hola! 👋</h1>
                    <div class="text-gray-600 mb-8 leading-relaxed"><?= !empty($user['welcome']) ? nl2br(htmlspecialchars($user['welcome'])) : 'Bienvenido al asistente de pedidos.' ?></div>
                    <form action="index.php" method="get">
                        <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
                        <input type="hidden" name="step" value="1">
                        <button type="submit" class="w-full py-4 bg-brand-600 text-white rounded-xl font-bold text-lg shadow-lg hover:bg-brand-700 transition-all transform hover:-translate-y-1">Comenzar Pedido <i class="fas fa-arrow-right ml-2"></i></button>
                    </form>
                </div>

            <?php elseif ($step == 1): ?>
                <!-- PASO 1: ELEGIR MODELO -->
                <div data-aos="fade-up">
                    <div class="text-center mb-6">
                        <span class="bg-brand-100 text-brand-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Paso 1 de 4</span>
                        <h2 class="text-2xl font-bold text-gray-800 mt-2">Elige tu Modelo</h2>
                    </div>
                    <form method="get" action="index.php" id="form-modelos">
                        <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
                        <input type="hidden" name="step" value="2">
                        <input type="hidden" name="model_id" id="model_id">
                        <input type="hidden" name="model_price" id="model_price">
                        <div class="grid grid-cols-2 gap-4">
                            <?php foreach ($models as $model): 
                                $sin_stock = $model['stock'] == 0; 
                                $final_price = $model['price'];
                                if ($discount_data) {
                                    $discount_amount = ($discount_data['type'] === 'percent') ? $model['price'] * ($discount_data['value'] / 100) : $discount_data['value'];
                                    $final_price = max(0, $model['price'] - $discount_amount);
                                }
                            ?>
                                <div class="glass-card rounded-2xl p-4 flex flex-col items-center cursor-pointer transition-all border border-transparent hover:border-brand-500 hover:shadow-lg relative group <?= $sin_stock ? 'opacity-60 pointer-events-none grayscale' : '' ?>" 
                                     onclick="<?= !$sin_stock ? "seleccionarModelo({$model['id']}, {$final_price})" : '' ?>">
                                    <?php if ($sin_stock): ?><div class="absolute inset-0 z-10 flex items-center justify-center"><span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded shadow">SIN STOCK</span></div><?php endif; ?>
                                    <div class="h-24 flex items-center justify-center mb-3"><img src="../assets/images/<?= htmlspecialchars($model['image']) ?>" class="max-h-full max-w-full object-contain"></div>
                                    <h3 class="font-bold text-gray-800 text-sm text-center leading-tight mb-1"><?= htmlspecialchars($model['title']) ?></h3>
                                    <div class="mt-auto pt-2"><span class="text-brand-600 font-extrabold text-lg">$<?= number_format($final_price, 0, ',', '.') ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </div>

            <?php elseif ($step == 2): 
                $model_id = $_GET['model_id']; $model_price = $_GET['model_price'];
                $stmt = $pdo->prepare("SELECT * FROM templates WHERE user_id = ?"); $stmt->execute([$user_id]); $plantillas = $stmt->fetchAll();
                if (empty($contenido) && !isset($_GET['linea1']) && !empty($plantillas)) {
                    $ftc = json_decode($plantillas[0]['content'], true);
                    $contenido = ['linea1' => $ftc['linea1'] ?? '', 'linea2' => $ftc['linea2'] ?? '', 'linea3' => $ftc['linea3'] ?? '', 'linea4' => $ftc['linea4'] ?? ''];
                }
                if (isset($_GET['linea1'])) { $contenido = ['linea1' => $_GET['linea1'], 'linea2' => $_GET['linea2'], 'linea3' => $_GET['linea3'], 'linea4' => $_GET['linea4']]; }
            ?>
                <!-- PASO 2: DATOS Y PLANTILLA -->
                <div data-aos="fade-left">
                    <div class="text-center mb-6">
                        <span class="bg-brand-100 text-brand-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Paso 2 de 4</span>
                        <h2 class="text-2xl font-bold text-gray-800 mt-2">Personaliza el Texto</h2>
                    </div>
                    <form method="get" action="index.php" id="form-datos">
                        <input type="hidden" name="u" value="<?= htmlspecialchars($link_code) ?>">
                        <?php if ($editing_order_id): ?> <input type="hidden" name="order_id" value="<?= htmlspecialchars($editing_order_id) ?>"> <?php endif; ?>
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="model_id" value="<?= htmlspecialchars($model_id) ?>">
                        <input type="hidden" name="model_price" value="<?= htmlspecialchars($model_price) ?>">
                        <input type="hidden" name="template_id" id="template_id" required>

                        <div class="glass-card rounded-2xl p-6 mb-6 space-y-4 shadow-sm border border-gray-100">
                            <?php for($i=1; $i<=4; $i++): ?>
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-grow">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold text-xs">L<?= $i ?></span>
                                        <input type="text" name="linea<?= $i ?>" id="linea<?= $i ?>_input" value="<?= htmlspecialchars($contenido['linea'.$i] ?? '') ?>" class="w-full pl-8 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-brand-500 bg-white/50 text-sm shadow-inner" <?= $i==1 ? 'required' : '' ?>> 
                                    </div>
                                    <input type="checkbox" name="chk_linea<?= $i ?>" id="chk_linea<?= $i ?>" <?= ($i==1 || !empty($contenido['linea'.$i])) ? 'checked' : '' ?> class="w-5 h-5 rounded text-brand-600 focus:ring-brand-500 border-gray-300">
                                </div>
                            <?php endfor; ?>
                        </div>

                        <h3 class="font-bold text-gray-700 mb-3 px-2 text-center uppercase tracking-widest text-xs">Selecciona un Diseño</h3>
                        <div class="space-y-6">
                            <?php foreach ($plantillas as $plantilla): ?>
                                <?php $contenido_p = json_decode($plantilla["content"], true); ?>
                                <div class="plantilla-item glass-card rounded-2xl overflow-hidden cursor-pointer border-2 border-transparent transition-all hover:shadow-xl relative h-44 w-full" 
                                     data-template-id="<?= $plantilla['id'] ?>" 
                                     onclick="seleccionarPlantilla(this, <?= $plantilla['id'] ?>)" 
                                     data-template-data='<?= htmlspecialchars(json_encode([
                                         'id' => $plantilla['id'],
                                         'linea1' => ['texto' => $contenido_p['linea1'] ?? '', 'fuente' => $plantilla['fuente_linea_1'], 'tamano' => $plantilla['tamano_linea_1'], 'negrita' => !empty($plantilla['bold_linea_1']), 'alineacion' => $plantilla['alineacion_linea_1'], 'margen' => $plantilla['margen_top_linea_1'], 'mayuscula' => !empty($plantilla['mayus_linea_1'])],
                                         'linea2' => ['texto' => $contenido_p['linea2'] ?? '', 'fuente' => $plantilla['fuente_linea_2'], 'tamano' => $plantilla['tamano_linea_2'], 'negrita' => !empty($plantilla['bold_linea_2']), 'alineacion' => $plantilla['alineacion_linea_2'], 'margen' => $plantilla['margen_top_linea_2'], 'mayuscula' => !empty($plantilla['mayus_linea_2'])],
                                         'linea3' => ['texto' => $contenido_p['linea3'] ?? '', 'fuente' => $plantilla['fuente_linea_3'], 'tamano' => $plantilla['tamano_linea_3'], 'negrita' => !empty($plantilla['bold_linea_3']), 'alineacion' => $plantilla['alineacion_linea_3'], 'margen' => $plantilla['margen_top_linea_3'], 'mayuscula' => !empty($plantilla['mayus_linea_3'])],
                                         'linea4' => ['texto' => $contenido_p['linea4'] ?? '', 'fuente' => $plantilla['fuente_linea_4'], 'tamano' => $plantilla['tamano_linea_4'], 'negrita' => !empty($plantilla['bold_linea_4']), 'alineacion' => $plantilla['alineacion_linea_4'], 'margen' => $plantilla['margen_top_linea_4'], 'mayuscula' => !empty($plantilla['mayus_linea_4'])]
                                     ]), ENT_QUOTES, 'UTF-8') ?>'>
                                    <div class="absolute inset-0 bg-white flex items-center justify-center p-4">
                                        <div class="preview-scale-wrapper pointer-events-none transform scale-[0.85] sm:scale-100">
                                            <div id="template-preview-<?= $plantilla['id'] ?>">
                                                <div class="plantilla-preview-container">
                                                    <div class="plantilla-linea plantilla-linea-1"></div>
                                                    <div class="plantilla-linea plantilla-linea-2"></div>
                                                    <div class="plantilla-linea plantilla-linea-3"></div>
                                                    <div class="plantilla-linea plantilla-linea-4"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="absolute inset-0 bg-black/20 hidden btn-overlay flex items-center justify-center transition-all backdrop-blur-[2px]">
                                        <button type="submit" class="bg-brand-600 text-white font-black py-3 px-10 rounded-full shadow-2xl hover:bg-brand-700 transform hover:scale-110 transition-all text-base tracking-widest uppercase">ELEGIR DISEÑO</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="submit" id="btn-next-step2" class="hidden"></button>
                    </form>
                </div>

            <?php elseif ($step == 3): 
                $model_id = $_GET['model_id']; $template_id = $_GET['template_id']; $model_price = $_GET['model_price'];
                $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?"); $stmt->execute([$template_id]);
                $plantilla_base = $stmt->fetch(PDO::FETCH_ASSOC);
                $lineas_texto = [1 => $_GET['linea1'] ?? '', 2 => $_GET['linea2'] ?? '', 3 => $_GET['linea3'] ?? '', 4 => $_GET['linea4'] ?? ''];
                $lineas_activas = [1 => true];
                for ($i=2; $i<=4; $i++) $lineas_activas[$i] = $editing_order_id ? !empty($lineas_texto[$i]) : isset($_GET["chk_linea$i"]);
                function getStyle($edit_styles, $idx, $key, $default) { return ($edit_styles && isset($edit_styles[$key][$idx])) ? $edit_styles[$key][$idx] : $default; } 
                $initial_template_data = [
                    'id' => $plantilla_base['id'],
                    'linea1' => ['texto' => $lineas_texto[1], 'fuente' => getStyle($edit_styles, 0, 'fuente', $plantilla_base['fuente_linea_1']), 'tamano' => getStyle($edit_styles, 0, 'tamano', $plantilla_base['tamano_linea_1']), 'negrita' => getStyle($edit_styles, 0, 'bold', !empty($plantilla_base['bold_linea_1'])), 'alineacion' => getStyle($edit_styles, 0, 'alineacion', $plantilla_base['alineacion_linea_1']), 'margen' => getStyle($edit_styles, 0, 'margen_top', $plantilla_base['margen_top_linea_1']), 'mayuscula' => getStyle($edit_styles, 0, 'mayuscula', !empty($plantilla_base['mayus_linea_1']))],
                    'linea2' => ['texto' => $lineas_texto[2], 'fuente' => getStyle($edit_styles, 1, 'fuente', $plantilla_base['fuente_linea_2']), 'tamano' => getStyle($edit_styles, 1, 'tamano', $plantilla_base['tamano_linea_2']), 'negrita' => getStyle($edit_styles, 1, 'bold', !empty($plantilla_base['bold_linea_2'])), 'alineacion' => getStyle($edit_styles, 1, 'alineacion', $plantilla_base['alineacion_linea_2']), 'margen' => getStyle($edit_styles, 1, 'margen_top', $plantilla_base['margen_top_linea_2']), 'mayuscula' => getStyle($edit_styles, 1, 'mayuscula', !empty($plantilla_base['mayus_linea_2']))],
                    'linea3' => ['texto' => $lineas_texto[3], 'fuente' => getStyle($edit_styles, 2, 'fuente', $plantilla_base['fuente_linea_3']), 'tamano' => getStyle($edit_styles, 2, 'tamano', $plantilla_base['tamano_linea_3']), 'negrita' => getStyle($edit_styles, 2, 'bold', !empty($plantilla_base['bold_linea_3'])), 'alineacion' => getStyle($edit_styles, 2, 'alineacion', $plantilla_base['alineacion_linea_3']), 'margen' => getStyle($edit_styles, 2, 'margen_top', $plantilla_base['margen_top_linea_3']), 'mayuscula' => getStyle($edit_styles, 2, 'mayuscula', !empty($plantilla_base['mayus_linea_3']))],
                    'linea4' => ['texto' => $lineas_texto[4], 'fuente' => getStyle($edit_styles, 3, 'fuente', $plantilla_base['fuente_linea_4']), 'tamano' => getStyle($edit_styles, 3, 'tamano', $plantilla_base['tamano_linea_4']), 'negrita' => getStyle($edit_styles, 3, 'bold', !empty($plantilla_base['bold_linea_4'])), 'alineacion' => getStyle($edit_styles, 3, 'alineacion', $plantilla_base['alineacion_linea_4']), 'margen' => getStyle($edit_styles, 3, 'margen_top', $plantilla_base['margen_top_linea_4']), 'mayuscula' => getStyle($edit_styles, 3, 'mayuscula', !empty($plantilla_base['mayus_linea_4']))]
                ];
            ?>
                <!-- PASO 3: EDITOR -->
                <script>window.initialTemplateData = <?= json_encode($initial_template_data); ?>;</script>
                <div data-aos="fade-in">
                    <div class="text-center mb-6">
                        <span class="bg-brand-100 text-brand-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Paso 3 de 4</span>
                        <h2 class="text-2xl font-bold text-gray-800 mt-2">Personaliza el Diseño</h2>
                    </div>
                    <form method="post" action="index.php?u=<?= htmlspecialchars($link_code) ?>&step=4" id="editorForm">
                        <?php if ($editing_order_id): ?> <input type="hidden" name="order_id" value="<?= $editing_order_id ?>"> <?php endif; ?>
                        <input type="hidden" name="model_id" value="<?= $model_id ?>"> <input type="hidden" name="template_id" value="<?= $template_id ?>"> <input type="hidden" name="model_price" value="<?= $model_price ?>">
                        
                        <div class="grid grid-cols-1 gap-6">
                            <div class="sticky top-4 z-20"><div class="glass-card rounded-2xl p-3 shadow-xl border border-gray-200 overflow-hidden flex justify-center"><div id="editor-preview-container" class="transform scale-[0.8] sm:scale-100"></div></div></div>
                            <div class="glass-card rounded-2xl p-4 shadow-sm border border-gray-100">
                                <div class="flex space-x-2 overflow-x-auto pb-2 mb-4 border-b border-gray-100">
                                    <?php for ($i = 1; $i <= 4; $i++): if (!$lineas_activas[$i]) continue; ?>
                                        <button type="button" onclick="showTab(<?= $i ?>)" id="tab-btn-<?= $i ?>" class="tab-btn px-4 py-2 rounded-lg text-sm font-bold transition-all bg-gray-100 text-gray-500">Línea <?= $i ?></button>
                                    <?php endfor; ?>
                                </div>
                                <?php for ($i = 1; $i <= 4; $i++): if (!$lineas_activas[$i]) continue; 
                                    $data = $initial_template_data['linea'.$i]; $line_number = $i; $selected_font = $data['fuente']; ?>
                                    <div id="tab-content-<?= $i ?>" class="tab-content hidden space-y-5">
                                        <div><label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Contenido</label>
                                        <input type="text" name="linea<?= $i ?>_texto_editor" value="<?= htmlspecialchars($data['texto']) ?>" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all font-medium text-sm"></div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div><label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Tipografía</label><div class="custom-select-wrapper w-full"><?php include '../admin/includes/_font_selector.php'; ?></div></div>
                                            <div><label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Tamaño</label><div class="flex items-center border border-gray-300 rounded-xl bg-gray-50 overflow-hidden focus-within:ring-2 focus-within:ring-brand-500 transition-all">
                                                <button type="button" class="px-4 py-2.5 text-gray-500" onclick="changeFontSize(<?= $i ?>, -1)"><i class="fas fa-minus text-xs"></i></button>
                                                <input type="text" name="tamano<?= $i ?>" id="tamano<?= $i ?>" value="<?= $data['tamano'] ?>" class="w-full text-center border-none bg-transparent focus:ring-0 p-0 text-sm font-bold text-gray-800" readonly>
                                                <button type="button" class="px-4 py-2.5 text-gray-500" onclick="changeFontSize(<?= $i ?>, 1)"><i class="fas fa-plus text-xs"></i></button></div></div>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="space-y-1.5"><label class="block text-xs font-bold text-gray-700 uppercase tracking-wide">Estilo y Alineación</label>
                                                <div class="flex items-center gap-2"><div class="flex bg-gray-100 p-1 rounded-xl border border-gray-200">
                                                    <input type="hidden" name="alineacion<?= $i ?>" id="alineacion<?= $i ?>" value="<?= $data['alineacion'] ?>">
                                                    <button type="button" onclick="setAlign(<?= $i ?>, 'left', this)" class="p-2.5 w-10 text-gray-400 align-btn" data-val="left"><i class="fas fa-align-left"></i></button>
                                                    <button type="button" onclick="setAlign(<?= $i ?>, 'center', this)" class="p-2.5 w-10 text-gray-400 align-btn" data-val="center"><i class="fas fa-align-center"></i></button>
                                                    <button type="button" onclick="setAlign(<?= $i ?>, 'right', this)" class="p-2.5 w-10 text-gray-400 align-btn" data-val="right"><i class="fas fa-align-right"></i></button></div>
                                                    <label class="flex items-center justify-center w-11 h-11 bg-gray-50 rounded-xl border border-gray-300 cursor-pointer shadow-sm"><input type="checkbox" name="negrita<?= $i ?>" <?= $data['negrita'] ? 'checked' : '' ?> class="hidden peer"><span class="font-black text-gray-400 peer-checked:text-brand-600 text-base transition-colors">B</span></label>
                                                    <label class="flex items-center justify-center w-11 h-11 bg-gray-50 rounded-xl border border-gray-300 cursor-pointer shadow-sm"><input type="checkbox" name="mayuscula<?= $i ?>" <?= $data['mayuscula'] ? 'checked' : '' ?> class="hidden peer"><span class="font-bold text-gray-400 peer-checked:text-brand-600 text-xs uppercase transition-colors">AA</span></label></div></div>
                                            <div><label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wide">Posición Vertical</label><div class="flex items-center gap-3 px-3 py-2.5 bg-gray-50 rounded-xl border border-gray-300"><input type="range" name="margen_top<?= $i ?>" id="margen_top<?= $i ?>" min="-20" max="50" value="<?= $data['margen'] ?>" class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-brand-600"></div></div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <button type="submit" class="w-full mt-8 py-4 bg-brand-600 text-white rounded-xl font-bold text-lg shadow-lg hover:bg-brand-700 transition-all uppercase tracking-wide">Finalizar Diseño <i class="fas fa-check ml-2"></i></button>
                    </form>
                </div>

            <?php elseif ($step == 4): 
                $custom_data = array_merge($_GET, $_POST);
            ?>
                <!-- PASO 4: DATOS FINALES -->
                <div data-aos="fade-up">
                    <div class="text-center mb-6">
                        <span class="bg-brand-100 text-brand-600 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">Paso 4 de 4</span>
                        <h2 class="text-2xl font-bold text-gray-800 mt-2">Detalles de Entrega</h2>
                    </div>
                    <form method="post" action="submit_order.php" class="glass-card rounded-3xl p-8 shadow-2xl border border-gray-100 space-y-5">
                        <?php foreach ($custom_data as $key => $value) { if ($key !== 'step') echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">' . "\n"; } 
                        if ($editing_order_id) echo '<input type="hidden" name="editing_order_id" value="' . htmlspecialchars($editing_order_id) . '">' . "\n"; ?>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Nombre</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all text-sm font-medium"></div>
                            <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Apellido</label>
                            <input type="text" name="lastname" value="<?= htmlspecialchars($edit_data['lastname'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all text-sm font-medium"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all text-sm font-medium"></div>
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Teléfono (WhatsApp)</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($edit_data['phone'] ?? '') ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all text-sm font-bold text-green-600">
                        </div>
                        
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Dirección de Entrega</label>
                        <div class="relative cursor-pointer" onclick="openMapModal()">
                            <input type="text" id="address" name="address" value="<?= htmlspecialchars($edit_data['address'] ?? '') ?>" required readonly class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all text-sm font-medium cursor-pointer" placeholder="Toca para buscar en el mapa...">
                            <input type="hidden" id="lat" name="lat" value="<?= $edit_data['lat'] ?? '' ?>">
                            <input type="hidden" id="lng" name="lng" value="<?= $edit_data['lng'] ?? '' ?>">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-brand-600"><i class="fas fa-search-location"></i></span>
                        </div></div>
                        
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1.5">Indicaciones (Opcional)</label>
                        <textarea name="comments" rows="2" class="w-full p-4 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-brand-500 transition-all text-sm font-medium resize-none"><?= htmlspecialchars($edit_data['comments'] ?? '') ?></textarea></div>
                        <button type="submit" class="w-full py-4 bg-green-600 text-white rounded-2xl font-black text-xl shadow-xl hover:bg-green-700 transition-all active:scale-95 uppercase tracking-wide">CONFIRMAR PEDIDO <i class="fas fa-paper-plane ml-2"></i></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- MAP MODAL -->
    <div id="mapModal" class="fixed inset-0 z-[100] hidden items-end sm:items-center justify-center bg-gray-900/80 backdrop-blur-sm transition-opacity">
        <div class="bg-white w-full sm:w-[600px] h-[90vh] sm:h-[600px] sm:rounded-2xl rounded-t-3xl shadow-2xl flex flex-col overflow-hidden animate-slide-up">
            <div class="p-4 bg-white border-b border-gray-100 flex justify-between items-center z-10">
                <h3 class="font-bold text-gray-800 text-lg">📍 Ubicación de Entrega</h3>
                <button type="button" onclick="closeMapModal()" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center hover:bg-gray-200"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 bg-white z-10 relative">
                <div class="relative shadow-md rounded-xl"><i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i><input type="text" id="map-search-input" class="w-full pl-10 pr-4 py-3 rounded-xl border-none bg-gray-50 focus:ring-2 focus:ring-brand-500 text-sm font-medium shadow-inner" placeholder="Escribe tu dirección..."></div>
            </div>
            <div class="flex-grow relative"><div id="google-map" class="w-full h-full bg-gray-200 shadow-inner"></div></div>
            <div class="p-5 bg-white border-t border-gray-100 z-10 text-center">
                <div class="mb-3"><label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Punto detectado:</label><p id="selected-address-text" class="text-sm font-bold text-gray-800 truncate px-4 italic">...</p></div>
                <button type="button" onclick="confirmLocation()" class="w-full py-3.5 bg-brand-600 text-white rounded-xl font-bold text-lg shadow-lg hover:bg-brand-700 transition-all uppercase tracking-widest">Confirmar Ubicación</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="../assets/js/plantilla-renderer.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        // PASO 1
        function seleccionarModelo(id, price) {
            document.getElementById('model_id').value = id;
            document.getElementById('model_price').value = price;
            document.getElementById('form-modelos').submit();
        }

        // PASO 2
        function seleccionarPlantilla(element, id) {
            document.querySelectorAll('.plantilla-item').forEach(el => {
                el.classList.remove('ring-4', 'ring-brand-500', 'shadow-xl');
                const ov = el.querySelector('.btn-overlay'); if(ov) ov.classList.add('hidden');
            });
            element.classList.add('ring-4', 'ring-brand-500', 'shadow-xl');
            const selectedOverlay = element.querySelector('.btn-overlay');
            if(selectedOverlay) selectedOverlay.classList.remove('hidden');
            document.getElementById('template_id').value = id;
            const data = JSON.parse(element.getAttribute('data-template-data'));
            for(let i=1; i<=4; i++) {
                const input = document.getElementById('linea' + i + '_input');
                const chk = document.getElementById('chk_linea' + i);
                if(input && (!input.value || input.value.trim() === "")) { input.value = data['linea' + i].texto || ""; }
                if(chk && input) { chk.checked = (input.value.trim() !== ""); }
            }
            updatePreviewsRealtime();
        }

        function updatePreviewsRealtime() {
            document.querySelectorAll('.plantilla-item').forEach(item => {
                const id = item.dataset.templateId;
                const data = JSON.parse(item.dataset.templateData);
                const wrapper = document.getElementById('template-preview-' + id);
                const container = wrapper ? wrapper.querySelector('.plantilla-preview-container') : null;
                if(!container) return;
                for(let i=1; i<=4; i++) {
                    const input = document.getElementById('linea' + i + '_input');
                    const chk = document.getElementById('chk_linea' + i);
                    if(chk && !chk.checked) { data['linea' + i].texto = ''; } 
                    else if(input) { data['linea' + i].texto = input.value; }
                }
                window.renderizarPlantilla(container, data, 'black');
            });
        }

        if("<?= $step ?>" === '2') {
            document.querySelectorAll('input[id$="_input"]').forEach(i => i.addEventListener('input', updatePreviewsRealtime));
            document.querySelectorAll('input[id^="chk_linea"]').forEach(i => i.addEventListener('change', updatePreviewsRealtime));
            updatePreviewsRealtime();
        }

        // PASO 3
        if("<?= $step ?>" === '3') {
            const form = document.getElementById('editorForm');
            const previewContainer = document.getElementById('editor-preview-container');
            document.querySelectorAll('select[name^="fuente"]').forEach(select => {
                select.classList.add('w-full', 'px-4', 'py-2.5', 'rounded-xl', 'border', 'border-gray-300', 'bg-gray-50', 'text-gray-900', 'focus:bg-white', 'focus:ring-2', 'focus:ring-brand-500', 'transition-all', 'appearance-none', 'text-sm');
            });
            window.actualizarVistaPreviaEditor = function() {
                const data = JSON.parse(JSON.stringify(window.initialTemplateData));
                if (!previewContainer.innerHTML.trim()) previewContainer.innerHTML = '<div class="plantilla-preview-container"><div class="plantilla-linea plantilla-linea-1"></div><div class="plantilla-linea plantilla-linea-2"></div><div class="plantilla-linea plantilla-linea-3"></div><div class="plantilla-linea plantilla-linea-4"></div></div>';
                for(let i=1; i<=4; i++) {
                    if(!data['linea'+i]) continue;
                    const inputs = { texto: form.querySelector(`[name="linea${i}_texto_editor"]`), fuente: form.querySelector(`[name="fuente${i}"]`), tamano: form.querySelector(`[name="tamano${i}"]`), negrita: form.querySelector(`[name="negrita${i}"]`), alineacion: form.querySelector(`[name="alineacion${i}"]`), margen: form.querySelector(`[name="margen_top${i}"]`), mayuscula: form.querySelector(`[name="mayuscula${i}"]`) };
                    if(inputs.texto) data['linea'+i].texto = inputs.texto.value;
                    if(inputs.fuente) data['linea'+i].fuente = inputs.fuente.value;
                    if(inputs.tamano) data['linea'+i].tamano = parseInt(inputs.tamano.value);
                    if(inputs.negrita) data['linea'+i].negrita = inputs.negrita.checked;
                    if(inputs.alineacion) data['linea'+i].alineacion = inputs.alineacion.value;
                    if(inputs.margen) data['linea'+i].margen = parseInt(inputs.margen.value);
                    if(inputs.mayuscula) data['linea'+i].mayuscula = inputs.mayuscula.checked;
                }
                window.renderizarPlantilla(previewContainer, data, 'black');
            };
            window.showTab = function(n) {
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                document.getElementById('tab-content-'+n).classList.remove('hidden');
                document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('bg-brand-500', 'text-white', 'shadow-md'); b.classList.add('bg-gray-100', 'text-gray-500'); });
                const btn = document.getElementById('tab-btn-'+n); btn.classList.remove('bg-gray-100', 'text-gray-500'); btn.classList.add('bg-brand-500', 'text-white', 'shadow-md');
            };
            window.changeFontSize = function(line, delta) { const el = document.getElementById('tamano'+line); el.value = Math.max(8, parseInt(el.value) + delta); window.actualizarVistaPreviaEditor(); };
            window.setAlign = function(line, val, btn) { document.getElementById('alineacion'+line).value = val; btn.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('text-brand-600')); btn.classList.add('text-brand-600'); window.actualizarVistaPreviaEditor(); };
            form.addEventListener('input', window.actualizarVistaPreviaEditor);
            form.addEventListener('change', window.actualizarVistaPreviaEditor);
            const firstTab = document.querySelector('button[id^="tab-btn-"]'); if(firstTab) firstTab.click();
            for(let i=1; i<=4; i++) { const val = document.getElementById('alineacion'+i)?.value; if(val) { const btn = document.querySelector(`#tab-content-${i} button[data-val="${val}"]`); if(btn) btn.classList.add('text-brand-600'); } }
            window.actualizarVistaPreviaEditor();
        }

        // MAP LOGIC
        let map, marker, geocoder;
        let defaultLocation = { lat: -34.603722, lng: -58.381592 }; 
        function initMapModal() {
            const savedLat = document.getElementById('lat').value; const savedLng = document.getElementById('lng').value;
            if(savedLat && savedLng) defaultLocation = { lat: parseFloat(savedLat), lng: parseFloat(savedLng) };
            if(map) return; 
            map = new google.maps.Map(document.getElementById("google-map"), { center: defaultLocation, zoom: 17, disableDefaultUI: true, zoomControl: true });
            geocoder = new google.maps.Geocoder();
            marker = new google.maps.Marker({ map: map, position: defaultLocation, draggable: true, animation: google.maps.Animation.DROP, icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png' });
            const searchInput = document.getElementById("map-search-input");
            const autocomplete = new google.maps.places.Autocomplete(searchInput, { componentRestrictions: {'country': ['ar']} });
            autocomplete.bindTo("bounds", map);
            autocomplete.addListener("place_changed", () => {
                const place = autocomplete.getPlace(); if (!place.geometry || !place.geometry.location) return;
                if (place.geometry.viewport) map.fitBounds(place.geometry.viewport); else { map.setCenter(place.geometry.location); map.setZoom(17); }
                marker.setPosition(place.geometry.location); updateCoordinates(place.geometry.location); document.getElementById("selected-address-text").textContent = place.formatted_address;
            });
            marker.addListener("dragend", () => { const pos = marker.getPosition(); updateCoordinates(pos); geocodePosition(pos); });
            map.addListener("click", (e) => { marker.setPosition(e.latLng); updateCoordinates(e.latLng); geocodePosition(e.latLng); });
        }
        function updateCoordinates(latLng) { document.getElementById("lat").value = latLng.lat(); document.getElementById("lng").value = latLng.lng(); }
        function geocodePosition(pos) {
            if (!geocoder) geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: pos }, (results, status) => {
                if (status === "OK" && results[0]) document.getElementById("selected-address-text").textContent = results[0].formatted_address;
                else document.getElementById("selected-address-text").textContent = "Punto marcado manualmente";
            });
        }
        function openMapModal() {
            document.getElementById('mapModal').classList.remove('hidden'); document.getElementById('mapModal').classList.add('flex');
            if(!map) { if(typeof google !== 'undefined') initMapModal(); else alert("Cargando Google Maps..."); }
            setTimeout(() => { google.maps.event.trigger(map, "resize"); map.setCenter(marker.getPosition()); }, 100);
        }
        function closeMapModal() { document.getElementById('mapModal').classList.add('hidden'); document.getElementById('mapModal').classList.remove('flex'); }
        function confirmLocation() {
            const searchVal = document.getElementById("map-search-input").value;
            const geocodeVal = document.getElementById("selected-address-text").textContent;
            let addressToSave = searchVal;
            if (!addressToSave || addressToSave.trim() === "") addressToSave = geocodeVal;
            if (addressToSave.includes("Punto marcado") || addressToSave.includes("...")) addressToSave = document.getElementById("address").value;
            if(addressToSave && addressToSave.trim() !== "") { document.getElementById("address").value = addressToSave; closeMapModal(); }
            else alert("Selecciona una dirección.");
        }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAXXtVhQP_A1Ix8lgPtkpwqR6XVzAQLQzs&libraries=places&callback=initMapModal" async defer></script>
</body>
</html>