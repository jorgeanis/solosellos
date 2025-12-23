<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

// --- STATUS MANAGEMENT LOGIC ---
$user_id = $_SESSION['user']['id'];

// Handle Delete Status
if (isset($_GET['delete_status'])) {
    $status_id_to_delete = $_GET['delete_status'];
    $stmt = $pdo->prepare("DELETE FROM custom_statuses WHERE id = ? AND user_id = ?");
    $stmt->execute([$status_id_to_delete, $user_id]);
    // Redirect to avoid re-deleting on refresh
    header("Location: personalizar.php?tab=pedidos");
    exit;
}

// Handle Add Status
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_status'])) {
    $new_status_name = $_POST['new_status_name'] ?? '';
    $new_status_color = $_POST['new_status_color'] ?? '#FFFFFF';
    if (!empty($new_status_name)) {
        $stmt = $pdo->prepare("INSERT INTO custom_statuses (user_id, status_name, color) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $new_status_name, $new_status_color]);
    }
    // Redirect to show the new status and clear POST
    header("Location: personalizar.php?tab=pedidos");
    exit;
}

// Fetch existing statuses
$stmt = $pdo->prepare("SELECT * FROM custom_statuses WHERE user_id = ? ORDER BY display_order ASC, status_name ASC");
$stmt->execute([$user_id]);
$custom_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
// --- END STATUS MANAGEMENT LOGIC ---

$success_message   = '';
$error_message     = '';
$password_success  = '';
$password_error    = '';
$show_password_modal = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check for password change form submission
    if (isset($_POST['password_change'])) {
        $show_password_modal = true;
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new === $confirm) {
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user']['id']]);
            $hash = $stmt->fetchColumn();

            if ($hash && password_verify($current, $hash)) {
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['user']['id']]);
                $password_success = 'Contraseña actualizada.';
            } else {
                $password_error = 'La contraseña actual no es correcta.';
            }
        } else {
            $password_error = 'Las nuevas contraseñas no coinciden.';
        }
    } 
    // Handle the main form submission (General Settings)
    elseif (!isset($_POST['add_status'])) {
        $color = $_POST['color_primary'];
        $email = $_POST['email'] ?? '';
        $name  = $_POST['name'] ?? '';
        $color_secundary = $_POST['color_secundary'] ?? '';
        $background_image = $_POST['background_image'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';

        $welcome = $_POST['welcome'];
        $footer  = $_POST['footer'];
        $logo    = $_SESSION['user']['logo'];

        // Referral Settings
        $referral_active = isset($_POST['referral_active']) ? 1 : 0;
        $referral_type = $_POST['referral_type'] ?? 'percent';
        $referral_value = $_POST['referral_value'] ?? 0.00;

        if (!empty($_FILES["logo"]["name"])) {
            $logo = basename($_FILES["logo"]["name"]);
            move_uploaded_file($_FILES["logo"]["tmp_name"], "../assets/images/" . $logo);
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, color_primary = ?, color_secundary = ?, logo = ?, footer = ?, whatsapp = ?, background_image = ?, welcome = ?, referral_active = ?, referral_type = ?, referral_value = ? WHERE id = ?");
        $stmt->execute([$name, $email, $color, $color_secundary, $logo, $footer, $whatsapp, $background_image, $welcome, $referral_active, $referral_type, $referral_value, $_SESSION['user']['id']]);

        // Update session variables
        $_SESSION['user']['color_primary'] = $color;
        $_SESSION['user']['logo']  = $logo;
        $_SESSION['user']['footer'] = $footer;
        $_SESSION['user']['name']   = $name;
        $_SESSION['user']['email']  = $email;
        $_SESSION['user']['color_secundary'] = $color_secundary;
        $_SESSION['user']['whatsapp'] = $whatsapp;
        $_SESSION['user']['welcome'] = $welcome;
        $_SESSION['user']['background_image'] = $background_image;
        $_SESSION['user']['referral_active'] = $referral_active;
        $_SESSION['user']['referral_type'] = $referral_type;
        $_SESSION['user']['referral_value'] = $referral_value;
        
        $success_message = "Cambios guardados correctamente.";
    }
}

// Determine active tab
$active_tab = $_GET['tab'] ?? 'general';
?>

<style>
/* Tab System Styles */
.tabs-nav {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #e0e0e0;
    margin-bottom: 25px;
    padding-bottom: 0;
}
.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    transition: all 0.3s ease;
}
.tab-btn:hover {
    color: #1abc9c;
    background-color: #f9f9f9;
}
.tab-btn.active {
    color: #1abc9c;
    border-bottom-color: #1abc9c;
    font-weight: bold;
}
.tab-content {
    display: none;
    animation: fadeIn 0.4s ease;
}
.tab-content.active {
    display: block;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Form Styles */
.settings-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    padding: 25px;
    margin-bottom: 20px;
    border: 1px solid #eee;
}
.settings-card h3 {
    margin-top: 0;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 15px;
    margin-bottom: 20px;
    font-size: 18px;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}
