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
    header("Location: personalizar.php");
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
    header("Location: personalizar.php");
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
    // Handle status add form submission, check for a specific name
    if (isset($_POST['add_status'])) {
        $new_status_name = trim($_POST['new_status_name'] ?? '');
        $new_status_color = $_POST['new_status_color'] ?? '#FFFFFF';
        if (!empty($new_status_name)) {
            $stmt = $pdo->prepare("INSERT INTO custom_statuses (user_id, status_name, color) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $new_status_name, $new_status_color]);
            header("Location: personalizar.php"); // Redirect to prevent form resubmission
            exit;
        }
    }
    // Check for password change form submission
    elseif (isset($_POST['password_change'])) {
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
    // Handle the main form submission
    else {
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
    }
}
?>

<style>
/* New layout styles */
.settings-container {
    display: grid;
    grid-template-columns: repeat(12, 1fr); /* 12-column grid */
    gap: 20px;
    margin-bottom: 20px;
}
.settings-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    padding: 20px;
    grid-column: span 12; /* Default: full width on small screens */
}
/* Spanning rules for specific cards on larger screens */
@media (min-width: 992px) {
    #card-perfil { grid-column: span 6; }
    #card-marca { grid-column: span 6; }
    #card-estados { grid-column: span 7; }
    #card-contenido { grid-column: span 5; }
    #card-fondo { grid-column: span 12; }
}
.settings-card h3 {
    margin-top: 0;
    border-bottom: 2px solid #1abc9c;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-size: 18px;
    color: #2c3e50;
}
.settings-card label {
    font-weight: bold;
    display: block;
    margin-bottom: 5px;
    font-size: 14px;
}
.settings-card input[type="text"],
.settings-card input[type="email"],
.settings-card textarea {
    width: 100%;
    max-width: 400px; /* Constrain max width of text inputs */
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
}
.settings-card .color-pickers {
    display: flex;
    gap: 20px;
    align-items: center;
}
.bg-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(100px, 1fr));
    gap:15px;
}
.bg-grid label {
    width: 100px;
    height: 180px;
    border: 3px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    position: relative;
    background-size: cover;
    background-position: center;
    transition: border-color 0.2s;
    display: inline-block;
}
.bg-grid input[type='radio'] {
    opacity: 0;
}
.bg-grid input[type='radio']:checked + .bg-image-label {
    border-color: #1abc9c;
}
.main-save-button {
    background: #1abc9c;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    padding: 10px 25px;
    font-size: 16px;
    transition: background 0.2s;
}
.main-save-button:hover {
    background: #16a085;
}
</style>

<h2>Personalización del sitio</h2>
<p>
  <a href="setup_wizard.php?force=1" style="background:#1abc9c;color:#fff;padding:6px 12px;border-radius:4px;text-decoration:none;">Ejecutar asistente</a>
