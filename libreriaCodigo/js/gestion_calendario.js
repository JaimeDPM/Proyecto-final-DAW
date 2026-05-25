/**
 * gestion-calendario.js
 * Calendario, eventos, próximos 15 días
 */

let calFecha       = new Date();
let calEventos     = [];
let eventoEditando = null;
let listadoCotos   = [];
let proximosEventos = [];

const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const DIAS  = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
const ICONOS_TEMPORADA = ['🦌','🐗','🐺','🦅','🐇','🔫','🌿','❄️','🍂','🌱','🏕️','📅','⭐','🎯','🔔'];

function mesStr() {
  const y = calFecha.getFullYear();
  const m = String(calFecha.getMonth() + 1).padStart(2, '0');
  return `${y}-${m}`;
}

function cambiarMes(delta) {
  calFecha.setMonth(calFecha.getMonth() + delta);
  renderCalendario();
}

async function cargarProximos() {
  try {
    const j = await fetch(`${API}eventos.php?proximos=1`).then(r => r.json());
    const card  = document.getElementById('cardProximos');
    const lista = document.getElementById('proximosLista');
    if (!j.ok || !j.data.length) { card.classList.remove('visible'); return; }

    proximosEventos = j.data;
    const ICONOS_TIPO = { caceria: '🦌', tramite: '📋', precinto: '🔒', temporada: '📅' };

    lista.innerHTML = j.data.map((e, i) => {
      const fecha    = new Date(e.fecha_inicio + 'T00:00:00');
      const fechaFmt = fecha.toLocaleDateString('es-ES', { weekday:'short', day:'numeric', month:'short' });
      const fechaFin = e.fecha_fin ? ` → ${new Date(e.fecha_fin + 'T00:00:00').toLocaleDateString('es-ES', { day:'numeric', month:'short' })}` : '';
      const icono    = e.tipo === 'temporada' && e.icono ? e.icono : (ICONOS_TIPO[e.tipo] || '📅');
      const coto     = e.coto_matricula ? `<span class="proximo-coto">${e.coto_matricula}</span>` : '';
      const recur    = e.recurrente     ? `<span class="proximo-recurrente">🔁 anual</span>`      : '';
      return `<div class="proximo-item ${e.tipo}">
        <span class="proximo-icono">${icono}</span>
        <span class="proximo-fecha">${fechaFmt}${fechaFin}</span>
        <span class="proximo-titulo">${e.titulo}</span>
        ${coto}${recur}
      </div>`;
    }).join('');
    card.classList.add('visible');
  } catch { /* silencioso */ }
}

function abrirEventoProximo(i) {
  const ev = proximosEventos[i];
  if (ev) abrirModalEditar(ev, ev.id);
}

function irAFecha() {
  const mes  = parseInt(document.getElementById('calSelMes').value);
  const anyo = parseInt(document.getElementById('calSelAnyo').value);
  calFecha.setFullYear(anyo);
  calFecha.setMonth(mes);
  renderCalendario();
}

async function renderCalendario() {
  const label = document.getElementById('calMesLabel');
  if (label) label.textContent = MESES[calFecha.getMonth()] + ' ' + calFecha.getFullYear();
  // Sincronizar selectores
  const selMes  = document.getElementById('calSelMes');
  const selAnyo = document.getElementById('calSelAnyo');
  if (selMes)  selMes.value  = calFecha.getMonth();
  if (selAnyo) selAnyo.value = calFecha.getFullYear();
  try {
    const j = await fetch(`${API}eventos.php?mes=${mesStr()}`).then(r => r.json());
    calEventos = j.ok ? j.data : [];
  } catch { calEventos = []; }
  dibujarCalendario();
}

