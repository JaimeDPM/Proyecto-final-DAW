<?php
session_start();
if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header('Location: gestion.php');
    exit;
}
require_once __DIR__ . '/php/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Papelera | Gestión Cinegética</title>
<link rel="icon" href="../Imagenes/perdizBuena.png" type="image/x-icon">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
  --bg: #f4f5f7; --surface: #fff; --border: #dde1e7;
  --primary: #1a5ca8; --text: #1c1e21; --muted: #6b7280;
  --danger: #dc2626; --success: #0f6e56; --radius: 10px;
}
body { font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); }
header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
.titulo { font-size: 17px; font-weight: 600; }
.subtitulo { font-size: 12px; color: var(--muted); margin-top: 2px; }
.contenedor { max-width: 1020px; margin: 28px auto; padding: 0 20px; display: grid; gap: 20px; }
.tabs { display: flex; gap: 4px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 6px; }
.tab { flex: 1; padding: 9px 12px; border: none; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; background: transparent; color: var(--muted); transition: all .15s; }
.tab.activo { background: var(--danger); color: #fff; }
.tab:hover:not(.activo) { background: #fee2e2; color: var(--danger); }
.card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
.card-header { padding: 13px 18px; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; justify-content: space-between; }
.card-header-left { display: flex; align-items: center; gap: 8px; }
.ibadge { width: 26px; height: 26px; background: #fee2e2; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 13px; }
.table-wrap { overflow-x: auto; max-height: 500px; overflow-y: auto; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
thead tr { background: #f8f9fb; position: sticky; top: 0; z-index: 1; }
th, td { padding: 9px 13px; text-align: left; border-bottom: 1px solid var(--border); }
th { font-weight: 600; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
.empty { padding: 36px 18px; text-align: center; color: var(--muted); font-size: 13px; }
.badge { display: inline-block; padding: 2px 7px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #e8f0fe; color: var(--primary); }
.badge-red { background: #fee2e2; color: var(--danger); }
.badge-user { background: #f0fdf4; color: #166534; }
.acciones-fila { display: flex; gap: 6px; }
.btn { padding: 6px 14px; border: none; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; transition: filter .15s; white-space: nowrap; }
.btn:disabled { opacity: .5; cursor: not-allowed; }
.btn-restore { background: #e1f5ee; color: var(--success); border: 1px solid #a7f3d0; }
.btn-restore:hover:not(:disabled) { filter: brightness(.95); }
.btn-delete { background: #fee2e2; color: var(--danger); border: 1px solid #fca5a5; }
.btn-delete:hover:not(:disabled) { filter: brightness(.95); }
.btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); padding: 7px 16px; font-size: 13px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; font-weight: 600; }
.btn-ghost:hover { background: var(--bg); }
.fecha-borrado { font-size: 11px; color: var(--muted); }
.info-banner { background: #fff3e0; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px 14px; font-size: 13px; color: #854f0b; }
#toast { position: fixed; bottom: 22px; right: 22px; background: #1c1e21; color: #fff; padding: 11px 18px; border-radius: 8px; font-size: 13px; opacity: 0; transform: translateY(8px); transition: opacity .25s, transform .25s; pointer-events: none; z-index: 99; }
#toast.show { opacity: 1; transform: translateY(0); }
.spinner { display: inline-block; width: 13px; height: 13px; border: 2px solid rgba(255,255,255,.35); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; vertical-align: middle; margin-right: 5px; }
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<header>
  <div>
    <div class="titulo">🗑 Papelera</div>
    <div class="subtitulo">Registros borrados — solo visible para administradores</div>
  </div>
  <a href="gestion.php" class="btn-ghost">← Volver a gestión</a>
</header>

<div class="contenedor">

  <div class="info-banner">
    ⚠️ Los registros aquí listados han sido borrados por los usuarios. Puedes <strong>restaurarlos</strong> para que vuelvan a aparecer, o <strong>eliminarlos definitivamente</strong> si ya no son necesarios.
  </div>

  <div class="tabs">
    <button class="tab activo" onclick="cambiarTab('persona')">👤 Personas</button>
    <button class="tab" onclick="cambiarTab('coto')">🌲 Cotos</button>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-header-left">
        <div class="ibadge">🗑</div>
        <span id="lblTitulo">Personas borradas</span>
      </div>
      <span id="lblTotal" style="font-size:12px;color:var(--muted)"></span>
    </div>
    <div class="table-wrap">
      <table>
        <thead id="thead"></thead>
        <tbody id="tbody"><tr><td colspan="10"><div class="empty">Cargando…</div></td></tr></tbody>
      </table>
    </div>
  </div>

</div>

<div id="toast"></div>

<script>
const API = 'php/';
let tabActual = 'persona';

const config = {
  persona: { label: 'Personas borradas', cols: ['ID','Nombre','Apellido 1','DNI/NIF','Usuario','Borrado el','Acciones'] },
  coto:    { label: 'Cotos borrados',    cols: ['ID','Matrícula','Municipio','Titular','Usuario','Borrado el','Acciones'] },
};

function toast(msg, ms = 3200) {
  const el = document.getElementById('toast');
  el.textContent = msg; el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), ms);
}
const fmt = v => (v == null || v === '') ? '—' : v;

function cambiarTab(tab) {
  tabActual = tab;
  document.querySelectorAll('.tab').forEach((t, i) => {
    t.classList.toggle('activo', Object.keys(config)[i] === tab);
  });
  cargar();
}

async function cargar() {
  const cfg = config[tabActual];
  document.getElementById('lblTitulo').textContent = cfg.label;
  document.getElementById('lblTotal').textContent  = '';
  document.getElementById('thead').innerHTML = '<tr>' + cfg.cols.map(c => `<th>${c}</th>`).join('') + '</tr>';
  document.getElementById('tbody').innerHTML = `<tr><td colspan="${cfg.cols.length}"><div class="empty">Cargando…</div></td></tr>`;

  try {
    const resp = await fetch(`${API}papelera_datos.php?tipo=${tabActual}`);
    const text = await resp.text();
    let j;
    try { j = JSON.parse(text); } catch { 
      document.getElementById('tbody').innerHTML = `<tr><td colspan="${cfg.cols.length}"><div class="empty">Respuesta inválida: ${text.substring(0,200)}</div></td></tr>`;
      return;
    }
    if (!j.ok) { document.getElementById('tbody').innerHTML = `<tr><td colspan="${cfg.cols.length}"><div class="empty">Error: ${j.msg}</div></td></tr>`; return; }

    document.getElementById('lblTotal').textContent = `${j.data.length} registro${j.data.length !== 1 ? 's' : ''}`;

    if (!j.data.length) {
      document.getElementById('tbody').innerHTML = `<tr><td colspan="${cfg.cols.length}"><div class="empty">No hay registros borrados en esta categoría ✓</div></td></tr>`;
      return;
    }

    document.getElementById('tbody').innerHTML = j.data.map(r => renderFila(r)).join('');
  } catch(e) {
    document.getElementById('tbody').innerHTML = `<tr><td colspan="${cfg.cols.length}"><div class="empty">Error de conexión: ${e.message}</div></td></tr>`;
  }
}

function renderFila(r) {
  const acciones = `
    <div class="acciones-fila">
      <button class="btn btn-restore" onclick="restaurar(${r.id})">↩ Restaurar</button>
      <button class="btn btn-delete"  onclick="eliminar(${r.id})">🗑 Eliminar</button>
    </div>`;
  const fechaBorrado = `<span class="fecha-borrado">${r.deleted_at ? r.deleted_at.substring(0,16).replace('T',' ') : '—'}</span>`;
  const usuario = r.usuario_nombre ? `<span class="badge badge-user">${fmt(r.usuario_nombre)}</span>` : '<span style="color:var(--muted);font-size:12px">—</span>';

  if (tabActual === 'persona') {
    return `<tr>
      <td><span class="badge">#${r.id}</span></td>
      <td>${fmt(r.nombre)}</td>
      <td>${fmt(r.apellido1)} ${fmt(r.apellido2) !== '—' ? fmt(r.apellido2) : ''}</td>
      <td>${fmt(r.dni_nif)}</td>
      <td>${usuario}</td>
      <td>${fechaBorrado}</td>
      <td>${acciones}</td>
    </tr>`;
  } else if (tabActual === 'coto') {
    return `<tr>
      <td><span class="badge">#${r.id}</span></td>
      <td><span class="badge" style="background:#e1f5ee;color:#0f6e56">${fmt(r.letra_provincia)}-${fmt(r.numero_matricula)}</span></td>
      <td>${fmt(r.municipio)}</td>
      <td>${fmt(r.titular)}</td>
      <td>${usuario}</td>
      <td>${fechaBorrado}</td>
      <td>${acciones}</td>
    </tr>`;
  } else if (tabActual === 'declaracion') {
    return `<tr>
      <td><span class="badge">#${r.id}</span></td>
      <td>${fmt(r.plantilla_pdf)}</td>
      <td>${usuario}</td>
      <td>${fechaBorrado}</td>
      <td>${acciones}</td>
    </tr>`;
  } else if (tabActual === 'documento') {
    return `<tr>
      <td><span class="badge">#${r.id}</span></td>
      <td>${fmt(r.plantilla)}</td>
      <td>${usuario}</td>
      <td>${fechaBorrado}</td>
      <td>${acciones}</td>
    </tr>`;
  } else if (tabActual === 'evento') {
    return `<tr>
      <td><span class="badge">#${r.id}</span></td>
      <td>${fmt(r.titulo)}</td>
      <td><span class="badge badge-red">${fmt(r.tipo)}</span></td>
      <td>${usuario}</td>
      <td>${fechaBorrado}</td>
      <td>${acciones}</td>
    </tr>`;
  }
  return '';
}

async function restaurar(id) {
  if (!confirm('¿Restaurar este registro? Volverá a aparecer en el sistema.')) return;
  try {
    const r = await fetch(`${API}papelera_restaurar.php?tipo=${tabActual}&id=${id}`).then(x => x.json());
    if (!r.ok) { toast('Error: ' + r.msg); return; }
    toast('Registro restaurado ✓');
    cargar();
  } catch { toast('Error de conexión'); }
}

async function eliminar(id) {
  if (!confirm('⚠️ ¿Eliminar DEFINITIVAMENTE este registro? Esta acción no se puede deshacer.')) return;
  try {
    const r = await fetch(`${API}papelera_eliminar.php?tipo=${tabActual}&id=${id}`).then(x => x.json());
    if (!r.ok) { toast('Error: ' + r.msg); return; }
    toast('Registro eliminado definitivamente');
    cargar();
  } catch { toast('Error de conexión'); }
}

// Cargar al inicio
cargar();
</script>
</body>
</html>