</p>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="background_image" id="background_image" value="<?= htmlspecialchars($_SESSION['user']['background_image'] ?? '') ?>">

    <div class="settings-container">

        <!-- Card 1: Perfil y Contacto -->
        <div class="settings-card" id="card-perfil">
            <h3>Perfil y Contacto</h3>
            <label for="name">Nombre:</label>
            <input type="text" id="name" name="name" value="<?= $_SESSION['user']['name'] ?>"><br><br>

            <label for="whatsapp">Número de WhatsApp:</label>
            <input type="text" id="whatsapp" name="whatsapp" value="<?= $_SESSION['user']['whatsapp'] ?>"><br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= $_SESSION['user']['email'] ?? '' ?>"><br><br>

            <button type="button" onclick="openPasswordModal()">Cambiar contraseña</button>
        </div>

        <!-- Card 2: Marca y Colores -->
        <div class="settings-card" id="card-marca">
            <h3>Marca y Colores</h3>
            <label for="logo">Logo actual:</label>
            <?php if ($_SESSION['user']['logo']): ?>
                <img src="../assets/images/<?= $_SESSION['user']['logo'] ?>" style="height:80px; display:block; margin-bottom:10px;"><br>
            <?php endif; ?>
            <input type="file" id="logo" name="logo"><br><br>

            <label>Colores de la marca:</label>
            <div class="color-pickers">
                <div>
                    <label for="color_primary" style="font-weight:normal;">Primario</label>
                    <input type="color" name="color_primary" id="color_primary" value="<?= $_SESSION['user']['color_primary'] ?>" style="width: 50px; height: 35px; padding: 2px; border-radius: 5px;">
                </div>
                <div>
                    <label for="color_secundary" style="font-weight:normal;">Secundario</label>
                    <input type="color" name="color_secundary" id="color_secundary" value="<?= $_SESSION['user']['color_secundary'] ?>" style="width: 50px; height: 35px; padding: 2px; border-radius: 5px;">
                </div>
            </div>
        </div>

        <!-- Card 3: Estados de Pedido -->
        <div class="settings-card" id="card-estados">
            <h3>Gestionar Estados de Pedidos</h3>
            <div>
                <?php foreach ($custom_statuses as $status): ?>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <div style="width: 20px; height: 20px; background-color: <?= htmlspecialchars($status['color']) ?>; border: 1px solid #666; border-radius: 4px;"></div>
                        <span><?= htmlspecialchars($status['status_name']) ?></span>
                        <a href="personalizar.php?delete_status=<?= $status['id'] ?>" onclick="return confirm('¿Estás seguro de que deseas eliminar este estado?');" style="color: red; text-decoration: none; margin-left:auto;">[Eliminar]</a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($custom_statuses)): ?>
                    <p>No has añadido ningún estado personalizado.</p>
                <?php endif; ?>
            </div>
            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee;">
                <label for="new_status_name">Añadir Nuevo Estado</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                     <input type="text" id="new_status_name" name="new_status_name" placeholder="Nombre del estado" style="margin:0;">
                     <input type="color" name="new_status_color" value="#e6f0ff" style="width: 50px; height: 38px; padding: 2px; border-radius:5px; margin:0;">
                     <button type="submit" name="add_status" style="width: auto; margin:0; padding: 8px 12px; font-size:14px;">Añadir</button>
                </div>
            </div>
        </div>

        <!-- Card 4: Contenido del Sitio -->
        <div class="settings-card" id="card-contenido">
            <h3>Contenido del Sitio</h3>
            <label for="welcome">Mensaje de bienvenida:</label>
            <textarea id="welcome" name="welcome" rows="3"><?php echo htmlspecialchars($_SESSION['user']['welcome'] ?? '', ENT_QUOTES); ?></textarea><br><br>

            <label>Texto del footer:</label>
            <div id="editor" style="height:150px;"><?= $_SESSION['user']['footer'] ?? "" ?></div>
            <textarea name="footer" id="footer" style="display:none"><?= $_SESSION['user']['footer'] ?? "" ?></textarea>
        </div>

        <!-- Card 5: Fondo del Sitio -->
        <div class="settings-card" id="card-fondo">
            <h3>Fondo del Sitio</h3>
            <div class="bg-grid">
                <?php
                  $fondos = glob('../assets/images/bg/*.jpg');
                  $fondo_actual = $_SESSION['user']['background_image'] ?? '';
                  foreach ($fondos as $fondo) {
                    $nombre = basename($fondo);
                    $checked = ($nombre === $fondo_actual) ? 'checked' : '';
                    echo "<div>
                            <input type='radio' name='bg_select' value='$nombre' id='bg_$nombre' $checked>
                            <label for='bg_$nombre' class='bg-image-label' style=\"background-image: url('../assets/images/bg/$nombre');\"></label>
                          </div>";
                  }
                ?>
            </div>
        </div>

        <!-- Card 6: Sistema de Referidos -->
        <div class="settings-card" id="card-referidos" style="grid-column: span 12;">
            <h3>Sistema de Referidos</h3>
            <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
                Configura el sistema de referidos para que tus clientes puedan invitar a otros y obtengan un descuento.
            </p>
            
            <div style="margin-bottom: 15px; display: flex; align-items: center;">
                <input type="checkbox" id="referral_active" name="referral_active" value="1" <?= !empty($_SESSION['user']['referral_active']) ? 'checked' : '' ?> style="width: auto; margin-right: 10px;">
                <label for="referral_active" style="margin-bottom: 0; cursor: pointer;">Activar sistema de referidos</label>
            </div>

            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div>
                    <label for="referral_type">Tipo de descuento</label>
                    <select name="referral_type" id="referral_type" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="percent" <?= ($_SESSION['user']['referral_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>Porcentaje (%)</option>
                        <option value="fixed" <?= ($_SESSION['user']['referral_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Monto Fijo ($)</option>
                    </select>
                </div>
                <div>
                    <label for="referral_value">Valor del descuento</label>
                    <input type="number" step="0.01" name="referral_value" id="referral_value" value="<?= htmlspecialchars($_SESSION['user']['referral_value'] ?? '0.00') ?>" style="width: 100px;">
                </div>
            </div>
        </div>
    </div> 

    <br>
    <button type="submit" class="main-save-button">Guardar Cambios Generales</button>
</form>

<style>
@keyframes fadeInDown { from { opacity:0; transform:translateY(-30px); } to { opacity:1; transform:translateY(0); } }
@keyframes fadeOutUp   { from { opacity:1; transform:translateY(0); } to { opacity:0; transform:translateY(-30px); } }
@keyframes shake {
  0%,100%{transform:translateX(0);}20%,60%{transform:translateX(-10px);}40%,80%{transform:translateX(10px);}
}
.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);justify-content:center;align-items:center;}
.modal.mostrar{display:flex;}
.modal-contenido{background:#fff;padding:20px;border-radius:8px;width:300px;animation:fadeInDown .3s forwards;}
.modal-cerrar{animation:fadeOutUp .3s forwards;}
.shake{animation:shake .3s;}
.mensaje-error{color:red;text-align:center;margin:0 0 10px;}
.mensaje-exito{color:green;text-align:center;margin:0 0 10px;}
</style>

<div id="passwordModal" class="modal<?= $show_password_modal ? ' mostrar' : '' ?>">
  <div class="modal-contenido<?= $password_error ? ' shake' : '' ?>">
    <span style="float:right; cursor:pointer;" onclick="closePasswordModal()">&times;</span>
    <h3>Cambiar contraseña</h3>
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
      <input type="password" name="current_password" required><br><br>
      <label>Nueva contraseña:</label><br>
      <input type="password" name="new_password" required><br><br>
      <label>Confirmar contraseña:</label><br>
      <input type="password" name="confirm_password" required><br><br>
      <button type="submit">Actualizar</button>
    </form>
  </div>
</div>






<!-- Quill editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>

<script>
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
</script>

<?php require_once 'includes/footer.php'; ?>

<script>
function seleccionarFondo(nombre) {
  fetch('guardar_fondo.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'fondo=' + encodeURIComponent(nombre)
  })
  .then(response => response.text())
  .then(data => {
    alert("Respuesta del servidor: " + data);
    location.reload();
  })
  .catch(error => {
    alert("Error en fetch: " + error);
  });
}
</script>
<script>
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
