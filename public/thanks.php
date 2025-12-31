<?php
require_once '../admin/includes/db.php';
session_start();

$order_code = $_GET['order'] ?? null;
$link_code = $_GET['u'] ?? null;

if (!$order_code || !$link_code) {
    die("Pedido no válido.");
}

// 1. OBTENER DATOS COMPLETOS DEL PEDIDO Y DEL USUARIO
$stmt = $pdo->prepare("
    SELECT o.*, 
           u.name AS admin_name, u.email AS admin_email, u.logo AS admin_logo,
           u.footer AS admin_footer, u.whatsapp AS admin_whatsapp, u.color_primary AS color,
           u.color_secundary, u.background_image,
           u.referral_active, u.referral_type, u.referral_value,
           m.title AS model_title, m.description AS model_description, m.image AS model_image,
           t.nombre AS template_name,
           cs.color AS status_color
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN templates t ON o.template_id = t.id
    JOIN models m ON o.model_id = m.id
    LEFT JOIN custom_statuses cs ON o.status = cs.status_name AND o.user_id = cs.user_id
    WHERE o.order_code = ? AND u.link_code = ?
");
$stmt->execute([$order_code, $link_code]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pedido no encontrado.");
}

// 2. DECODIFICAR ESTILOS Y PREPARAR DATOS PARA EL RENDERIZADOR
$custom_styles = json_decode($order['styles'], true);

$plantilla_data = [
    'id' => $order['template_id'],
    'nombre' => $order['template_name']
];

for ($i = 1; $i <= 4; $i++) {
    $plantilla_data["linea$i"] = [
        'texto' => $order["text_line$i"],
        'fuente' => $custom_styles['fuente'][$i-1] ?? 'Arial',
        'tamano' => $custom_styles['tamano'][$i-1] ?? '16',
        'negrita' => $custom_styles['bold'][$i-1] ?? false,
        'alineacion' => $custom_styles['alineacion'][$i-1] ?? 'center',
        'margen' => $custom_styles['margen_top'][$i-1] ?? '0',
        'mayuscula' => $custom_styles['mayuscula'][$i-1] ?? false
    ];
}

// 3. PREPARAR FUENTES DE GOOGLE
$fonts_to_load = [];
if (isset($custom_styles['fuente']) && is_array($custom_styles['fuente'])) {
    foreach ($custom_styles['fuente'] as $font) {
        if (!empty($font)) {
            $fonts_to_load[] = $font;
        }
    }
}
$fonts_to_load = array_unique($fonts_to_load);

$google_fonts_url = '';
if (!empty($fonts_to_load)) {
    $font_families = [];
    foreach ($fonts_to_load as $font) {
        $font_families[] = 'family=' . urlencode($font) . ':wght@400;700';
    }
    $google_fonts_url = 'https://fonts.googleapis.com/css2?' . implode('&', $font_families) . '&display=swap';
}

// Construct Base URL for links
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$thanks_url = $protocol . $domainName . $_SERVER['REQUEST_URI'];

// REFERRAL LOGIC
$referral_active = $order['referral_active'] ?? 0;
$referral_link = "";
if ($referral_active) {
    $scriptPath = dirname($_SERVER['PHP_SELF']); 
    $baseUrl = $protocol . $domainName . str_replace('/thanks.php', '/index.php', $_SERVER['PHP_SELF']);
    $referral_link = $baseUrl . "?u=" . $link_code . "&ref=" . $order_code;
}

// Prepare WhatsApp Message
$wa_message = "Hola! Acabo de realizar el pedido nro #" . $order['id'] . ". Podes ver los detalles y el diseño aquí: " . $thanks_url;
$wa_link = "https://wa.me/+549" . preg_replace('/[^0-9]/', '', $order['admin_whatsapp']) . "?text=" . urlencode($wa_message);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Gracias por tu pedido! - <?= htmlspecialchars($order['admin_name']) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '<?= htmlspecialchars($order['color']) ?>',
                            600: '<?= htmlspecialchars($order['color']) ?>',
                            900: '<?= htmlspecialchars($order['color_secundary'] ?? $order['color']) ?>',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <?php if (!empty($google_fonts_url)): ?>
        <link href="<?= $google_fonts_url ?>" rel="stylesheet">
    <?php endif; ?>

    <link rel="stylesheet" href="../assets/css/plantilla-preview.css">

    <style>
        body {
            background-color: #f3f4f6;
            <?php if($order['background_image']): ?>
            background-image: url('../assets/images/bg/<?= $order['background_image'] ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .plantilla-preview-container {
            box-sizing: content-box !important;
        }

        .plantilla-preview-wrapper {
            position: relative;
            width: 280px; 
            height: 110px;
            border: 1px solid #ddd;
            border-radius: 12px;
            background-color: #fff;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .plantilla-preview-wrapper .plantilla-preview-container {
            width: 425px !important;
            height: 145px !important;
            flex-shrink: 0;
            transform: scale(0.6);
            transform-origin: center center;
            margin: 0 !important;
            padding: 10px !important;
            background: #fff !important;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen flex flex-col">

    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-3xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <?php if($order['admin_logo']): ?>
                    <img src="../assets/images/<?= htmlspecialchars($order['admin_logo']) ?>" class="h-10 w-auto object-contain">
                <?php endif; ?>
                <span class="text-lg font-bold text-gray-800 border-l border-gray-200 pl-3 leading-none"><?= htmlspecialchars($order['admin_name']) ?></span>
            </div>
            <a href="https://wa.me/+549<?= preg_replace('/[^0-9]/', '', $order['admin_whatsapp']) ?>" target="_blank" 
               class="text-green-600 hover:text-green-700 transition-colors">
                <i class="fab fa-whatsapp text-2xl"></i>
            </a>
        </div>
    </header>

    <main class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-lg mx-auto space-y-6">
            
            <!-- Main Content Card -->
            <div class="glass-card rounded-[2.5rem] shadow-2xl overflow-hidden border border-white/50" data-aos="zoom-in">
                
                <div class="bg-brand-600 p-8 text-center text-white relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                        <i class="fas fa-stamp text-[10rem] absolute -bottom-10 -right-10 transform rotate-12"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                            <i class="fas fa-check text-2xl"></i>
                        </div>
                        <h1 class="text-3xl font-black tracking-tight">¡Pedido Recibido!</h1>
                        <p class="text-white/80 font-medium mt-1 uppercase tracking-widest text-xs">Orden #<?= $order['id'] ?></p>
                    </div>
                </div>

                <div class="p-8">
                    <p class="text-center text-gray-600 font-medium leading-relaxed mb-8 text-lg">
                        ¡Gracias <strong><?= htmlspecialchars($order['name']) ?></strong> por tu pedido!<br>Así quedó tu diseño:
                    </p>

                    <!-- Preview Section -->
                    <div class="flex flex-col items-center justify-center mb-8 bg-gray-50/50 p-6 rounded-3xl border border-gray-100 shadow-inner">
                        <div class="plantilla-preview-wrapper" 
                             id="final-preview" 
                             data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
                            <?php 
                            $plantilla = $plantilla_data; 
                            include '../admin/includes/_plantilla_preview.php'; 
                            ?>
                        </div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Vista previa de tu sello</p>
                    </div>

                    <!-- WhatsApp Button -->
                    <a href="<?= $wa_link ?>" 
                       class="flex items-center justify-center gap-3 w-full py-5 bg-[#25D366] text-white rounded-2xl font-black text-base sm:text-xl shadow-xl shadow-green-500/30 hover:bg-[#1ebe5d] hover:scale-[1.02] transition-all transform active:scale-95" target="_blank">
                       <i class="fab fa-whatsapp text-xl sm:text-2xl"></i> FINALIZAR POR WHATSAPP
                    </a>

                    <?php if ($referral_active && !empty($referral_link)): ?>
                        <!-- Referral Section -->
                        <div class="mt-8 p-6 bg-brand-50 rounded-3xl border border-brand-100 text-center relative overflow-hidden group">
                            <div class="absolute -top-4 -right-4 w-20 h-20 bg-brand-200/30 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                            
                            <h3 class="text-lg font-bold text-brand-700 mb-2 flex items-center justify-center gap-2">
                                <span>🎁</span> ¡Regalá un descuento!
                            </h3>
                            <?php 
                                $discount_text = ($order['referral_type'] === 'percent') ? number_format($order['referral_value'], 0) . "%" : "$" . number_format($order['referral_value'], 0);
                            ?>
                            <p class="text-sm text-brand-600/80 mb-4">Compartí este link con amigos. <br>Si compran, tendrán <strong><?= $discount_text ?> off</strong>.</p>
                            
                            <div class="flex items-center gap-2">
                                <div class="flex-grow bg-white border border-brand-200 px-4 py-2.5 rounded-xl text-[10px] font-mono text-brand-500 truncate shadow-inner">
                                    <?= $referral_link ?>
                                </div>
                                <button onclick="navigator.clipboard.writeText('<?= $referral_link ?>'); alert('¡Enlace copiado!');" 
                                        class="p-3 bg-white border border-brand-200 text-brand-600 rounded-xl hover:bg-brand-100 transition-colors shadow-sm">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 gap-4" data-aos="fade-up" data-aos-delay="200">
                <div class="glass-card rounded-3xl p-6 border border-white/50 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg" style="background-color: <?= htmlspecialchars($order['status_color'] ?? '#e0e0e0') ?>">
                            <i class="fas fa-stream"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Estado Actual</p>
                            <p class="font-bold text-gray-800"><?= htmlspecialchars(ucfirst($order['status'])) ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="glass-card rounded-3xl p-5 border border-white/50 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Modelo</p>
                        <div class="flex items-center gap-2">
                            <?php if (!empty($order['model_image'])): ?>
                                <img src="../assets/images/<?= htmlspecialchars($order['model_image']) ?>" class="w-8 h-8 object-contain rounded-lg border border-gray-100 bg-white">
                            <?php endif; ?>
                            <p class="font-bold text-gray-800 text-sm truncate"><?= htmlspecialchars($order['model_title']) ?></p>
                        </div>
                    </div>
                    <div class="glass-card rounded-3xl p-5 border border-white/50 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Importe</p>
                        <p class="font-black text-brand-600 text-xl tracking-tight">$<?= number_format($order['price'], 0, ',', '.') ?></p>
                    </div>
                </div>

                <div class="glass-card rounded-3xl p-6 border border-white/50 shadow-sm space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Lugar de Entrega</p>
                            <p class="text-sm font-medium text-gray-700 leading-tight"><?= htmlspecialchars($order["address"]) ?></p>
                        </div>
                    </div>
                    <?php if (!empty($order['comments'])): ?>
                        <div class="flex items-start gap-4 pt-4 border-t border-gray-100">
                            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                <i class="fas fa-comment-alt"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Indicaciones</p>
                                <p class="text-sm italic text-gray-600"><?= nl2br(htmlspecialchars($order['comments'])) ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white/80 backdrop-blur-md border-t border-gray-200 py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <div class="text-gray-400 text-sm mb-4">
                <?= $order['admin_footer'] ?>
            </div>
            <p class="text-[10px] font-bold text-gray-300 uppercase tracking-[0.2em]">&copy; <?= date('Y') ?> SoloSellos - Desarrollado con ❤️</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="../assets/js/plantilla-renderer.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });

        document.addEventListener('DOMContentLoaded', () => {
            const previewWrapper = document.getElementById('final-preview');
            const templateDataAttr = previewWrapper.getAttribute('data-template-data');

            if (templateDataAttr) {
                try {
                    const templateData = JSON.parse(templateDataAttr);
                    const previewContainer = previewWrapper.querySelector('.plantilla-preview-container');
                    if (previewContainer) {
                        window.renderizarPlantilla(previewContainer, templateData, 'black');
                    }
                } catch (e) {
                    console.error('Error al renderizar la vista previa final:', e);
                }
            }
        });
    </script>
</body>
</html>