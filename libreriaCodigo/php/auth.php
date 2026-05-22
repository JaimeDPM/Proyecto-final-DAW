<?php
/**
 * auth.php
 * Incluir al inicio de cualquier página protegida.
 * Si no hay sesión activa redirige al login.
 */
session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit;
}

// Si el usuario debe cambiar su contraseña, inyectar modal bloqueante
if (!empty($_SESSION['must_change_password'])): ?>
<style>
.mcp-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.6);
  z-index: 9999; display: flex; align-items: center; justify-content: center;
}
.mcp-modal {
  background: #fff; border-radius: 12px; padding: 32px;
  width: 100%; max-width: 420px; margin: 20px;
  box-shadow: 0 20px 60px rgba(0,0,0,.3);
}
.mcp-modal h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; font-family: system-ui, sans-serif; }
.mcp-modal p  { font-size: 13px; color: #6b7280; margin-bottom: 20px; font-family: system-ui, sans-serif; }
.mcp-group { margin-bottom: 14px; }
.mcp-group label { display: block; font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; font-family: system-ui, sans-serif; }
.mcp-group input { width: 100%; padding: 9px 12px; border: 1px solid #dde1e7; border-radius: 8px; font-size: 13px; outline: none; font-family: system-ui, sans-serif; box-sizing: border-box; }
.mcp-group input:focus { border-color: #1a5ca8; }
.mcp-error { color: #dc2626; font-size: 12px; margin-bottom: 12px; display: none; font-family: system-ui, sans-serif; }
.mcp-btn { width: 100%; padding: 10px; background: #1a5ca8; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: system-ui, sans-serif; }
.mcp-btn:hover { background: #154d8f; }
.mcp-btn:disabled { opacity: .6; cursor: not-allowed; }
</style>
<div class="mcp-overlay" id="mcpOverlay">
  <div class="mcp-modal">
    <h3>🔐 Establece tu contraseña</h3>
    <p>Es tu primer acceso. Por seguridad, elige una contraseña personal antes de continuar. No podrás usar la aplicación hasta completar este paso.</p>
    <div class="mcp-group">
      <label>Nueva contraseña <span style="color:#dc2626">*</span></label>
      <input type="password" id="mcpPass1" placeholder="Mínimo 8 caracteres">
    </div>
    <div class="mcp-group">
      <label>Repetir contraseña <span style="color:#dc2626">*</span></label>
      <input type="password" id="mcpPass2" placeholder="Repite la contraseña">
    </div>
    <div class="mcp-error" id="mcpError"></div>
    <button class="mcp-btn" id="mcpBtn" onclick="mcpGuardar()">Guardar contraseña</button>
  </div>
</div>
<script>
async function mcpGuardar() {
  const p1  = document.getElementById('mcpPass1').value;
  const p2  = document.getElementById('mcpPass2').value;
  const err = document.getElementById('mcpError');
  const btn = document.getElementById('mcpBtn');
  err.style.display = 'none';
  if (p1.length < 8)  { err.textContent = 'La contraseña debe tener al menos 8 caracteres.'; err.style.display='block'; return; }
  if (p1 !== p2)      { err.textContent = 'Las contraseñas no coinciden.'; err.style.display='block'; return; }
  btn.disabled = true; btn.textContent = 'Guardando…';
  try {
    const r = await fetch('php/cambiar_password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password: p1 })
    }).then(x => x.json());
    if (r.ok) {
      document.getElementById('mcpOverlay').remove();
    } else {
      err.textContent = r.msg || 'Error al guardar.';
      err.style.display = 'block';
      btn.disabled = false; btn.textContent = 'Guardar contraseña';
    }
  } catch {
    err.textContent = 'Error de conexión.';
    err.style.display = 'block';
    btn.disabled = false; btn.textContent = 'Guardar contraseña';
  }
}
// Bloquear teclado Escape y clicks fuera
document.addEventListener('keydown', e => { if (e.key === 'Escape') e.preventDefault(); });
</script>
<?php endif;