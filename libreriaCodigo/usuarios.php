<?php
require_once 'php/auth.php';
if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Location: gestion.php');
    exit;
}
require_once 'php/config.php';

$msg   = '';
$error = '';

// ── Acciones POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Crear usuario
    if ($accion === 'crear') {
        $nombre    = trim($_POST['nombre']    ?? '');
        $email     = trim($_POST['email']     ?? '');
        $password  = trim($_POST['password']  ?? '');
        $password2 = trim($_POST['password2'] ?? '');
        $rol       = $_POST['rol'] ?? 'usuario';

        if (!$nombre || !$email || !$password || !$password2) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El email no es válido.';
        } elseif (strlen($password) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $password2) {
            $error = 'Las contraseñas no coinciden.';
        } else {
            try {
                // Comprobar nombre duplicado
                $check = getDB()->prepare("SELECT id FROM usuarios WHERE nombre = :nombre");
                $check->execute([':nombre' => $nombre]);
                if ($check->fetch()) {
                    $error = 'Ya existe un usuario con ese nombre.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
                    $stmt = getDB()->prepare("INSERT INTO usuarios (nombre, email, password, rol, must_change_password) VALUES (:nombre, :email, :password, :rol, 1)");
                    $stmt->execute([':nombre' => $nombre, ':email' => $email, ':password' => $hash, ':rol' => $rol]);
                    $msg = "Usuario <strong>{$nombre}</strong> creado correctamente.";
                }
            } catch (PDOException $e) {
                $error = $e->getCode() == 23000 ? 'Ese email ya está registrado.' : 'Error: ' . $e->getMessage();
            }
        }
    }

    // Cambiar rol
    if ($accion === 'cambiar_rol') {
        $id  = (int)($_POST['id'] ?? 0);
        $rol = $_POST['rol'] ?? 'usuario';
        if ($id && $id !== $_SESSION['usuario_id']) {
            getDB()->prepare("UPDATE usuarios SET rol = :rol WHERE id = :id")->execute([':rol' => $rol, ':id' => $id]);
            $msg = 'Rol actualizado.';
        } else {
            $error = 'No puedes cambiar tu propio rol.';
        }
    }

    // Activar / desactivar
    if ($accion === 'toggle_activo') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== $_SESSION['usuario_id']) {
            $stmt = getDB()->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $msg = 'Estado del usuario actualizado.';
        } else {
            $error = 'No puedes desactivar tu propia cuenta.';
        }
    }

    // Resetear contraseña
    if ($accion === 'resetear_password') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== $_SESSION['usuario_id']) {
            // Generar contraseña temporal aleatoria de 10 caracteres
            $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
            $tempPass = '';
            for ($i = 0; $i < 10; $i++) $tempPass .= $chars[random_int(0, strlen($chars)-1)];
            $hash = password_hash($tempPass, PASSWORD_BCRYPT, ['cost' => 10]);
            getDB()->prepare("UPDATE usuarios SET password = :password, must_change_password = 1 WHERE id = :id")
                   ->execute([':password' => $hash, ':id' => $id]);
            $msg = 'Contraseña temporal generada: <strong style="font-size:16px;letter-spacing:2px">' . htmlspecialchars($tempPass) . '</strong> — Comunícasela al usuario, deberá cambiarla al entrar.';
        } else {
            $error = 'No puedes resetear tu propia contraseña desde aquí.';
        }
    }

    // Eliminar
    if ($accion === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id && $id !== $_SESSION['usuario_id']) {
            getDB()->prepare("DELETE FROM usuarios WHERE id = :id")->execute([':id' => $id]);
            $msg = 'Usuario eliminado.';
        } else {
            $error = 'No puedes eliminarte a ti mismo.';
        }
    }
}

