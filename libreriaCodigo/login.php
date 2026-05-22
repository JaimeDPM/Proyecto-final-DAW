<?php
session_start();

// Si ya está logado, redirigir al panel
if (!empty($_SESSION['usuario_id'])) {
    header('Location: gestion.php');
    exit;
}

require_once __DIR__ . '/php/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        try {
            $stmt = getDB()->prepare('SELECT id, nombre, password, rol, activo, must_change_password FROM usuarios WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && $user['activo'] && password_verify($password, $user['password'])) {
                $_SESSION['usuario_id']              = $user['id'];
                $_SESSION['usuario_nombre']          = $user['nombre'];
                $_SESSION['usuario_rol']             = $user['rol'];
                $_SESSION['must_change_password']    = !empty($user['must_change_password']);
                session_regenerate_id(true);
                header('Location: gestion.php');
                exit;
            } else {
                $error = 'Email o contraseña incorrectos.';
            }
        } catch (Throwable $e) {
            $error = 'Error de conexión. Inténtalo de nuevo.';
        }
    } else {
        $error = 'Introduce email y contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acceso | CotoyCaza</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<style>
body { background: #f4f5f7; }
.login-card {
    max-width: 400px; margin: 80px auto;
    background: #fff; border-radius: 12px;
    padding: 36px; box-shadow: 0 8px 32px rgba(0,0,0,.12);
}
.login-logo { text-align: center; margin-bottom: 24px; }
.login-logo img { max-height: 60px; }
.login-logo h5 { margin-top: 10px; font-weight: 700; color: #bb9564; }
.btn-login { background: #bb9564; border: none; color: #fff; font-weight: 600; }
.btn-login:hover { background: #a07840; color: #fff; }
</style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <img src="../Imagenes/perdizBuena.png" alt="CotoyCaza">
        <h5>Panel de Gestión</h5>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" placeholder="tu@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label small fw-semibold">Contraseña</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-login w-100">Entrar</button>
    </form>
    <div class="text-center mt-3">
        <a href="../index.html" class="small text-muted">← Volver al inicio</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>