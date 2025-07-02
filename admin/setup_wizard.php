<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

$already_configured = !empty($_SESSION['user']['name']) && !empty($_SESSION['user']['color_primary']);
$force = isset($_GET['force']) && $_GET['force'] === '1';
if ($already_configured && !$force) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $email = $_POST['email'] ?? '';
    $color_primary = $_POST['color_primary'] ?? '#1abc9c';
    $color_secundary = $_POST['color_secundary'] ?? '#16a085';
    $background_image = $_POST['background_image'] ?? '';
    $welcome = $_POST['welcome'] ?? '';
    $footer = $_POST['footer'] ?? '';
    $logo = $_SESSION['user']['logo'] ?? '';

    if (!empty($_FILES['logo']['name'])) {
        $logo = basename($_FILES['logo']['name']);
        move_uploaded_file($_FILES['logo']['tmp_name'], '../assets/images/' . $logo);
    }

    $stmt = $pdo->prepare("UPDATE users SET name=?, whatsapp=?, email=?, color_primary=?, color_secundary=?, background_image=?, welcome=?, footer=?, logo=? WHERE id=?");
    $stmt->execute([$name, $whatsapp, $email, $color_primary, $color_secundary, $background_image, $welcome, $footer, $logo, $_SESSION['user']['id']]);

    $_SESSION['user'] = array_merge($_SESSION['user'], [
        'name' => $name,
        'whatsapp' => $whatsapp,
        'email' => $email,
        'color_primary' => $color_primary,
        'color_secundary' => $color_secundary,
        'background_image' => $background_image,
        'welcome' => $welcome,
        'footer' => $footer,
        'logo' => $logo
    ]);

    header('Location: dashboard.php');
    exit;
} else {
    $name = $_SESSION['user']['name'] ?? '';
    $whatsapp = $_SESSION['user']['whatsapp'] ?? '';
    $email = $_SESSION['user']['email'] ?? '';
    $color_primary = $_SESSION['user']['color_primary'] ?? '#1abc9c';
    $color_secundary = $_SESSION['user']['color_secundary'] ?? '#16a085';
    $background_image = $_SESSION['user']['background_image'] ?? '';
    $welcome = $_SESSION['user']['welcome'] ?? '';
    $footer = $_SESSION['user']['footer'] ?? '';
    $logo = $_SESSION['user']['logo'] ?? '';
}
?>
<h2>Asistente de Configuración</h2>
<form method="POST" enctype="multipart/form-data" id="wizardForm">
<div id="wizard" style="display:flex;gap:40px;">
  <div class="steps" style="width:40%;max-width:350px;">
    <div class="step active" data-step="1">
        <h3>Paso 1</h3>
        <label>Nombre de tu negocio</label>
        <input type="text" name="name" id="bizname" value="<?= htmlspecialchars($name) ?>" required>
        <label>Logo</label>
        <input type="file" name="logo" id="logoInput">
    </div>
    <div class="step" data-step="2">
        <h3>Paso 2</h3>
        <label>Número de WhatsApp</label>
        <input type="text" name="whatsapp" id="whatsapp" value="<?= htmlspecialchars($whatsapp) ?>">
        <label>Email de notificaciones</label>
        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email) ?>">
    </div>
    <div class="step" data-step="3">
        <h3>Paso 3</h3>
        <label>Color primario</label>
        <input type="color" name="color_primary" id="color_primary" value="<?= htmlspecialchars($color_primary) ?>">
        <label>Color secundario</label>
        <input type="color" name="color_secundary" id="color_secundary" value="<?= htmlspecialchars($color_secundary) ?>">
        <input type="hidden" name="background_image" id="background_image" value="<?= htmlspecialchars($background_image) ?>">
        <p>Imagen de fondo:</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,80px);gap:10px;">
        <?php
          $fondos = glob('../assets/images/bg/*.jpg');
          foreach ($fondos as $f) {
            $n = basename($f);
            $checked = $n === $background_image ? 'checked' : '';
            echo "<label style=\"width:80px;height:80px;cursor:pointer;background-size:cover;background-image:url('../assets/images/bg/$n');display:inline-block;position:relative;border:2px solid transparent;\">";
            echo "<input type='radio' name='bg_select' value='$n' style='position:absolute;top:5px;left:5px;' $checked>";
            echo "</label>";
          }
        ?>
        </div>
    </div>
    <div class="step" data-step="4">
        <h3>Paso 4</h3>
        <label>Mensaje de bienvenida</label>
        <textarea name="welcome" id="welcome" rows="2"><?= htmlspecialchars($welcome) ?></textarea>
        <label>Footer</label>
        <textarea name="footer" id="footer" rows="2"><?= htmlspecialchars($footer) ?></textarea>
    </div>
    <div style="margin-top:20px;">
      <button type="button" id="prevBtn" style="display:none;">Anterior</button>
      <button type="button" id="nextBtn">Siguiente</button>
      <button type="submit" id="finishBtn" style="display:none;">Guardar</button>
    </div>
  </div>
  <div class="phone-frame">
      <iframe id="previewFrame" src="../public/index.php?u=<?= urlencode($_SESSION['user']['link_code']) ?>" scrolling="no"></iframe>
  </div>
