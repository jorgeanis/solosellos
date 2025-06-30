
<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    require_once 'includes/auth.php';
    require_once 'includes/db.php';
    $pdo = $pdo ?? null;

    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $whatsapp = $_POST['whatsapp'];
    $active = $_POST['active'];

    if (!empty($_POST['password'])) {
        $pass = hash('sha256', $_POST['password']);
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, whatsapp=?, active=?, password=? WHERE id=?");
        $stmt->execute([$name, $email, $whatsapp, $active, $pass, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, whatsapp=?, active=? WHERE id=?");
        $stmt->execute([$name, $email, $whatsapp, $active, $id]);
    }
    header("Location: clientes.php");
    exit;
}
?>


<?php
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    require_once 'includes/auth.php';
    require_once 'includes/db.php'; // si usás uno separado
    $pdo = $pdo ?? null;

    if ($_GET['action'] === 'get_admin') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT id, name, email, whatsapp, active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($admin ?: []);
        exit;
    }

    if ($_GET['action'] === 'cambiar_estado') {
        $id = $_GET['id'] ?? 0;
        $estado = $_GET['estado'] ?? 0;
        $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        echo json_encode(["ok" => true]);
        exit;
    }

    if ($_GET['action'] === 'eliminar_admin') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["ok" => true]);
        exit;
    }
}
?>



<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

if ($_SESSION['user']['email'] !== 'admin@solosellos.com') {
    echo "<p>No tenés permiso para ver esta página.</p>";
    require_once 'includes/footer.php';
    exit;
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    if ($_GET['action'] === 'get_admin') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("SELECT id, name, email, whatsapp, active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($admin ?: []);
        exit;
    }
    if ($_GET['action'] === 'cambiar_estado') {
        $id = $_GET['id'] ?? 0;
        $estado = $_GET['estado'] ?? 0;
        $stmt = $pdo->prepare("UPDATE users SET active = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        echo "OK";
        exit;
    }
    if ($_GET['action'] === 'eliminar_admin') {
        $id = $_GET['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo "OK";
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email != 'admin@solosellos.com' ORDER BY id DESC");
$stmt->execute();
$admins = $stmt->fetchAll();

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https://" : "http://";
$dominio = $protocolo . $_SERVER['HTTP_HOST'];
?>

<style>
    .password-wrapper {
        position: relative;
        display: inline-block;
    }
    .password-wrapper input {
        padding-right: 25px;
    }
    .password-wrapper button {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
    }
    td.estado {
        text-align: center;
    }
</style>

<h2>Gestión de Clientes (Admins)</h2>

<h3>Admins registrados</h3>
<table border="1" cellpadding="5">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Email</th>
        <th>Enlace</th>
        <th>WhatsApp</th>
        <th>Estado</th>
        <th style="white-space: nowrap;">Acciones</th>
    </tr>
    
    <?php foreach ($admins as $admin): ?>
        <tr>
            <td><?= $admin['id'] ?></td>
            <td><?= htmlspecialchars($admin['name']) ?></td>
            <td><?= htmlspecialchars($admin['email']) ?></td>
            <td><input type="text" value="<?= $dominio ?>/public/index.php?u=<?= $admin['link_code'] ?>" style="width:100%;"></td>
            <td><?= htmlspecialchars($admin['whatsapp']) ?></td>
            <td class="estado"><?= $admin['active'] ? '✅' : '❌' ?></td>
            <td style="white-space: nowrap; display:flex; gap:4px;">
                <button onclick="cambiarEstado(<?= $admin['id'] ?>, 1)" style="width:50px;">▶️</button>
                <button onclick="cambiarEstado(<?= $admin['id'] ?>, 0)" style="width:50px;">⏸️</button>
                <button onclick="editarAdmin(<?= $admin['id'] ?>)" style="width:50px;">✏️</button>
                <button onclick="eliminarAdmin(<?= $admin['id'] ?>)" style="width:50px;">🗑️</button>
            </td>
        </tr>
    <?php endforeach; ?>

</table>

<!-- Modal de edición -->
<div id="modalEditar" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:white; border:1px solid #ccc; padding:25px; width:400px; box-shadow:0 0 15px rgba(0,0,0,0.3); z-index:1000;">
    <h3>Editar Admin</h3>
    <form method="POST" action="clientes.php">
        <input type="hidden" name="id" id="edit_id">
        <label>Nombre:</label><br>
        <input type="text" name="name" id="edit_name" required><br>
        <label>Email:</label><br>
        <input type="email" name="email" id="edit_email" required><br>
        <label>WhatsApp:</label><br>
        <input type="text" name="whatsapp" id="edit_whatsapp"><br>
        <label>Nueva Contraseña:</label><br>
        <div style="position: relative;">
            <input type="password" id="password" name="password" id="password" style="padding-right: 30px;">
            <span onclick="togglePassword()" style="position: absolute; right: 8px; top: 5px; cursor: pointer;">👁️</span>
        </div><br>
        <label>Estado:</label><br>
        <select name="active" id="edit_active">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
        </select><br><br>
        <button type="submit">Guardar</button>
        <button type="button" onclick="cerrarModal()">Cancelar</button>
    </form>
</div>

<script>
function editarAdmin(id) {
    fetch('clientes.php?action=get_admin&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (!data || !data.id) return alert("Error al cargar admin.");
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.name;
            document.getElementById('edit_email').value = data.email;
            document.getElementById('edit_whatsapp').value = data.whatsapp;
            document.getElementById('edit_active').value = data.active;
            document.getElementById('modalEditar').style.display = 'block';
            document.getElementById('modalEditar').style.display = 'block';
        });
}

function cerrarModal() {
    document.getElementById('modalEditar').style.display = 'none';
}

function cambiarEstado(id, estado) {
    fetch('clientes.php?action=cambiar_estado&id=' + id + '&estado=' + estado)
        .then(() => location.reload());
}

function eliminarAdmin(id) {
    if (confirm('¿Eliminar este admin?')) {
        fetch('clientes.php?action=eliminar_admin&id=' + id)
            .then(() => location.reload());
    }
}

function togglePassword() {
    const input = document.getElementById("password");
    if (input) {
        input.type = input.type === "password" ? "text" : "password";
    }
}
</script>


<?php require_once 'includes/footer.php'; ?>

<script>
function togglePassword(button) {
    const input = button.previousElementSibling;
    input.type = input.type === "password" ? "text" : "password";
}

function togglePassword() {
    const input = document.getElementById("password");
    if (input) {
        input.type = input.type === "password" ? "text" : "password";
    }
}
</script>

