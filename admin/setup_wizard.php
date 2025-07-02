<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

if (!empty($_SESSION['user']['setup_complete'])) {
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

    $stmt = $pdo->prepare("UPDATE users SET name=?, whatsapp=?, email=?, color_primary=?, color_secundary=?, background_image=?, welcome=?, footer=?, logo=?, setup_complete=1 WHERE id=?");
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
        'logo' => $logo,
        'setup_complete' => 1
    ]);

    header('Location: dashboard.php');
    exit;
}
?>
<h2>Asistente de Configuración</h2>
<form method="POST" enctype="multipart/form-data" id="wizardForm">
<div id="wizard" style="display:flex;gap:40px;">
  <div class="steps" style="width:40%;max-width:350px;">
    <div class="step active" data-step="1">
        <h3>Paso 1</h3>
        <label>Nombre de tu negocio</label>
        <input type="text" name="name" id="bizname" required>
        <label>Logo</label>
        <input type="file" name="logo" id="logoInput">
    </div>
    <div class="step" data-step="2">
        <h3>Paso 2</h3>
        <label>Número de WhatsApp</label>
        <input type="text" name="whatsapp" id="whatsapp">
        <label>Email de notificaciones</label>
        <input type="email" name="email" id="email">
    </div>
    <div class="step" data-step="3">
        <h3>Paso 3</h3>
        <label>Color primario</label>
        <input type="color" name="color_primary" id="color_primary" value="#1abc9c">
        <label>Color secundario</label>
        <input type="color" name="color_secundary" id="color_secundary" value="#16a085">
        <input type="hidden" name="background_image" id="background_image">
        <p>Imagen de fondo:</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,80px);gap:10px;">
        <?php
          $fondos = glob('../assets/images/bg/*.jpg');
          foreach ($fondos as $f) {
            $n = basename($f);
            echo "<label style=\"width:80px;height:80px;cursor:pointer;background-size:cover;background-image:url('../assets/images/bg/$n');display:inline-block;position:relative;border:2px solid transparent;\">";
            echo "<input type='radio' name='bg_select' value='$n' style='position:absolute;top:5px;left:5px;'>";
            echo "</label>";
          }
        ?>
        </div>
    </div>
    <div class="step" data-step="4">
        <h3>Paso 4</h3>
        <label>Mensaje de bienvenida</label>
        <textarea name="welcome" id="welcome" rows="2"></textarea>
        <label>Footer</label>
        <textarea name="footer" id="footer" rows="2"></textarea>
    </div>
    <div style="margin-top:20px;">
      <button type="button" id="prevBtn" style="display:none;">Anterior</button>
      <button type="button" id="nextBtn">Siguiente</button>
      <button type="submit" id="finishBtn" style="display:none;">Guardar</button>
    </div>
  </div>
  <div id="preview" style="flex:1;border:1px solid #ccc;padding:20px;border-radius:8px;position:relative;">
      <div id="previewHeader" style="background:#1abc9c;color:#fff;padding:10px;border-radius:6px 6px 0 0;text-align:center;">
        <img id="previewLogo" src="" style="max-height:60px;display:none;margin-bottom:10px;" />
        <h3 id="previewName">Tu negocio</h3>
      </div>
      <div style="padding:15px;min-height:120px;background-size:cover;background-position:center;" id="previewBody">
        <p id="previewWelcome"></p>
      </div>
      <div id="previewFooter" style="padding:10px;background:#f5f5f5;border-radius:0 0 6px 6px;text-align:center;font-size:14px;"></div>
  </div>
</div>
</form>

<style>
.step{display:none;}
.step.active{display:block;animation:fadeIn 0.3s ease;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
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

const previewHeader = document.getElementById('previewHeader');
const previewBody = document.getElementById('previewBody');
const previewName = document.getElementById('previewName');
const previewWelcome = document.getElementById('previewWelcome');
const previewFooter = document.getElementById('previewFooter');
const previewLogo = document.getElementById('previewLogo');

document.getElementById('bizname').addEventListener('input',e=>{previewName.textContent=e.target.value||'Tu negocio';});
document.getElementById('welcome').addEventListener('input',e=>{previewWelcome.textContent=e.target.value;});
document.getElementById('footer').addEventListener('input',e=>{previewFooter.textContent=e.target.value;});
document.getElementById('color_primary').addEventListener('input',e=>{previewHeader.style.background=e.target.value;});
document.getElementById('color_secundary').addEventListener('input',e=>{previewBody.style.background=e.target.value;});
document.querySelectorAll("input[name='bg_select']").forEach(el=>{el.addEventListener('change',()=>{document.getElementById('background_image').value=el.value;previewBody.style.backgroundImage="url('../assets/images/bg/"+el.value+"')";});});

document.getElementById('logoInput').addEventListener('change',e=>{const file=e.target.files[0];if(file){previewLogo.src=URL.createObjectURL(file);previewLogo.style.display='block';}});
</script>
<?php require_once 'includes/footer.php'; ?>
