<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto | CotoyCaza</title>
    <link rel="icon" href="Imagenes/perdizBuena.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <style>
    .nav-link {
        position: relative;
        color: #fff !important;
        transition: color 0.3s;
    }
    .nav-link::after {
        content: "";
        position: absolute;
        left: 0;
        bottom: 0.2em;
        width: 0;
        height: 3px;
        background-color: #66c938;
        border-radius: 2px;
        transition: width 0.3s, background-color 0.3s;
    }
    .nav-link:hover,
    .nav-link:focus,
    .nav-link.active {
        color: #66c938 !important;
    }
    .nav-link:hover::after,
    .nav-link:focus::after,
    .nav-link.active::after {
        width: 100%;
    }
    .navbar-nav .nav-item {
        margin-left: 1rem;
        margin-right: 1rem;
    }
    .hero-section {
        position: relative;
        min-height: calc(100vh - 56px);
        display: flex;
        align-items: center;
        justify-content: center;
        background: url('Imagenes/perdicesHome2.jpg') center center/cover no-repeat;
    }
    .hero-section::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1;
    }
    .hero-section .container {
        position: relative;
        z-index: 2;
    }
    .hero-section h1, .hero-section p {
        color: #fff;
        text-shadow: 2px 2px 8px #000;
    }
    .extra-shadow {
        box-shadow: 0 8px 24px 0 rgba(0,0,0,0.25), 0 1.5px 6px 0 rgba(102,201,56,0.15);
        transition: box-shadow 0.4s, transform 0.4s;
    }
    .extra-shadow:hover {
        box-shadow: 0 16px 32px 0 rgba(0,0,0,0.35), 0 3px 12px 0 rgba(102,201,56,0.25);
        transform: translateY(-8px) scale(1.03);
    }
    @media (max-width: 768px) {
        .hero-section {
            min-height: 60vh;
            padding: 3rem 1rem;
        }
        .hero-section h1 { font-size: 2rem; }
        .hero-section p  { font-size: 1rem; }
        .navbar.fixed-bottom .nav-link {
            font-size: 0.9rem;
            padding: 0.3rem 0.5rem;
            text-align: center;
        }
        .navbar.fixed-bottom .nav-link i {
            font-size: 1.3rem;
            display: block;
        }
    }
    .bg-success, .footer-bg {
        background-color: #b49d71 !important;
    }
    .bg-sobre-nosotros {
        background-color: #ddba90;
    }
    </style>
</head>
<body>

<?php
$enviado = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = htmlspecialchars(trim($_POST['nombre']    ?? ''));
    $email     = htmlspecialchars(trim($_POST['email']     ?? ''));
    $comentario = htmlspecialchars(trim($_POST['comentario'] ?? ''));
    $terminos  = isset($_POST['terminos']);

    if (!$nombre || !$email || !$comentario) {
        $error = 'Por favor rellena todos los campos.';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El email introducido no es válido.';
    } elseif (!$terminos) {
        $error = 'Debes aceptar los términos y condiciones.';
    } else {
        $para    = 'jaime@proaempresarial.com';
        $asunto  = '=?UTF-8?B?' . base64_encode('Nuevo mensaje de contacto - CotoyCaza') . '?=';
        $cuerpo  = "Has recibido un nuevo mensaje desde el formulario de contacto de CotoyCaza.\n\n";
        $cuerpo .= "Nombre: {$nombre}\n";
        $cuerpo .= "Email: {$email}\n\n";
        $cuerpo .= "Mensaje:\n{$comentario}\n";
        $headers  = "From: no-reply@cotoycaza.es\r\n";
        $headers .= "Reply-To: {$_POST['email']}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($para, $asunto, $cuerpo, $headers)) {
            $enviado = true;
        } else {
            $error = 'Hubo un problema al enviar el mensaje. Inténtalo de nuevo o contáctanos por teléfono.';
        }
    }
}
?>

