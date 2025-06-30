
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SelloSmart</title>
  <link rel="stylesheet" href="styles.css">

<style>
:root {
  --primary: #25d366;
  --bg: #f9f9f9;
  --text: #333;
  --radius: 10px;
  --shadow: 0 2px 10px rgba(0,0,0,0.1);
}

body {
  font-family: 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  margin: 0;
  padding: 0;
  -webkit-font-smoothing: antialiased;
}

.container, .step, form, .card {
  background: white;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px;
  margin: 10px auto;
  max-width: 600px;
  transition: all 0.3s ease;
}

.card {
  padding: 20px;
  animation: fadeIn 0.4s ease;
}

input, select, textarea, button {
  font-size: 1rem;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: var(--radius);
  width: 100%;
  margin-top: 8px;
  transition: border 0.3s ease;
}

input:focus, select:focus, textarea:focus {
  border-color: var(--primary);
  outline: none;
}

button {
  background-color: var(--primary);
  color: white;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

button:hover {
  background-color: #1ebe5d;
}

.tabs, .preview-grid, .model-card {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 600px) {
  .container, .step, .card {
    padding: 14px;
    margin: 8px;
  }
  input, button, select {
    font-size: 1rem;
  }
}
</style>


<style>
:root {
  --primary: #25d366;
  --bg: #f9f9f9;
  --text: #333;
  --radius: 10px;
  --shadow: 0 2px 10px rgba(0,0,0,0.08);
}

body {
  font-family: 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  margin: 0;
  padding: 0;
  -webkit-font-smoothing: antialiased;
}

.container, .step, form, .card {
  background: white;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px;
  margin: 10px auto;
  max-width: 600px;
  transition: all 0.3s ease;
}

.card {
  padding: 20px;
  animation: fadeIn 2s ease;
}

input, select, textarea {
  font-size: 1rem;
  padding: 12px;
  border: 1px solid #ccc;
  border-radius: var(--radius);
  width: 100%;
  margin-top: 12px;
  background-color: #fff;
  transition: border 0.3s ease, box-shadow 0.3s ease;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
}

input:focus, select:focus, textarea:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2);
  outline: none;
}

button {
  background-color: var(--primary);
  color: white;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
  padding: 12px 16px;
  border-radius: var(--radius);
  font-weight: bold;
}

button:hover {
  background-color: #1ebe5d;
}

.tabs, .preview-grid, .model-card {
  animation: fadeIn 0.4s ease;
}

.fadeIn {
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 600px) {
  .container, .step, .card {
    padding: 14px;
    margin: 8px;
  }
  input, button, select {
    font-size: 1rem;
  }
}
</style>

</head>
<body>


  <header>
    <nav>
      <div class="logo">🧿 SelloSmart</div>
      <ul>
        <li><a href="#inicio">Inicio</a></li>
        <li><a href="#informacion">Información</a></li>
        <li><a href="#faq">Preguntas Frecuentes</a></li>
        <li><a href="#tutoriales">Tutoriales</a></li>
        <li><a href="#contacto">Contacto</a></li>
      </ul>
    </nav>
  </header>

  <section id="inicio" class="hero">
    <h1>SelloSmart: Revoluciona tu Mundo de Sellos</h1>
    <p>Descubre la forma más moderna e intuitiva de crear sellos personalizados con calidad profesional.</p>
    <a href="#tutoriales" class="btn">Ver Tutoriales →</a>
  </section>

  <?php
$heroFolder = __DIR__ . '/assets/images/hero/';
$heroOrdenPath = $heroFolder . 'orden.json';
$heroImgs = [];
$sliderTime = 4000;

if (file_exists($heroOrdenPath)) {
  $data = json_decode(file_get_contents($heroOrdenPath), true);
  if (isset($data['orden'])) {
    $heroImgs = array_filter($data['orden'], fn($img) => file_exists($heroFolder . $img));
    $sliderTime = isset($data['tiempo']) ? intval($data['tiempo']) * 1000 : 4000;
  } elseif (is_array($data)) {
    $heroImgs = array_filter($data, fn($img) => file_exists($heroFolder . $img));
  }
}
?>
<div class="hero-slider">
  <?php foreach ($heroImgs as $img): ?>
    <div class="slide" style="background-image: url('assets/images/hero/<?= $img ?>');"></div>
  <?php endforeach; ?>
</div>

  <section id="informacion" class="info">
    <h2>La Nueva Era de la Fabricación de Sellos</h2>
    <p>SelloSmart combina tecnología de vanguardia con una interfaz amigable para ofrecerte una experiencia sin igual.</p>
    <div class="features">
      <div class="card">🎨<h3>Diseño Innovador</h3><p>Interfaz visual, editable, moderna.</p></div>



<style>
.hero-slider {
  position: relative;
  width: 100%;
  height: 280px;
  overflow: hidden;
}
.hero-slider .slide {
  position: absolute;
  top: 0; left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center;
  opacity: 0;
  transition: opacity 1s ease;
}
.hero-slider .slide.active {
  opacity: 1;
  z-index: 1;
}
@media (max-width: 600px) {
  .hero-slider {
    height: 180px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const slides = document.querySelectorAll('.hero-slider .slide');
  let index = 0;
  if (slides.length > 0) slides[0].classList.add('active');

  setInterval(() => {
    if (slides.length < 2) return;
    slides[index].classList.remove('active');
    index = (index + 1) % slides.length;
    slides[index].classList.add('active');
  }, <?= $sliderTime ?>);
});
</script>
      <div class="card">⚡<h3>Fabricación Rápida</h3><p>Listos para imprimir en minutos.</p></div>
      <div class="card">✅<h3>Resultados Profesionales</h3><p>Calidad óptica garantizada.</p></div>
      <div class="card">✅<h3>Resultados Profesionales</h3><p>Calidad óptica garantizada.</p></div>
      <div class="card">✅<h3>Resultados Profesionales</h3><p>Calidad óptica garantizada.</p></div>
      <div class="card">✅<h3>Resultados Profesionales</h3><p>Calidad óptica garantizada.</p></div>
      </section>

  <section id="faq" class="faq">
    <h2>Preguntas Frecuentes</h2>
    <details><summary>¿Cuánto cuesta usar SelloSmart?</summary><p>Podés usarlo gratis durante 15 días.</p></details>
    <details><summary>¿Necesito conocimientos técnicos?</summary><p>No, está diseñado para cualquier persona.</p></details>
    <details><summary>¿Se adapta a celulares?</summary><p>Sí, está optimizado para dispositivos móviles.</p></details>
  </section>

  <section id="tutoriales" class="tutoriales">
    <h2>Tutoriales</h2>
    <div class="videos">
      <div class="video-card"><iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="Tutorial" frameborder="0" allowfullscreen></iframe></div>
    </div>
  </section>

  <section id="contacto" class="contacto">
    <h2>Contacto</h2>
    <form action="contacto.php" method="POST">
      <input type="text" name="nombre" placeholder="Tu nombre" required>
      <input type="email" name="email" placeholder="Tu correo" required>
      <textarea name="mensaje" placeholder="Tu mensaje" required></textarea>
      <button type="submit">Enviar Mensaje</button>
    </form>
  </section>

  <footer>
    <p>&copy; 2025 SelloSmart. Todos los derechos reservados.</p>
  </footer>

  <script src="scripts.js"></script>
</body>
</html>
