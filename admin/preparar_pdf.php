<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$pedidos_ids = [];
$user_id = $_SESSION['user']['id'];
$layout_state = 'null';
$batch_id = null;

// Check if we are regenerating from a batch
if (isset($_GET['batch_id'])) {
    $batch_id = $_GET['batch_id'];
    
    // Verify the user owns this batch and get layout state
    $stmt = $pdo->prepare("SELECT user_id, layout_state FROM export_batches WHERE id = ?");
    $stmt->execute([$batch_id]);
    $batch_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($batch_data && $batch_data['user_id'] == $user_id) {
        // Fetch order IDs from the batch
        $stmt = $pdo->prepare("SELECT order_id FROM exported_orders WHERE batch_id = ?");
        $stmt->execute([$batch_id]);
        $pedidos_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Pass the layout state to the frontend. It might be null.
        $layout_state = $batch_data['layout_state'] ?? 'null';
    } else {
        die("No tienes permiso para ver este lote de exportación.");
    }

} 
// Otherwise, create a new batch from a list of orders
else if (isset($_GET['pedidos'])) {
    $pedidos_ids_str = $_GET['pedidos'] ?? '';
    if (empty($pedidos_ids_str)) {
        die("No se han seleccionado pedidos.");
    }

    $pedidos_ids = explode(',', $pedidos_ids_str);
    $pedidos_ids = array_filter($pedidos_ids, 'is_numeric');

    if (!empty($pedidos_ids)) {
        try {
            $pdo->beginTransaction();

            // 1. Create a new export batch
            $stmt = $pdo->prepare("INSERT INTO export_batches (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $batch_id = $pdo->lastInsertId();

            // 2. Link orders to the new batch
            $stmt = $pdo->prepare("INSERT INTO exported_orders (batch_id, order_id) VALUES (?, ?)");
            foreach ($pedidos_ids as $order_id) {
                $stmt->execute([$batch_id, $order_id]);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error al guardar el lote de exportación: " . $e->getMessage());
        }
    }
}

// 1. Obtener y validar los IDs de los pedidos
if (empty($pedidos_ids)) {
    die("No se han seleccionado pedidos o el lote es inválido.");
}

$placeholders = implode(',', array_fill(0, count($pedidos_ids), '?'));

// 2. Obtener los datos de los pedidos de la BD
$sql = "SELECT * FROM orders WHERE id IN ($placeholders) AND user_id = ?";
$params = array_merge($pedidos_ids, [$_SESSION['user']['id']]);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($orders)) {
    die("No se encontraron los pedidos seleccionados o no tienes permiso para verlos.");
}

// 3. Lógica para precargar las fuentes de Google
$fonts = [];
foreach ($orders as $order) {
    $styles = json_decode($order["styles"], true);
    if (is_array($styles) && isset($styles["fuente"])) {
        foreach ($styles["fuente"] as $font) {
            if (!empty($font) && !in_array($font, $fonts)) {
                $fonts[] = $font;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preparar PDF para Impresión</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Incluir los enlaces a las fuentes de Google para que el navegador las descargue -->
    <?php if (!empty($fonts)): ?>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <?php foreach ($fonts as $font): ?>
            <link href="https://fonts.googleapis.com/css2?family=<?= urlencode($font) ?>:wght@400;700&display=swap" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #bdc3c7; /* Color de fondo gris para la página */
            margin: 0;
            padding-top: 70px; /* Espacio para la barra fija + margen */
            padding-left: 60px; /* Espacio para la barra de herramientas */
        }
        .controls { 
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #fff; 
            padding: 10px 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            display: flex;
            align-items: center;
            z-index: 1000;
            box-sizing: border-box;
        }
        .controls .title {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
        }
        #generate-pdf-btn, #save-layout-btn { 
            margin-left: 20px;
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 14px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        #generate-pdf-btn {
            margin-left: auto; /* Empuja el botón a la derecha */
            background-color: #28a745; 
        }
        #save-layout-btn {
            background-color: #007bff;
        }
        #generate-pdf-btn:hover { background-color: #218838; }
        #save-layout-btn:hover { background-color: #0056b3; }

        #loading-msg { 
            font-size: 14px; 
            color: #6c757d;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }
        
        #a4-sheet { 
            background: #e0e0e0; 
            width: 210mm; 
            height: 297mm; 
            margin: 20px auto; 
            box-shadow: 0 0 15px rgba(0,0,0,0.1); 
            position: relative; 
            overflow: hidden; 
        }
        
        .stamp-item {
            width: 38mm;
            height: 14mm;
            position: absolute;
            cursor: move;
            background-color: transparent; /* Fondo transparente */
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            border: 1px solid transparent; /* Borde transparente */
            user-select: none;
            z-index: 1; /* Asegura que los sellos estén por encima de los rectángulos de fondo */
        }

        .stamp-item .plantilla-preview-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding-top: 5px; /* Añadido para bajar el texto sin afectar espaciados */
            box-sizing: border-box; /* Asegura que el padding no aumente la altura total */
            /* Se quita justify-content y align-items para que los estilos de cada línea tengan efecto */
            transform: scale(0.98); /* Pequeño ajuste para que no toque los bordes */
        }
        .stamp-item .plantilla-linea {
            width: 100%;
            white-space: nowrap;
            line-height: 1.1;
        }

        #toolbar {
            position: fixed;
            left: 0;
            top: 60px; /* Debajo de la barra superior */
            width: 50px;
            background: #fff;
            padding: 5px 0;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 0 8px 8px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            z-index: 1001;
        }
        #toolbar button {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
        }
        #toolbar button:hover:not(:disabled) {
            background-color: #f0f0f0;
        }
        #toolbar button:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .rect-item {
            position: absolute;
            cursor: move;
            background-color: black;
            box-sizing: border-box;
            border: 1px solid transparent;
            user-select: none;
            z-index: 0; /* Por debajo de los sellos */
        }
        .stamp-item.selected, .rect-item.selected {
            border: 2px solid #007bff !important;
            box-shadow: 0 0 10px rgba(0,123,255,0.5);
        }

        /* Estilos de la cuadrícula */
        #grid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            display: none; /* Oculto por defecto */
        }
        #grid-overlay.active {
            display: block;
        }
        .grid-line {
            position: absolute;
            background-color: #e0e0e0;
            opacity: 0.7;
        }
        .grid-line.vertical {
            width: 1px;
            height: 100%;
        }
        .grid-line.horizontal {
            width: 100%;
            height: 1px;
        }
        #grid-overlay.active {
            display: block;
        }
        #guia-visual {
            position: absolute;
            width: 38mm;
            height: 14mm;
            border: 1.5px dotted red;
            box-sizing: border-box;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 100;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>