<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success d-none d-md-flex">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="Imagenes/perdizBuena.png" alt="Logo CotoyCaza" class="img-fluid me-2" style="max-height: 50px;">
                CotoyCaza
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link text-white" href="index.html#inicio">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="index.html#servicios">Servicios</a></li>
                    <li class="nav-item"><a class="nav-link text-white" href="index.html#sobre-nosotros">Sobre Nosotros</a></li>
                    <li class="nav-item"><a class="nav-link text-white active" href="contacto.php">Contacto</a></li>
                    <li class="nav-item ms-2"><a class="btn btn-sm btn-outline-light" href="gestion/login.php"><i class="bi bi-lock-fill me-1"></i>Acceso</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <nav class="navbar navbar-dark bg-success navbar-expand fixed-bottom d-flex d-md-none">
        <div class="container justify-content-around">
            <a class="nav-link text-white" href="index.html#inicio"><i class="bi bi-house"></i><br>Inicio</a>
            <a class="nav-link text-white" href="index.html#servicios"><i class="bi bi-gear"></i><br>Servicios</a>
            <a class="nav-link text-white" href="index.html#sobre-nosotros"><i class="bi bi-people"></i><br>Nosotros</a>
            <a class="nav-link text-white active" href="contacto.php"><i class="bi bi-envelope"></i><br>Contacto</a>
            <a class="nav-link text-white" href="gestion/login.php"><i class="bi bi-lock-fill"></i><br>Acceso</a>
        </div>
    </nav>
</header>

<section class="mt-5">
  <div class="container">
    <div class="row justify-content-center" id="contacto">
      <div class="col-md-6 text-center">
        <h2 class="mb-4">📌 Contacto</h2>
        <p class="fw-light">Contáctanos para más información...</p>

        <?php if ($enviado): ?>
          <div class="alert alert-success mt-3">
            ✅ Mensaje enviado correctamente. Nos pondremos en contacto contigo pronto.
          </div>
        <?php else: ?>

          <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="nombre" name="nombre"
                     value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                     placeholder="Escribe tu nombre">
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control" id="email" name="email"
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                     placeholder="nombre@ejemplo.com">
            </div>
            <div class="mb-3">
              <label for="comentario" class="form-label">Comentario</label>
              <textarea class="form-control" id="comentario" name="comentario" rows="3"
                        placeholder="Escribe un comentario"><?= htmlspecialchars($_POST['comentario'] ?? '') ?></textarea>
            </div>
            <div class="mb-3 form-check text-start">
              <input type="checkbox" class="form-check-input" id="terminos" name="terminos"
                     <?= isset($_POST['terminos']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="terminos">Acepto los <a href="terminos.html" target="_blank">términos y condiciones</a></label>
            </div>
            <button type="submit" class="btn btn-success w-100">Enviar</button>
          </form>

        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<footer class="footer-bg text-white py-4 mt-5">
  <div class="container">
    <div class="row">
      <div class="col-12 col-md-3 mb-3">
        <h5>Sobre Nosotros</h5>
        <p class="small">Ofrecemos servicios enfocados en mejorar la gestión de tu coto de caza. Contáctanos para más información 979707015</p>
      </div>
      <div class="col-12 col-md-6 mb-3">
        <h5>Donde estamos</h5>
        <div style="height: 300px;">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2951.252349366464!2d-4.535971523713019!3d42.00971197120706!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd47b04790abe01d%3A0xc97899e390176b55!2sProa%20Empresarial%20S.L.!5e0!3m2!1ses!2ses!4v1718888888888!5m2!1ses!2ses"
            width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
      <div class="col-12 col-md-3 mb-3">
        <h5>Palencia</h5>
        <ul class="list-unstyled">
          <li><i class="bi bi-telephone-fill me-2"></i>979 70 70 15</li>
          <li><i class="bi bi-phone-fill me-2"></i>672 60 26 93</li>
          <li><i class="bi bi-geo-alt-fill me-2"></i>Calle La Cestilla 2, 3º 34001 Palencia</li>
        </ul>
      </div>
    </div>
    <hr class="my-3 text-secondary">
    <div class="text-center small">&copy; 2025 CotoyCaza. Todos los derechos reservados. | <a href="privacidad.html" class="text-white">Política de Privacidad</a> | <a href="terminos.html" class="text-white">Términos y Condiciones</a></div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>