// ── Listar usuarios ──────────────────────────────────────────
$usuarios = getDB()->query("SELECT id, nombre, email, rol, activo, created_at FROM usuarios ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestión de usuarios | CotoyCaza</title>
<link rel="icon" href="../Imagenes/perdizBuena.png" type="image/x-icon">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
body { background: #f4f5f7; }
.topbar { background: #fff; border-bottom: 1px solid #dde1e7; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
.topbar-title { font-size: 16px; font-weight: 700; }
.topbar-links { display: flex; gap: 12px; align-items: center; font-size: 13px; }
.topbar-links a { color: #6b7280; text-decoration: none; }
.topbar-links a:hover { color: #1a5ca8; }
.contenedor { max-width: 900px; margin: 28px auto; padding: 0 20px; }
.card { border-radius: 10px; border: 1px solid #dde1e7; }
.card-header-custom { background: #bb9564; color: #fff; padding: 13px 18px; font-weight: 700; font-size: 14px; border-radius: 10px 10px 0 0; }
.badge-admin  { background: #1a5ca8; color: #fff; }
.badge-usuario { background: #e5dfc7; color: #6b7280; }
.badge-activo   { background: #e1f5ee; color: #0f6e56; }
.badge-inactivo { background: #fee2e2; color: #dc2626; }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-title">👤 Gestión de usuarios</div>
  <div class="topbar-links">
    <span>Hola, <strong><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></strong></span>
    <a href="gestion.php">← Volver al panel</a>
    <a href="logout.php" class="text-danger">Cerrar sesión</a>
  </div>
</div>

<div class="contenedor">

  <?php if ($msg): ?>
    <div class="alert alert-success mt-3"><?= $msg ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Lista de usuarios -->
  <div class="card mt-3">
    <div class="card-header-custom">Usuarios registrados (<?= count($usuarios) ?>)</div>
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:13px">
        <thead class="table-light">
          <tr>
            <th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Estado</th><th>Creado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><span class="badge bg-secondary">#<?= $u['id'] ?></span></td>
            <td><strong><?= htmlspecialchars($u['nombre']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="badge <?= $u['rol'] === 'admin' ? 'badge-admin' : 'badge-usuario' ?>">
                <?= $u['rol'] ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $u['activo'] ? 'badge-activo' : 'badge-inactivo' ?>">
                <?= $u['activo'] ? 'Activo' : 'Inactivo' ?>
              </span>
            </td>
            <td><?= substr($u['created_at'], 0, 10) ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <!-- Cambiar rol -->
                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                <form method="POST" class="d-inline">
                  <input type="hidden" name="accion" value="cambiar_rol">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <select name="rol" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()" style="font-size:11px">
                    <option value="usuario" <?= $u['rol']==='usuario'?'selected':'' ?>>usuario</option>
                    <option value="admin"   <?= $u['rol']==='admin'  ?'selected':'' ?>>admin</option>
                  </select>
                </form>
                <!-- Activar/desactivar -->
                <form method="POST" class="d-inline">
                  <input type="hidden" name="accion" value="toggle_activo">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button class="btn btn-sm <?= $u['activo'] ? 'btn-warning' : 'btn-success' ?>" style="font-size:11px">
                    <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                  </button>
                </form>
                <!-- Resetear contraseña -->
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Resetear la contraseña de <?= htmlspecialchars($u['nombre']) ?>? Se generará una contraseña temporal.')">
                  <input type="hidden" name="accion" value="resetear_password">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button class="btn btn-sm btn-secondary" style="font-size:11px">🔑 Resetear</button>
                </form>
                <!-- Eliminar -->
                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar a <?= htmlspecialchars($u['nombre']) ?>?')">
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button class="btn btn-sm btn-danger" style="font-size:11px">Eliminar</button>
                </form>
                <?php else: ?>
                  <span class="text-muted" style="font-size:11px">(tu cuenta)</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Crear nuevo usuario -->
  <div class="card mt-4 mb-5">
    <div class="card-header-custom">Crear nuevo usuario</div>
    <div class="card-body p-4">
      <form method="POST">
        <input type="hidden" name="accion" value="crear">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Contraseña <span class="text-muted">(mín. 8 caracteres)</span></label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Repetir contraseña</label>
            <input type="password" name="password2" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Rol</label>
            <select name="rol" class="form-select">
              <option value="usuario">usuario</option>
              <option value="admin">admin</option>
            </select>
          </div>
          <div class="col-12">
            <button type="submit" class="btn" style="background:#bb9564;color:#fff;font-weight:600">Crear usuario</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>