function abrirDia(event, fechaStr) {
  event.stopPropagation();
  const evsDia = calEventos.filter(e => {
    if (e.fecha_fin) return fechaStr >= e.fecha_inicio && fechaStr <= e.fecha_fin;
    return e.fecha_inicio === fechaStr;
  }).filter(e => e.tipo !== 'temporada');

  const [anyo, mes, dia] = fechaStr.split('-');
  const fechaFmt = `${dia}/${mes}/${anyo}`;
  const ICONOS_TIPO = { caceria: '🦌', tramite: '📋', precinto: '🔒' };

  document.getElementById('modalDiaFecha').textContent = fechaFmt;
  document.getElementById('modalDiaLista').innerHTML = evsDia.map(e => {
    const icono = ICONOS_TIPO[e.tipo] || '📅';
    const recur = e.recurrente ? ' 🔁' : '';
    const coto  = e.coto_matricula
      ? `<span class="proximo-coto">${e.coto_matricula}</span>`
      : `<span class="proximo-coto">Todos</span>`;
    return `<div class="proximo-item ${e.tipo}" onclick="abrirModalEditar(event,${e.id});cerrarModal2('modalDia')" style="cursor:pointer">
      <span class="proximo-icono">${icono}</span>
      <span class="proximo-titulo">${e.titulo}${recur}</span>
      ${coto}
    </div>`;
  }).join('');

  document.getElementById('modalDia').classList.add('open');
}