<div class="controls">
    <span class="title">Mesa de Trabajo</span>
    <span id="loading-msg">Cargando fuentes y diseños, por favor espere...</span>
    <button id="save-layout-btn">Guardar Diseño</button>
    <button id="generate-pdf-btn">Generar PDF</button>
</div>

<div id="toolbar">
    <button id="rotate-cw-btn" title="Girar 90° Derecha">↻</button>
    <button id="rotate-ccw-btn" title="Girar 90° Izquierda">↺</button>
<button id="duplicate-btn" title="Duplicar">👯</button>
<button id="add-guide-box-btn" title="Añadir Caja Guía">⬚</button>
    <button id="draw-rect-btn" title="Dibujar Rectángulo">⬛</button>
    <button id="grid-btn" title="Mostrar/Ocultar Cuadrícula">⊞</button>
    <button id="snap-btn" title="Activar/Desactivar Ajuste">🧲</button>
    <button id="delete-btn" title="Eliminar">🗑️</button>
    <hr style="width: 80%;">
    <button id="zoom-in-btn" title="Acercar" disabled>+</button>
    <button id="zoom-out-btn" title="Alejar" disabled>-</button>
</div>

<div id="a4-sheet" style="visibility: hidden;" data-batch-id="<?= $batch_id ?? '' ?>" data-layout-state='<?= $layout_state ?? 'null' ?>'>
    <div id="guia-visual" class="hidden"></div>
    <div id="grid-overlay">
        <?php
            // Generar líneas de la cuadrícula con divs para máxima compatibilidad
            $width_mm = 210;
            $height_mm = 297;
            $spacing_mm = 5;

            // Líneas verticales
            for ($x = $spacing_mm; $x < $width_mm; $x += $spacing_mm) {
                echo "<div class='grid-line vertical' style='left: {$x}mm;'></div>";
            }
            // Líneas horizontales
            for ($y = $spacing_mm; $y < $height_mm; $y += $spacing_mm) {
                echo "<div class='grid-line horizontal' style='top: {$y}mm;'></div>";
            }
        ?>
    </div>
    <?php foreach ($orders as $order): ?>
        <?php
            $styles = json_decode($order['styles'], true);
            $plantilla_data = ['id' => $order['template_id']];
            for ($i = 1; $i <= 4; $i++) {
                $plantilla_data["linea$i"] = [
                    'texto' => $order["text_line$i"],
                    'fuente' => $styles['fuente'][$i-1] ?? 'Arial',
                    'tamano' => $styles['tamano'][$i-1] ?? 10,
                    'negrita' => $styles['bold'][$i-1] ?? false,
                    'alineacion' => $styles['alineacion'][$i-1] ?? 'center',
                    'margen' => $styles['margen_top'][$i-1] ?? 0,
                    'mayuscula' => $styles['mayuscula'][$i-1] ?? false
                ];
            }
        ?>
        <div class="stamp-item" data-order-id="<?= $order['id'] ?>" data-template-data='<?= htmlspecialchars(json_encode($plantilla_data), ENT_QUOTES, 'UTF-8') ?>'>
            <div class="plantilla-preview-container">
                <div class="plantilla-linea plantilla-linea-1"></div>
                <div class="plantilla-linea plantilla-linea-2"></div>
                <div class="plantilla-linea plantilla-linea-3"></div>
                <div class="plantilla-linea plantilla-linea-4"></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="../assets/js/plantilla-renderer.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Contenedores y variables principales
    const hojaA4 = document.getElementById('a4-sheet');
    const loadingMsg = document.getElementById('loading-msg');
    
    // Estado de la aplicación
    let allItems = []; 
    let itemSeleccionado = null;
    let snapActivado = false;
    const mmToPx = 3.7795;
    const batchId = hojaA4.dataset.batchId;

    // --- Lógica de Guardado y Restauración ---
    async function guardarDiseño() {
        const saveBtn = document.getElementById('save-layout-btn');
        saveBtn.textContent = 'Guardando...';
        saveBtn.disabled = true;

        const layout = allItems.map(item => {
            const transform = item.style.transform || '';
            const angleMatch = transform.match(/rotate\(([^)]+)deg\)/);
            const scaleMatch = transform.match(/scale\(([^)]+)\)/);
            const isRect = item.classList.contains('rect-item');

            return {
                id: item.id,
                order_id: isRect ? null : item.dataset.orderId,
                is_rect: isRect,
                x: parseFloat(item.getAttribute('data-x')) || 0,
                y: parseFloat(item.getAttribute('data-y')) || 0,
                angle: angleMatch ? parseFloat(angleMatch[1]) : 0,
                scale: scaleMatch ? parseFloat(scaleMatch[1]) : 1,
                width: parseFloat(item.style.width) || null,
                height: parseFloat(item.style.height) || null,
            };
        });

        try {
            const response = await fetch('guardar_layout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    batch_id: batchId,
                    layout_state: JSON.stringify(layout)
                })
            });
            const result = await response.json();
            if (result.success) {
                saveBtn.textContent = 'Guardado ✓';
                setTimeout(() => { saveBtn.textContent = 'Guardar Diseño'; }, 2000);
            } else {
                throw new Error(result.message || 'Error desconocido al guardar.');
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            alert('No se pudo guardar el diseño: ' + error.message);
            saveBtn.textContent = 'Error al Guardar';
        } finally {
            saveBtn.disabled = false;
        }
    }

    function restaurarDiseño(layoutState) {
        if (!layoutState) return;
        try {
            const layout = JSON.parse(layoutState);
            if (!Array.isArray(layout)) return;

            layout.forEach(itemState => {
                let element = document.getElementById(itemState.id);

                if (!element && itemState.is_rect) {
                    element = document.createElement('div');
                    element.id = itemState.id;
                    element.classList.add('rect-item');
                    hojaA4.appendChild(element);
                    addSelectionListeners(element);
                    initInteractableForRect(element);
                    allItems.push(element);
                }

                if (!element) return;
                
                element.style.left = `${itemState.x}px`;
                element.style.top = `${itemState.y}px`;
                element.setAttribute('data-x', itemState.x);
                element.setAttribute('data-y', itemState.y);
                
                const angle = itemState.angle || 0;
                let transform = `rotate(${angle}deg)`;

                if (itemState.is_rect) {
                    element.style.width = `${itemState.width}px`;
                    element.style.height = `${itemState.height}px`;
                } else {
                    const scale = itemState.scale || 1;
                    transform += ` scale(${scale})`;
                    element.setAttribute('data-scale', scale);
                }
                element.style.transform = transform;
                element.setAttribute('data-angle', angle);
            });
        } catch (e) {
            console.error("Error al restaurar el diseño:", e);
        }
    }


    // --- Lógica de Zoom ---
    const zoomInBtn = document.getElementById('zoom-in-btn');
    const zoomOutBtn = document.getElementById('zoom-out-btn');
    let zoomLevel = 1.0;
    const zoomStep = 0.1;
    zoomInBtn.disabled = false;
    zoomOutBtn.disabled = false;
    hojaA4.style.transformOrigin = 'top center';

    zoomInBtn.addEventListener('click', () => {
        zoomLevel += zoomStep;
        hojaA4.style.transform = `scale(${zoomLevel})`;
    });

    zoomOutBtn.addEventListener('click', () => {
        if (zoomLevel > 0.2) {
            zoomLevel -= zoomStep;
            hojaA4.style.transform = `scale(${zoomLevel})`;
        }
    });

    // --- Funciones de Interactividad ---

    function initInteractable(element) {
        const dragOptions = {
            listeners: {
                move(event) {
                    const target = event.target;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                    target.style.left = `${x}px`;
                    target.style.top = `${y}px`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                }
            },
            inertia: true,
            modifiers: [interact.modifiers.restrictRect({ restriction: 'parent', endOnly: true })]
        };

        return interact(element)
            .draggable(dragOptions)
            .gesturable({
                 listeners: {
                    move (event) {
                        const target = event.target;
                        const angle = (parseFloat(target.getAttribute('data-angle')) || 0) + event.da;
                        const scale = parseFloat(target.getAttribute('data-scale')) || 1;
                        
                        target.style.transform = `rotate(${angle}deg) scale(${scale})`;
                        target.setAttribute('data-angle', angle);
                    }
                }
            })
            .resizable({
                edges: { top: true, left: true, bottom: true, right: true },
                listeners: {
                    move: function (event) {
                        const target = event.target;
                        const angle = parseFloat(target.getAttribute('data-angle')) || 0;
                        
                        const baseWidthPx = 38 * mmToPx; 
                        const newScale = event.rect.width / baseWidthPx;

                        target.style.transform = `rotate(${angle}deg) scale(${newScale})`;
                        target.setAttribute('data-scale', newScale);

                        let x = (parseFloat(target.getAttribute('data-x')) || 0) + event.deltaRect.left;
                        let y = (parseFloat(target.getAttribute('data-y')) || 0) + event.deltaRect.top;
                        target.style.left = x + 'px';
                        target.style.top = y + 'px';
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);
                    }
                },
                modifiers: [
                    interact.modifiers.aspectRatio({
                        ratio: 'preserve',
                        equalDelta: true,
                    })
                ],
                inertia: false
            });
    }

    function initInteractableForRect(element) {
        const dragOptions = {
            listeners: {
                move(event) {
                    const target = event.target;
                    const x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
                    const y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;
                    target.style.left = `${x}px`;
                    target.style.top = `${y}px`;
                    target.setAttribute('data-x', x);
                    target.setAttribute('data-y', y);
                }
            },
            inertia: true,
            modifiers: [interact.modifiers.restrictRect({ restriction: 'parent', endOnly: true })]
        };

        return interact(element)
            .draggable(dragOptions)
            .gesturable({
                 listeners: {
                    move (event) {
                        const target = event.target;
                        const angle = (parseFloat(target.getAttribute('data-angle')) || 0) + event.da;
                        target.style.transform = `rotate(${angle}deg)`;
                        target.setAttribute('data-angle', angle);
                    }
                }
            })
            .resizable({
                edges: { top: true, left: true, bottom: true, right: true },
                listeners: {
                    move: function (event) {
                        const target = event.target;
                        let x = (parseFloat(target.getAttribute('data-x')) || 0);
                        let y = (parseFloat(target.getAttribute('data-y')) || 0);
                        target.style.width = `${event.rect.width}px`;
                        target.style.height = `${event.rect.height}px`;
                        x += event.deltaRect.left;
                        y += event.deltaRect.top;
                        target.style.left = x + 'px';
                        target.style.top = y + 'px';
                        target.setAttribute('data-x', x);
                        target.setAttribute('data-y', y);
                    }
                },
                modifiers: [interact.modifiers.restrictSize({ min: { width: 10, height: 10 } })],
                inertia: false
            });
    }
    
    function addSelectionListeners(item) {
        item.addEventListener('click', (e) => {
            if (itemSeleccionado) {
                itemSeleccionado.classList.remove('selected');
            }
            itemSeleccionado = e.currentTarget;
            itemSeleccionado.classList.add('selected');
            e.stopPropagation();
        });
    }

    // --- Lógica de Dibujo de Rectángulos ---
    let isDrawingMode = false;
    let isCurrentlyDrawing = false;
    let newRectElement = null;
    let startX, startY;

    const drawRectBtn = document.getElementById('draw-rect-btn');
    drawRectBtn.addEventListener('click', () => {
        isDrawingMode = !isDrawingMode;
        if (isDrawingMode) {
            hojaA4.style.cursor = 'crosshair';
            drawRectBtn.style.backgroundColor = '#007bff';
            drawRectBtn.style.color = 'white';
        } else {
            hojaA4.style.cursor = 'default';
            drawRectBtn.style.backgroundColor = '';
            drawRectBtn.style.color = '';
        }
    });

    hojaA4.addEventListener('mousedown', (e) => {
        if (!isDrawingMode || e.target.closest('.stamp-item, .rect-item')) return;
        e.preventDefault();
        isCurrentlyDrawing = true;
        const rect = hojaA4.getBoundingClientRect();
        startX = (e.clientX - rect.left) / zoomLevel;
        startY = (e.clientY - rect.top) / zoomLevel;

        newRectElement = document.createElement('div');
        newRectElement.classList.add('rect-item');
        newRectElement.style.left = `${startX}px`;
        newRectElement.style.top = `${startY}px`;
        newRectElement.style.width = '0px';
        newRectElement.style.height = '0px';
        hojaA4.appendChild(newRectElement);
    });

    document.addEventListener('mousemove', (e) => {
        if (!isCurrentlyDrawing) return;
        const rect = hojaA4.getBoundingClientRect();
        const currentX = (e.clientX - rect.left) / zoomLevel;
        const currentY = (e.clientY - rect.top) / zoomLevel;
        const width = currentX - startX;
        const height = currentY - startY;
        newRectElement.style.width = `${Math.abs(width)}px`;
        newRectElement.style.height = `${Math.abs(height)}px`;
        newRectElement.style.left = `${width < 0 ? currentX : startX}px`;
        newRectElement.style.top = `${height < 0 ? currentY : startY}px`;
    });

    document.addEventListener('mouseup', (e) => {
        if (!isCurrentlyDrawing) return;
        isCurrentlyDrawing = false;
        const finalWidth = parseFloat(newRectElement.style.width);
        const finalHeight = parseFloat(newRectElement.style.height);
        
        if (finalWidth > 5 && finalHeight > 5) {
            const finalX = parseFloat(newRectElement.style.left);
            const finalY = parseFloat(newRectElement.style.top);
            newRectElement.id = `rect-${allItems.length}`;
            newRectElement.setAttribute('data-x', finalX);
            newRectElement.setAttribute('data-y', finalY);
            addSelectionListeners(newRectElement);
            initInteractableForRect(newRectElement);
            allItems.push(newRectElement);
        } else {
            newRectElement.remove();
        }
        isDrawingMode = false;
        hojaA4.style.cursor = 'default';
        drawRectBtn.style.backgroundColor = '';
        drawRectBtn.style.color = '';
    });

    // --- Inicialización y Lógica de Botones de la Barra de Herramientas ---

    document.fonts.ready.then(() => {
        loadingMsg.textContent = '¡Listo! Organiza los sellos y genera el PDF.';
        setTimeout(() => { if(loadingMsg) loadingMsg.style.display = 'none'; }, 2000);
        hojaA4.style.visibility = 'visible';
        
        const initialSellos = document.querySelectorAll('.stamp-item');
        initialSellos.forEach((sello, index) => {
            const dataAttr = sello.getAttribute('data-template-data');
            if (dataAttr) {
                try {
                    const data = JSON.parse(dataAttr);
                    const container = sello.querySelector('.plantilla-preview-container');
                    window.renderizarPlantilla(container, data, 'white', 0.3);
                } catch (e) { console.error("Error renderizando sello:", e); }
            }
            sello.id = `sello-${sello.dataset.orderId || index}`;
            addSelectionListeners(sello);
            initInteractable(sello);
            allItems.push(sello);
        });

        // Restore layout if it exists
        const layoutState = hojaA4.dataset.layoutState;
        if (layoutState && layoutState !== 'null') {
             restaurarDiseño(layoutState);
        } else {
            // Default placement if no layout is saved
            const stampWidth = 38 * mmToPx;
            const stampHeight = 14 * mmToPx;
            const numItems = initialSellos.length;
            const numCols = Math.ceil(Math.sqrt(numItems));
            initialSellos.forEach((sello, index) => {
                const row = Math.floor(index / numCols);
                const col = index % numCols;
                const x = 10 + col * (stampWidth + 5);
                const y = 10 + row * (stampHeight + 5);
                sello.style.left = `${x}px`;
                sello.style.top = `${y}px`;
                sello.setAttribute('data-x', x);
                sello.setAttribute('data-y', y);
            });
        }
        
        document.body.addEventListener('click', (e) => {
            if (itemSeleccionado && !itemSeleccionado.contains(e.target) && !e.target.closest('#toolbar')) {
                itemSeleccionado.classList.remove('selected');
                itemSeleccionado = null;
            }
        });
        
        document.getElementById('save-layout-btn').addEventListener('click', guardarDiseño);

        document.getElementById('snap-btn').addEventListener('click', () => {
            snapActivado = !snapActivado;
        });

        document.getElementById('delete-btn').addEventListener('click', () => {
            if (itemSeleccionado) {
                const index = allItems.indexOf(itemSeleccionado);
                if (index > -1) { allItems.splice(index, 1); }
                itemSeleccionado.remove();
                itemSeleccionado = null;
            } else { alert("Por favor, selecciona un item para eliminar."); }
        });

        document.getElementById('rotate-cw-btn').addEventListener('click', () => {
            if (itemSeleccionado) {
                const target = itemSeleccionado;
                const scale = parseFloat(target.getAttribute('data-scale')) || 1;
                let angle = (parseFloat(target.getAttribute('data-angle')) || 0) + 90;
                let transform = `rotate(${angle}deg)`;
                if (!target.classList.contains('rect-item')) {
                    transform += ` scale(${scale})`;
                }
                target.style.transform = transform;
                target.setAttribute('data-angle', angle);
            } else { alert("Por favor, selecciona un item para girar."); }
        });

        document.getElementById('rotate-ccw-btn').addEventListener('click', () => {
            if (itemSeleccionado) {
                const target = itemSeleccionado;
                const scale = parseFloat(target.getAttribute('data-scale')) || 1;
                let angle = (parseFloat(target.getAttribute('data-angle')) || 0) - 90;
                let transform = `rotate(${angle}deg)`;
                if (!target.classList.contains('rect-item')) {
                    transform += ` scale(${scale})`;
                }
                target.style.transform = transform;
                target.setAttribute('data-angle', angle);
            } else { alert("Por favor, selecciona un item para girar."); }
        });

        document.getElementById('duplicate-btn').addEventListener('click', () => {
            if (itemSeleccionado) {
                const clon = itemSeleccionado.cloneNode(true);
                clon.classList.remove('selected');
                clon.id = `${itemSeleccionado.classList.contains('rect-item') ? 'rect' : 'clon'}-${allItems.length}`;
                let x = (parseFloat(itemSeleccionado.getAttribute('data-x')) || 0) + 10;
                let y = (parseFloat(itemSeleccionado.getAttribute('data-y')) || 0) + 10;
                clon.style.left = `${x}px`;
                clon.style.top = `${y}px`;
                clon.setAttribute('data-x', x);
                clon.setAttribute('data-y', y);
                addSelectionListeners(clon);

                if (itemSeleccionado.classList.contains('stamp-item')) {
                    initInteractable(clon);
                } else {
                    initInteractableForRect(clon);
                }

                hojaA4.appendChild(clon);
                allItems.push(clon);
            } else { alert("Por favor, selecciona un item para duplicar."); }
        });
        
        document.getElementById('grid-btn').addEventListener('click', () => {
            document.getElementById('grid-overlay').classList.toggle('active');
        });

        document.getElementById('add-guide-box-btn').addEventListener('click', () => {
            document.getElementById('guia-visual').classList.toggle('hidden');
        });

        const btnGenerar = document.getElementById('generate-pdf-btn');
        btnGenerar.addEventListener('click', () => {
            btnGenerar.textContent = 'Generando...';
            btnGenerar.disabled = true;

            const currentSelection = itemSeleccionado;
            const sheetOriginalColor = hojaA4.style.backgroundColor || getComputedStyle(hojaA4).backgroundColor;

            if (currentSelection) {
                currentSelection.classList.remove('selected');
            }
            hojaA4.style.backgroundColor = 'white';

            html2canvas(hojaA4, { scale: 5, useCORS: true, backgroundColor: null }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                pdf.addImage(imgData, 'PNG', 0, 0, 210, 297);
                pdf.save('sellos_para_imprimir.pdf');
            }).catch(err => {
                console.error("Error al generar el PDF:", err);
                alert("Hubo un error al generar el PDF.");
            }).finally(() => {
                hojaA4.style.backgroundColor = sheetOriginalColor;
                if (currentSelection) {
                    currentSelection.classList.add('selected');
                }
                btnGenerar.textContent = 'Generar PDF';
                btnGenerar.disabled = false;
            });
        });
    });
});
</script>

</body>
</html>