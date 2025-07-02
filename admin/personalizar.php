<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['password_change'])) {
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
                $success_message = 'Contrase\xC3\xB1a actualizada.';
            } else {
                $error_message = 'La contrase\xC3\xB1a actual no es correcta.';
            }
        } else {
            $error_message = 'Las nuevas contrase\xC3\xB1as no coinciden.';
        }
    } else {
        $color = $_POST['color_primary'];
        $email = $_POST['email'] ?? '';
        $name  = $_POST['name'] ?? '';
        $color_secundary = $_POST['color_secundary'] ?? '';
        $background_image = $_POST['background_image'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';

        $welcome = $_POST['welcome'];
        $footer  = $_POST['footer'];
        $logo    = $_SESSION['user']['logo'];

        if (!empty($_FILES["logo"]["name"])) {
            $logo = basename($_FILES["logo"]["name"]);
            move_uploaded_file($_FILES["logo"]["tmp_name"], "../assets/images/" . $logo);
        }

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, color_primary = ?, color_secundary = ?, logo = ?, footer = ?, whatsapp = ?, background_image = ?, welcome = ? WHERE id = ?");
        $stmt->execute([$name, $email, $color, $color_secundary, $logo, $footer, $whatsapp, $background_image, $welcome, $_SESSION['user']['id']]);

        $_SESSION['user']['color_primary'] = $color;
        $_SESSION['user']['logo']  = $logo;
        $_SESSION['user']['footer'] = $footer;
        $_SESSION['user']['name']   = $name;
        $_SESSION['user']['email']  = $email;
        $_SESSION['user']['color_secundary'] = $color_secundary;
        $_SESSION['user']['whatsapp'] = $whatsapp;
        $_SESSION['user']['welcome'] = $welcome;
        $_SESSION['user']['background_image'] = $background_image;
    }
}
?>

<h2>Personalización del sitio</h2>
<?php if (!empty($success_message)): ?>
    <p style="color:green;"><?= $success_message ?></p>
<?php elseif (!empty($error_message)): ?>
    <p style="color:red;"><?= $error_message ?></p>
<?php endif; ?>


<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="background_image" id="background_image" value="<?= htmlspecialchars($_SESSION['user']['background_image'] ?? '') ?>">
<div style="display: flex; gap: 40px; align-items: flex-start;">

<div style="width: 50%;">
    <label>Nombre:</label><br>
    <input type="text" name="name" value="<?= $_SESSION['user']['name'] ?>"><br><br>

    <label>Número de WhatsApp:</label><br>
    <input type="text" name="whatsapp" value="<?= $_SESSION['user']['whatsapp'] ?>"><br><br>

        
    <label>Email:</label><br>
    <input type="email" name="email" value="<?= $_SESSION['user']['email'] ?? '' ?>"><br><br>

    <button type="button" onclick="openPasswordModal()">Cambiar contraseña</button><br><br>


<div style="display: flex; gap: 40px; align-items: flex-start; margin-bottom: 15px;">
        <div>
            <label>Color primario:</label><br>
            <input type="color" name="color_primary" id="color_primary" value="<?= $_SESSION['user']['color_primary'] ?>"><br>
            <div style="width: 40px; height: 20px; background: <?= $_SESSION['user']['color_primary'] ?>; border: 1px solid #ccc; margin-top: 5px;"></div>
        </div>
        <div>
            <label>Color secundario:</label><br>
            <input type="color" name="color_secundary" id="color_secundary" value="<?= $_SESSION['user']['color_secundary'] ?>"><br>
            <div style="width: 40px; height: 20px; background: <?= $_SESSION['user']['color_secundary'] ?>; border: 1px solid #ccc; margin-top: 5px;"></div>
        </div>
    </div>
</div>
<div style="width: 50%;">
   <label>Logo actual:</label><br>
    <?php if ($_SESSION['user']['logo']): ?>
        <img src="../assets/images/<?= $_SESSION['user']['logo'] ?>" style="height:120px;"><br>
    <?php endif; ?>
    <input type="file" name="logo"><br><br>

    <div>
        <label><strong>Mensaje de bienvenida:</strong></label><br>
<textarea name="welcome" rows="4" style="width:100%; margin-bottom:20px; height:50px;"><?php echo htmlspecialchars($_SESSION['user']['welcome'] ?? '', ENT_QUOTES); ?></textarea><br><br>
</div

    <label>Texto del footer:</label><br>
<div id="editor" style="height:200px;"><?= $_SESSION['user']['footer'] ?? "" ?></div>
<textarea name="footer" id="footer" name="footer" style="display:none"><?= $_SESSION['user']['footer'] ?? "" ?></textarea><br>
</div>

</div>
<div style="margin-top: 20px;">
  <label><strong>Elegí un fondo para tu sitio:</strong></label>
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:15px;">
    <?php
      $fondos = glob('../assets/images/bg/*.jpg');
      $fondo_actual = $_SESSION['user']['background_image'] ?? '';
      foreach ($fondos as $fondo) {
    $nombre = basename($fondo);
    $checked = ($nombre === $fondo_actual) ? 'checked' : '';
    echo "<label style=\"width: 100px; height: 180px; border: 0px solid #000; border-radius: 10px; cursor: pointer; position: relative; background-size: cover; background-position: center; background-image: url('../assets/images/bg/$nombre'); display: inline-block;\">
            <input type='radio' name='bg_select' value='$nombre' style='position: absolute; top: 5px; left: 5px;' $checked>
          </label>";
}


    ?>
  </div>
</div>
<br>

<button type="submit">Guardar cambios</button>
</form>

<div id="passwordModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
  <div style="background:#fff; padding:20px; border-radius:8px; width:300px;">
    <span style="float:right; cursor:pointer;" onclick="closePasswordModal()">&times;</span>
    <h3>Cambiar contraseña</h3>
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputPrimary = document.getElementById("color_primary");
    const inputSecondary = document.getElementById("color_secundary");
    const previewPrimary = document.getElementById("preview_primary");
    const previewSecondary = document.getElementById("preview_secondary");

    if (inputPrimary && previewPrimary) {
        inputPrimary.addEventListener("input", function() {
            previewPrimary.style.backgroundColor = inputPrimary.value;
        });
    }

    if (inputSecondary && previewSecondary) {
        inputSecondary.addEventListener("input", function() {
            previewSecondary.style.backgroundColor = inputSecondary.value;
        });
    }
});
</script>


<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputPrimary = document.querySelector("input[name='color_primary']");
    const inputSecondary = document.querySelector("input[name='color_secundary']");
    const previewPrimary = document.getElementById("preview_primary");
    const previewSecondary = document.getElementById("preview_secondary");

    if (inputPrimary && previewPrimary) {
        inputPrimary.addEventListener("input", function() {
            previewPrimary.style.backgroundColor = inputPrimary.value;
        });
    }

    if (inputSecondary && previewSecondary) {
        inputSecondary.addEventListener("input", function() {
            previewSecondary.style.backgroundColor = inputSecondary.value;
        });
    }
});
</script>

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
  document.getElementById('passwordModal').style.display = 'flex';
}

function closePasswordModal() {
  document.getElementById('passwordModal').style.display = 'none';
}
</script>