</div>
</form>

<style>
.step{display:none;}
.step.active{display:block;animation:fadeIn 0.3s ease;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
.phone-frame{width:360px;height:640px;max-width:100%;margin:auto;background:url('../assets/images/movil.png') no-repeat center/contain;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:20px;}
.phone-frame iframe{width:100%;height:100%;border:0;overflow:hidden;}
.phone-frame iframe::-webkit-scrollbar{display:none;}
</style>

<script>
const steps = document.querySelectorAll('.step');
let current = 0;
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const finishBtn = document.getElementById('finishBtn');
function showStep(i){
  steps[current].classList.remove('active');
  current=i;
  steps[current].classList.add('active');
  prevBtn.style.display = current===0? 'none':'inline-block';
  nextBtn.style.display = current===steps.length-1? 'none':'inline-block';
  finishBtn.style.display = current===steps.length-1? 'inline-block':'none';
}
prevBtn.addEventListener('click',()=>{ if(current>0) showStep(current-1); });
nextBtn.addEventListener('click',()=>{ if(current<steps.length-1) showStep(current+1); });
showStep(0);

const iframe = document.getElementById('previewFrame');
let previewDoc = null;
iframe.addEventListener('load', () => {
  previewDoc = iframe.contentWindow.document;
  if(previewDoc && previewDoc.body){
    previewDoc.body.style.overflow = 'hidden';
  }
  updatePreview();
});

function updatePreview(){
  if(!previewDoc) return;
  const name = document.getElementById('bizname').value || 'Tu negocio';
  const welcome = document.getElementById('welcome').value;
  const footer = document.getElementById('footer').value;
  const colorP = document.getElementById('color_primary').value;
  const colorS = document.getElementById('color_secundary').value;
  const bg = document.getElementById('background_image').value;

  const logoInput = document.getElementById('logoInput');
  const logoFile = logoInput.files[0];
  const logoSrc = logoFile ? URL.createObjectURL(logoFile) : previewDoc.querySelector('header img')?.src;

  const headerImg = previewDoc.querySelector('header img');
  if (headerImg && logoSrc) headerImg.src = logoSrc;
  const nameEl = previewDoc.querySelector('header span');
  if (nameEl) nameEl.textContent = name;

  previewDoc.documentElement.style.setProperty('--color-principal', colorP);
  previewDoc.documentElement.style.setProperty('--color-secundario', colorS);
  if (bg) previewDoc.body.style.backgroundImage = "url('../assets/images/bg/"+bg+"')";

  const welcomeEl = previewDoc.querySelector('.bienvenida div');
  if (welcomeEl) welcomeEl.textContent = welcome;
  const footerEl = previewDoc.querySelector('footer');
  if (footerEl) footerEl.innerHTML = footer;
}

document.querySelectorAll('#bizname,#welcome,#footer,#color_primary,#color_secundary').forEach(el=>el.addEventListener('input',updatePreview));
document.querySelectorAll("input[name='bg_select']").forEach(el=>el.addEventListener('change',()=>{document.getElementById('background_image').value=el.value;updatePreview();}));
document.getElementById('logoInput').addEventListener('change',updatePreview);
</script>
<?php require_once 'includes/footer.php'; ?>