.settings-card label {
    font-weight: 600;
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: #444;
}
.form-group {
    margin-bottom: 20px;
}
input[type="text"], input[type="email"], input[type="number"], select, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-sizing: border-box;
    font-size: 14px;
}
.color-pickers {
    display: flex;
    gap: 20px;
    align-items: center;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
}
.bg-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(100px, 1fr));
    gap:15px;
}
.bg-grid label {
    height: 150px;
    border: 3px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    background-size: cover;
    background-position: center;
    transition: all 0.2s;
    display: block;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.bg-grid input[type='radio'] { opacity: 0; position: absolute; }
.bg-grid input[type='radio']:checked + label {
    border-color: #1abc9c;
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(26, 188, 156, 0.3);
}
.main-save-button {
    /* Existing style override for FAB */
    display: none; /* Hide old buttons */
}
/* FAB Button Styles */
.fab-save {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    background: #1abc9c;
    color: white;
    border-radius: 30px; /* Circle initially */
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(26, 188, 156, 0.4);
    cursor: pointer;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    z-index: 1000;
    border: none;
    font-family: inherit;
}
.fab-save .icon {
    font-size: 24px;
    transition: transform 0.4s ease;
}
.fab-save .text {
    width: 0;
    opacity: 0;
    white-space: nowrap;
    margin-left: 0;
    font-weight: bold;
    font-size: 16px;
    transition: all 0.4s ease;
}
.fab-save:hover {
    width: 180px; /* Expand width */
    border-radius: 30px;
    background: #16a085;
    padding-right: 15px; /* Add padding for text */
}
.fab-save:hover .icon {
    transform: rotate(360deg); /* Rotate icon */
}
.fab-save:hover .text {
    width: auto;
    opacity: 1;
    margin-left: 10px;
}
</style>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <h2>Personalización del Sitio</h2>
    <?php if($success_message): ?>
        <div style="padding: 10px 20px; background: #d4edda; color: #155724; border-radius: 5px; font-weight: bold;">
            <?= $success_message ?>
        </div>
    <?php endif; ?>
</div>

<!-- Tabs Navigation -->
<div class="tabs-nav">
    <button class="tab-btn <?= $active_tab == 'general' ? 'active' : '' ?>" onclick="openTab('general')">⚙️ General</button>
    <button class="tab-btn <?= $active_tab == 'apariencia' ? 'active' : '' ?>" onclick="openTab('apariencia')">🎨 Apariencia</button>
    <button class="tab-btn <?= $active_tab == 'pedidos' ? 'active' : '' ?>" onclick="openTab('pedidos')">📋 Estados de Pedido</button>
    <button class="tab-btn <?= $active_tab == 'referidos' ? 'active' : '' ?>" onclick="openTab('referidos')">🎁 Sistema de Referidos</button>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="background_image" id="background_image" value="<?= htmlspecialchars($_SESSION['user']['background_image'] ?? '') ?>">

    <!-- TAB 1: GENERAL -->
    <div id="tab-general" class="tab-content <?= $active_tab == 'general' ? 'active' : '' ?>">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <!-- Columna Izquierda: Datos del Negocio -->
            <div class="settings-card" style="margin-bottom: 0; height: 100%;">
                <h3>👤 Datos del Negocio</h3>
                <div class="form-group">
                    <label for="name">Nombre del Negocio:</label>
                    <input type="text" id="name" name="name" value="<?= $_SESSION['user']['name'] ?>">
                </div>
                <div class="form-group">
                    <label for="whatsapp">WhatsApp (sin símbolos):</label>
                    <input type="text" id="whatsapp" name="whatsapp" value="<?= $_SESSION['user']['whatsapp'] ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email de notificaciones:</label>
                    <input type="email" id="email" name="email" value="<?= $_SESSION['user']['email'] ?? '' ?>">
                </div>
                <div style="margin-top: 25px;">
                     <button type="button" onclick="openPasswordModal()" style="background: #34495e; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        🔐 Cambiar contraseña de acceso
                     </button>
                </div>
            </div>

            <!-- Columna Derecha: Logo -->
            <div class="settings-card" style="margin-bottom: 0; height: 100%;">
                <h3>🖼️ Logo del Sitio</h3>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center;">
                    <?php if ($_SESSION['user']['logo']): ?>
                        <div style="border: 1px dashed #ccc; padding: 15px; border-radius: 10px; margin-bottom: 20px; background: #fafafa; width: 80%; display: flex; justify-content: center;">
                            <img src="../assets/images/<?= $_SESSION['user']['logo'] ?>" style="max-height: 100px; max-width: 100%; object-fit: contain;">
                        </div>
                    <?php else: ?>
                        <div style="margin-bottom: 20px; color: #999; font-style: italic;">Sin logo actual</div>
                    <?php endif; ?>
                    
                    <label for="logo" style="cursor: pointer; background: #3498db; color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; display: inline-block;">
                        📂 Seleccionar Nuevo Archivo
                    </label>
                    <input type="file" id="logo" name="logo" style="display: none;" onchange="document.getElementById('file-name').textContent = this.files[0].name">
                    <span id="file-name" style="margin-top: 10px; font-size: 13px; color: #666;"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: APARIENCIA -->
    <div id="tab-apariencia" class="tab-content <?= $active_tab == 'apariencia' ? 'active' : '' ?>">
        <div class="settings-card">
            <h3>🎨 Colores de la Marca</h3>
            <div class="color-pickers">
                <div>
                    <label for="color_primary" style="font-weight:normal;">Color Principal</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="color" name="color_primary" id="color_primary" value="<?= $_SESSION['user']['color_primary'] ?>" style="width: 50px; height: 40px; border: none; padding: 0; cursor: pointer;">
                        <span style="font-family:monospace;"><?= $_SESSION['user']['color_primary'] ?></span>
                    </div>
                </div>
                <div>
                    <label for="color_secundary" style="font-weight:normal;">Color Secundario</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="color" name="color_secundary" id="color_secundary" value="<?= $_SESSION['user']['color_secundary'] ?>" style="width: 50px; height: 40px; border: none; padding: 0; cursor: pointer;">
                        <span style="font-family:monospace;"><?= $_SESSION['user']['color_secundary'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <h3>🌄 Fondo del Sitio</h3>
            <div class="bg-grid">
                <?php
                  $fondos = glob('../assets/images/bg/*.jpg');
                  $fondo_actual = $_SESSION['user']['background_image'] ?? '';
                  foreach ($fondos as $fondo) {
                    $nombre = basename($fondo);
                    $checked = ($nombre === $fondo_actual) ? 'checked' : '';
                    echo "<div>
                            <input type='radio' name='bg_select' value='$nombre' id='bg_$nombre' $checked>
                            <label for='bg_$nombre' style=\"background-image: url('../assets/images/bg/$nombre');\"></label>
                          </div>";
                  }
                ?>
            </div>
        </div>

        <div class="settings-card">
            <h3>📝 Textos</h3>
            <div class="form-group">
                <label for="welcome">Mensaje de bienvenida (Inicio):</label>
                <textarea id="welcome" name="welcome" rows="3"><?php echo htmlspecialchars($_SESSION['user']['welcome'] ?? '', ENT_QUOTES); ?></textarea>
            </div>
            <div class="form-group">
                <label>Texto del Pie de Página (Footer):</label>
                <div id="editor" style="height:150px; background: white;"><?= $_SESSION['user']['footer'] ?? "" ?></div>
                <textarea name="footer" id="footer" style="display:none"><?= $_SESSION['user']['footer'] ?? "" ?></textarea>
            </div>
        </div>
    </div>

    <!-- TAB 3: PEDIDOS -->
    <div id="tab-pedidos" class="tab-content <?= $active_tab == 'pedidos' ? 'active' : '' ?>">
        <div class="settings-card">
            <h3>🏷️ Gestión de Estados</h3>
            <p style="color: #666; margin-bottom: 15px;">Define los estados por los que pasa un pedido para organizar tu flujo de trabajo.</p>
            
            <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; border: 1px dashed #ccc; margin-bottom: 20px;">
                <label style="margin-bottom: 10px;">Añadir Nuevo Estado:</label>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                     <input type="text" name="new_status_name" placeholder="Ej: En Producción" style="flex: 1; min-width: 200px;">
                     <input type="color" name="new_status_color" value="#3498db" style="width: 50px; height: 38px; padding: 0; border: none; cursor: pointer;">
                     <button type="submit" name="add_status" value="1" style="background: #2c3e50; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">+ Agregar</button>
                </div>
            </div>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f0f0f0; text-align: left;">
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Color</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd;">Nombre del Estado</th>
                        <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($custom_statuses as $status): ?>
                        <tr>
                            <td style="padding: 10px; border-bottom: 1px solid #eee; width: 60px;">
                                <div style="width: 30px; height: 30px; background-color: <?= htmlspecialchars($status['color']) ?>; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #eee;">
                                <strong><?= htmlspecialchars($status['status_name']) ?></strong>
                            </td>
                            <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: right;">
                                <a href="personalizar.php?delete_status=<?= $status['id'] ?>&tab=pedidos" onclick="return confirm('¿Estás seguro de que deseas eliminar este estado?');" style="color: #e74c3c; text-decoration: none; padding: 5px 10px; border: 1px solid #e74c3c; border-radius: 4px; font-size: 13px;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($custom_statuses)): ?>
                        <tr><td colspan="3" style="padding: 20px; text-align: center; color: #999;">No hay estados personalizados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: REFERIDOS -->
    <div id="tab-referidos" class="tab-content <?= $active_tab == 'referidos' ? 'active' : '' ?>">
        <div class="settings-card">
            <h3>🎁 Configuración de Referidos</h3>
            <p style="color: #666; font-size: 15px; margin-bottom: 20px; line-height: 1.5;">
                Incentiva a tus clientes a compartir tu página. Cuando un cliente refiere a otro, el nuevo cliente obtiene un descuento automático en su primera compra.
            </p>
            
            <div style="background: #e8f5e9; border: 1px solid #a5d6a7; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                <div style="display: flex; align-items: center; margin-bottom: 15px;">
                    <input type="checkbox" id="referral_active" name="referral_active" value="1" <?= !empty($_SESSION['user']['referral_active']) ? 'checked' : '' ?> style="width: 20px; height: 20px; margin-right: 10px;">
                    <label for="referral_active" style="margin: 0; font-size: 16px; color: #2e7d32; cursor: pointer;">Activar Sistema de Referidos</label>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div class="form-group">
                    <label for="referral_type">Tipo de Descuento:</label>
                    <select name="referral_type" id="referral_type" style="background: #fff;">
                        <option value="percent" <?= ($_SESSION['user']['referral_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Porcentaje (%)</option>
                        <option value="fixed" <?= ($_SESSION['user']['referral_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Monto Fijo ($)</option>
                    </select>
                    <small style="color: #777;">Elige si descontar un % del total o una cantidad fija de dinero.</small>
                </div>
                <div class="form-group">
                    <label for="referral_value">Valor del Descuento:</label>
                    <input type="number" step="0.01" name="referral_value" id="referral_value" value="<?= htmlspecialchars($_SESSION['user']['referral_value'] ?? '0.00') ?>" style="font-size: 18px; font-weight: bold;">
                    <small style="color: #777;">Ejemplo: Si es porcentaje pon 10 (para 10%). Si es fijo pon 500 (para $500).</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button type="submit" class="fab-save" title="Guardar Cambios">
        <span class="icon">💾</span>
        <span class="text">Guardar Cambios</span>
    </button>

</form>

<style>
/* Modal Styles */
@keyframes fadeInDown { from { opacity:0; transform:translateY(-30px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeOutUp   { from { opacity:1; transform:translateY(0); } to { opacity:0; transform:translateY(-30px); } }
@keyframes shake {
  0%,100%{transform:translateX(0);}
  20%,60%{transform:translateX(-10px);}
  40%,80%{transform:translateX(10px);}
}
.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;}
.modal.mostrar{display:flex;}
.modal-contenido{background:#fff;padding:25px;border-radius:10px;width:350px;animation:fadeInDown .3s forwards; box-shadow: 0 10px 25px rgba(0,0,0,0.2);}
.modal-cerrar{animation:fadeOutUp .3s forwards;}
.shake{animation:shake .3s;}
.mensaje-error{color:#e74c3c;text-align:center;margin:0 0 10px; font-weight: bold;}
.mensaje-exito{color:#27ae60;text-align:center;margin:0 0 10px; font-weight: bold;}
</style>

<div id="passwordModal" class="modal<?= $show_password_modal ? ' mostrar' : '' ?>">
  <div class="modal-contenido<?= $password_error ? ' shake' : '' ?>">
    <span style="float:right; cursor:pointer; font-size: 20px;" onclick="closePasswordModal()">&times;</span>
    <h3 style="margin-top:0;">Cambiar contraseña</h3>
    <?php if ($password_success): ?>
      <p class="mensaje-exito" id="passwordMessage"><?= $password_success ?></p>
    <?php elseif ($password_error): ?>
      <p class="mensaje-error" id="passwordMessage"><?= $password_error ?></p>
    <?php else: ?>
      <p id="passwordMessage" style="display:none;"></p>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="password_change" value="1">
      <label>Contraseña actual:</label><br>
      <input type="password" name="current_password" required style="width:100%; margin-bottom:10px;"><br>
      <label>Nueva contraseña:</label><br>
      <input type="password" name="new_password" required style="width:100%; margin-bottom:10px;"><br>
      <label>Confirmar contraseña:</label><br>
      <input type="password" name="confirm_password" required style="width:100%; margin-bottom:15px;"><br>
      <button type="submit" style="width:100%; background: #1abc9c; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold;">Actualizar</button>
    </form>
  </div>
</div>

<!-- Quill editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
// Tab Switching Logic
function openTab(tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    // Deactivate all buttons
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    // Show target content
    document.getElementById('tab-' + tabName).classList.add('active');
    // Activate target button
    document.querySelector(`.tab-btn[onclick="openTab('${tabName}')"]`).classList.add('active');

    // Update URL without reload to persist state on refresh if desired (optional)
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
}

document.addEventListener("DOMContentLoaded", function () {
    const quill = new Quill('#editor', {
        theme: 'snow'
    });

    // Establecer contenido inicial desde el textarea
    const hiddenTextarea = document.getElementById("footer");
    quill.root.innerHTML = hiddenTextarea.value;

    // Actualizar textarea cuando se envía el formulario
    const form = document.querySelector("form");
    if (form) {
        form.addEventListener("submit", function () {
            hiddenTextarea.value = quill.root.innerHTML;
        });
    }
});

document.querySelectorAll("input[type=radio][name=bg_select]").forEach(el => {
  el.addEventListener("change", () => {
    document.getElementById("background_image").value = el.value;
  });
});
const selected = document.querySelector("input[name='bg_select']:checked");
if (selected) {
  document.getElementById('background_image').value = selected.value;
}

function openPasswordModal() {
  const modal = document.getElementById('passwordModal');
  modal.classList.add('mostrar');
  modal.querySelector('.modal-contenido').classList.remove('modal-cerrar');
}

function closePasswordModal() {
  const modal = document.getElementById('passwordModal');
  const cont = modal.querySelector('.modal-contenido');
  cont.classList.add('modal-cerrar');
  setTimeout(() => {
    modal.classList.remove('mostrar');
    cont.classList.remove('modal-cerrar');
  }, 300);
}
<?php if ($show_password_modal): ?>
document.addEventListener('DOMContentLoaded', openPasswordModal);
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>