function dibujarCalendario() {
  const grid   = document.getElementById('calGrid');
  const anyo   = calFecha.getFullYear();
  const mes    = calFecha.getMonth();
  const hoy    = new Date();
  const hoyStr = `${hoy.getFullYear()}-${String(hoy.getMonth()+1).padStart(2,'0')}-${String(hoy.getDate()).padStart(2,'0')}`;
  let html = DIAS.map(d => `<div class="cal-dow">${d}</div>`).join('');
  const primerDia = new Date(anyo, mes, 1);
  let offsetDow = primerDia.getDay() - 1;
  if (offsetDow < 0) offsetDow = 6;
  for (let i = 0; i < offsetDow; i++) html += `<div class="cal-day vacio"></div>`;
  const diasMes = new Date(anyo, mes + 1, 0).getDate();
  for (let d = 1; d <= diasMes; d++) {
    const fechaStr = `${anyo}-${String(mes+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const esHoy    = fechaStr === hoyStr;
    const evsDia   = calEventos.filter(e => {
      if (e.fecha_fin) return fechaStr >= e.fecha_inicio && fechaStr <= e.fecha_fin;
      return e.fecha_inicio === fechaStr;
    });
    const temporadas = evsDia.filter(e => e.tipo === 'temporada');
    const normales   = evsDia.filter(e => e.tipo !== 'temporada');
    const iconosHtml = temporadas.length
      ? `<div style="display:flex;flex-wrap:wrap;gap:1px;margin-bottom:3px">${temporadas.map(e => `<span onclick="abrirModalEditar(event,${e.id})" title="${e.titulo}" style="cursor:pointer;font-size:16px;line-height:1">${e.icono || '📅'}</span>`).join('')}</div>`
      : '';
    const evHtml = normales.slice(0, 2).map(e => {
      const label = e.coto_matricula ? `${e.titulo} (${e.coto_matricula})` : e.titulo;
      const recur = e.recurrente ? '🔁 ' : '';
      return `<div class="cal-evento ${e.tipo}" onclick="abrirModalEditar(event,${e.id})" title="${label}">${recur}${label}</div>`;
    }).join('');
    const masHtml = normales.length > 2
      ? `<div class="cal-mas" onclick="abrirDia(event,'${fechaStr}')">+${normales.length - 2} más</div>`
      : '';
    html += `<div class="cal-day${esHoy?' hoy':''}" onclick="abrirModalNuevoFecha('${fechaStr}')">
      <div class="cal-num">${d}</div>${iconosHtml}${evHtml}${masHtml}</div>`;
  }
  grid.innerHTML = html;
  actualizarLeyenda();
}

async function cargarCotos() {
  if (listadoCotos.length) return;
  try {
    const j = await fetch(`${API}buscar.php?tipo=cotos&q=`).then(r => r.json()).catch(()=>({ok:false}));
    listadoCotos = j.ok ? j.data : [];
  } catch { listadoCotos = []; }
  const sel = document.getElementById('evCoto');
  sel.innerHTML = '<option value="">— General (todos) —</option>';
  listadoCotos.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = `${c.num_matricula} — ${c.municipio}`;
    sel.appendChild(opt);
  });
}

function actualizarLeyenda() {
  const temporadas = [...new Set(calEventos.filter(e => e.tipo === 'temporada').map(e => JSON.stringify({icono: e.icono, titulo: e.titulo})))].map(s => JSON.parse(s));
  const leyenda = document.getElementById('calLeyenda');
  const baseItems = `
    <div class="cal-leyenda-item"><div class="cal-leyenda-dot" style="background:var(--caceria-bg);border:1px solid var(--caceria)"></div> Cacería / Jornada</div>
    <div class="cal-leyenda-item"><div class="cal-leyenda-dot" style="background:var(--tramite-bg);border:1px solid var(--tramite)"></div> Trámite administrativo</div>
    <div class="cal-leyenda-item"><div class="cal-leyenda-dot" style="background:var(--precinto-bg);border:1px solid var(--precinto)"></div> Precintos</div>
  `;
  const tempItems = temporadas.map(t => `<div class="cal-leyenda-item"><span style="font-size:16px">${t.icono || '📅'}</span> ${t.titulo}</div>`).join('');
  leyenda.innerHTML = baseItems + tempItems;
}

function onTipoEvento() {
  const tipo = document.getElementById('evTipo').value;
  const bloque = document.getElementById('bloqueIcono');
  bloque.style.display = tipo === 'temporada' ? '' : 'none';
  if (tipo === 'temporada' && !document.getElementById('iconoGrid').childElementCount) {
    renderIconoGrid(null);
  }
}

function renderIconoGrid(seleccionado) {
  const grid = document.getElementById('iconoGrid');
  grid.innerHTML = ICONOS_TEMPORADA.map(ic => `
    <div onclick="seleccionarIcono('${ic}')" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;font-size:20px;border:2px solid ${ic === seleccionado ? 'var(--primary)' : 'transparent'};background:${ic === seleccionado ? '#e8f0fe' : '#f4f5f7'}" data-ic="${ic}">${ic}</div>
  `).join('');
  document.getElementById('evIcono').value = seleccionado || '';
}

function seleccionarIcono(ic) {
  document.getElementById('evIcono').value = ic;
  renderIconoGrid(ic);
}

function abrirModalNuevo() {
  eventoEditando = null; resetModal();
  document.getElementById('modalTitulo').textContent = 'Nuevo evento';
  document.getElementById('btnEliminarEvento').style.display = 'none';
  document.getElementById('modalOverlay').classList.add('open');
}

function abrirModalNuevoFecha(fecha) {
  eventoEditando = null; resetModal();
  document.getElementById('evFechaInicio').value = fecha;
  document.getElementById('modalTitulo').textContent = 'Nuevo evento';
  document.getElementById('btnEliminarEvento').style.display = 'none';
  document.getElementById('modalOverlay').classList.add('open');
}

function abrirModalEditar(e, id) {
  e.stopPropagation();
  const ev = calEventos.find(x => +x.id === +id);
  if (!ev) return;
  const esPropietario = ES_ADMIN || +ev.usuario_id === USUARIO_ID;
  eventoEditando = ev;

  if (ev.tipo === 'temporada' && !esPropietario) {
    document.getElementById('modalTitulo').textContent   = ev.titulo;
    document.getElementById('evTitulo').value            = ev.titulo;
    document.getElementById('evTipo').value              = ev.tipo;
    document.getElementById('evFechaInicio').value       = ev.fecha_inicio;
    document.getElementById('evFechaFin').value          = ev.fecha_fin || '';
    document.getElementById('evComentario').value        = ev.comentario || '';
    document.getElementById('evCoto').closest('.form-row').style.display = 'none';
    document.getElementById('evRecurrente').closest('.form-group').style.display = 'none';
    document.getElementById('bloqueIcono').style.display = 'none';
    ['evTitulo','evTipo','evFechaInicio','evFechaFin','evComentario'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.disabled = true;
    });
    document.getElementById('btnEliminarEvento').style.display = 'none';
    document.getElementById('btnGuardarEvento').style.display  = 'none';
    document.getElementById('modalOverlay').classList.add('open');
    return;
  }

  document.getElementById('modalTitulo').textContent    = esPropietario ? 'Editar evento' : 'Ver evento';
  document.getElementById('evTitulo').value             = ev.titulo;
  document.getElementById('evTipo').value               = ev.tipo;
  document.getElementById('evCoto').value               = ev.coto_id || '';
  document.getElementById('evFechaInicio').value        = ev.fecha_inicio;
  document.getElementById('evFechaFin').value           = ev.fecha_fin || '';
  document.getElementById('evComentario').value         = ev.comentario || '';
  document.getElementById('evRecurrente').checked       = !!+ev.recurrente;
  document.getElementById('evCoto').closest('.form-row').style.display = '';
  document.getElementById('evRecurrente').closest('.form-group').style.display = '';
  if (ev.tipo === 'temporada') {
    document.getElementById('bloqueIcono').style.display = '';
    renderIconoGrid(ev.icono || null);
  } else {
    document.getElementById('bloqueIcono').style.display = 'none';
  }
  ['evTitulo','evTipo','evCoto','evFechaInicio','evFechaFin','evComentario','evRecurrente'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = !esPropietario;
  });
  document.getElementById('btnEliminarEvento').style.display = esPropietario ? '' : 'none';
  document.getElementById('btnGuardarEvento').style.display  = esPropietario ? '' : 'none';
  document.getElementById('modalOverlay').classList.add('open');
}

function resetModal() {
  document.getElementById('evTitulo').value       = '';
  document.getElementById('evTipo').value         = 'caceria';
  document.getElementById('evCoto').value         = '';
  document.getElementById('evFechaInicio').value  = '';
  document.getElementById('evFechaFin').value     = '';
  document.getElementById('evComentario').value   = '';
  document.getElementById('evRecurrente').checked = false;
  document.getElementById('evIcono').value        = '';
  document.getElementById('bloqueIcono').style.display = 'none';
  document.getElementById('iconoGrid').innerHTML  = '';
  document.getElementById('evCoto').closest('.form-row').style.display = '';
  document.getElementById('evRecurrente').closest('.form-group').style.display = '';
  ['evTitulo','evTipo','evCoto','evFechaInicio','evFechaFin','evComentario','evRecurrente'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = false;
  });
  document.getElementById('btnGuardarEvento').style.display = '';
}

function cerrarModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function cerrarModalSiOverlay(e) { if (e.target === document.getElementById('modalOverlay')) cerrarModal(); }

async function guardarEvento() {
  const titulo      = document.getElementById('evTitulo').value.trim();
  const tipo        = document.getElementById('evTipo').value;
  const cotoId      = document.getElementById('evCoto').value || null;
  const fechaInicio = document.getElementById('evFechaInicio').value;
  const fechaFin    = document.getElementById('evFechaFin').value || null;
  const comentario  = document.getElementById('evComentario').value.trim() || null;
  const recurrente  = document.getElementById('evRecurrente').checked;
  const icono       = document.getElementById('evIcono').value || null;
  if (!titulo || !fechaInicio) { toast('El título y la fecha de inicio son obligatorios'); return; }
  const body = { titulo, tipo, icono, coto_id: cotoId, fecha_inicio: fechaInicio, fecha_fin: fechaFin, comentario, recurrente };
  const btn  = document.getElementById('btnGuardarEvento');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>Guardando…';
  try {
    let resp, r;
    if (eventoEditando) {
      resp = await fetch(`${API}eventos.php?id=${eventoEditando.id}`, { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
    } else {
      resp = await fetch(`${API}eventos.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
    }
    const text = await resp.text();
    try { r = JSON.parse(text); } catch { toast('Error del servidor: ' + text.substring(0, 120)); return; }
    if (!r.ok) { toast('Error: ' + (r.msg || JSON.stringify(r))); return; }
    toast(eventoEditando ? 'Evento actualizado ✓' : 'Evento creado ✓');
    cerrarModal(); renderCalendario(); cargarProximos();
  } catch(e) { toast('Error de conexión: ' + e.message); }
  finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}

async function eliminarEvento() {
  if (!eventoEditando) return;
  if (!confirm(`¿Eliminar "${eventoEditando.titulo}"?`)) return;
  try {
    const r = await fetch(`${API}eventos.php?id=${eventoEditando.id}`, { method:'DELETE' }).then(x => x.json());
    if (!r.ok) { toast('Error: ' + r.msg); return; }
    toast('Evento eliminado');
    cerrarModal(); renderCalendario(); cargarProximos();
  } catch { toast('Error de conexión'); }
}