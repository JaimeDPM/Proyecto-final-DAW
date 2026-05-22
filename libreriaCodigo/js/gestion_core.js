/**
 * gestion-core.js
 * Variables globales, inicialización, tabs, búsqueda, tabla y ficha
 */

const API = 'php/';
let tabActual   = 'personas';
let registroSel = null;

window.addEventListener('DOMContentLoaded', () => {
  cargarCotos();
  renderCalendario();
  cargarProximos();
  cargarInicial('personas');
});

const tabConfig = {
  personas:      { label: 'Buscar persona',           placeholder: 'Nombre, apellido, DNI…' },
  cotos:         { label: 'Buscar coto',              placeholder: 'Matrícula, municipio, titular…' },
  declaraciones: { label: 'Buscar declaración Junta', placeholder: 'ID, nombre de plantilla…' },
  documentos:    { label: 'Buscar modelo',            placeholder: 'ID, nombre de plantilla…' },
};

function toast(msg, ms = 8000) {
  const el = document.getElementById('toast');
  el.textContent = msg; el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), ms);
}

const fmt = v => (v == null || v === '') ? '—' : v;

function cambiarTab(tab) {
  tabActual = tab;
  document.querySelectorAll('.tab').forEach((t, i) => {
    t.classList.toggle('activo', ['personas','cotos','declaraciones','documentos'][i] === tab);
  });

  if (tab === 'documentos') {
    mostrarSeccionPlantillas(true);
    cargarPlantillas();
    return;
  }

  mostrarSeccionPlantillas(false);
  const cfg = tabConfig[tab];
  document.getElementById('lblBuscar').textContent        = cfg.label;
  document.getElementById('q').placeholder                = cfg.placeholder;
  document.getElementById('q').value                      = '';
  document.getElementById('fichaCard').classList.remove('visible');
  registroSel = null;
  cargarInicial(tab);
}

async function cargarInicial(tab) {
  document.getElementById('cardResultados').style.display = '';
  document.getElementById('lblResultados').textContent    = 'Cargando…';
  try {
    const j = await fetch(`${API}buscar.php?tipo=${tab}&q=`).then(r => r.json());
    if (!j.ok) { document.getElementById('lblResultados').textContent = 'Error al cargar'; return; }
    document.getElementById('lblResultados').textContent = `Registros (${j.total})`;
    renderTabla(j.data);
  } catch { document.getElementById('lblResultados').textContent = 'Error de conexión'; }
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('q').addEventListener('keydown', e => { if (e.key === 'Enter') buscar(); });
});

async function buscar() {
  const q = document.getElementById('q').value.trim();
  if (q.length < 2) { toast('Introduce al menos 2 caracteres'); return; }
  const btn = document.getElementById('btnBuscar');
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>Buscando…';
  try {
    const j = await fetch(`${API}buscar.php?tipo=${tabActual}&q=${encodeURIComponent(q)}`).then(r => r.json());
    btn.disabled = false; btn.textContent = 'Buscar';
    if (!j.ok) { toast('Error: ' + j.msg); return; }
    document.getElementById('lblResultados').textContent = `Resultados (${j.total})`;
    document.getElementById('cardResultados').style.display = '';
    document.getElementById('fichaCard').classList.remove('visible');
    renderTabla(j.data);
  } catch { btn.disabled = false; btn.textContent = 'Buscar'; toast('Error de conexión'); }
}

function renderTabla(rows) {
  const thead = document.getElementById('thead');
  const tbody = document.getElementById('tbody');
  const cols  = {
    personas:      ['ID','Nombre','Apellido 1','Apellido 2','DNI/NIF','Acciones'],
    cotos:         ['ID','Matrícula','Municipio','Titular','Acciones'],
    declaraciones: ['ID','Plantilla PDF'],
    documentos:    ['ID','Plantilla Word'],
  };
  thead.innerHTML = '<tr>' + cols[tabActual].map(c => `<th>${c}</th>`).join('') + '</tr>';
  if (!rows.length) {
    tbody.innerHTML = `<tr><td colspan="${cols[tabActual].length}"><div class="empty">Sin resultados</div></td></tr>`;
    return;
  }
  const tipoEntidad = { personas:'persona', cotos:'coto', declaraciones:'declaracion', documentos:'documento' };
  const tipo = tipoEntidad[tabActual];
  tbody.innerHTML = rows.map(r => {
    let celdas = '';
    if (tabActual === 'personas') {
      const nombre = `${r.nombre} ${r.apellido1}`;
      celdas = `<td><span class="badge">#${r.id}</span></td><td>${fmt(r.nombre)}</td><td>${fmt(r.apellido1)}</td><td>${fmt(r.apellido2)}</td><td>${fmt(r.dni_nif)}</td>
        <td onclick="event.stopPropagation()" style="white-space:nowrap">
          <button class="btn btn-ghost btn-sm" onclick="editarPersona(${r.id})"><i class='bi bi-pencil'></i> Editar</button>
          <button class="btn btn-danger btn-sm" onclick="borrarRegistro('persona',${r.id},'${nombre.replace(/'/g,"\\'")}')">🗑</button>
        </td>`;
    } else if (tabActual === 'cotos') {
      celdas = `<td><span class="badge">#${r.id}</span></td><td><span class="badge-green badge">${fmt(r.num_matricula)}</span></td><td>${fmt(r.municipio)}</td><td>${fmt(r.titular)}</td>
        <td onclick="event.stopPropagation()" style="white-space:nowrap">
          <button class="btn btn-ghost btn-sm" onclick="editarCoto(${r.id})"><i class='bi bi-pencil'></i> Editar</button>
          <button class="btn btn-danger btn-sm" onclick="borrarRegistro('coto',${r.id},'${(r.num_matricula||'').replace(/'/g,"\\'")}')">🗑</button>
        </td>`;
    } else if (tabActual === 'declaraciones') {
      celdas = `<td><span class="badge">#${r.id}</span></td><td>${fmt(r.plantilla_pdf)}</td>`;
    } else {
      celdas = `<td><span class="badge">#${r.id}</span></td><td>${fmt(r.plantilla)}</td>`;
    }
    return `<tr onclick="seleccionar(${r.id},'${tipo}')" data-id="${r.id}">${celdas}</tr>`;
  }).join('');
}

async function seleccionar(id, tipo) {
  document.querySelectorAll('#tbody tr').forEach(tr => tr.classList.toggle('activa', +tr.dataset.id === id));
  try {
    const peticiones = [fetch(`${API}obtener.php?tipo=${tipo}&id=${id}`).then(r => r.json())];
    if (tipo === 'coto') peticiones.push(fetch(`${API}obtener.php?tipo=colindantes&id=${id}`).then(r => r.json()));
    const [j, jCol] = await Promise.all(peticiones);
    if (!j.ok) { toast('Error: ' + j.msg); return; }
    registroSel = { ...j.data, _tipo: tipo };
    renderFicha(j.data, tipo, jCol?.data ?? []);
    const fc = document.getElementById('fichaCard');
    fc.classList.add('visible');
    fc.scrollIntoView({ behavior: 'smooth', block: 'start' });
  } catch { toast('Error al cargar el registro'); }
}

const bloque = (titulo, campos) => `
  <div class="seccion-bloque">
    <div class="seccion-titulo">${titulo}</div>
    <div class="ficha-grid">
      ${campos.map(([l,v]) => `<div class="ficha-campo"><label>${l}</label><div class="valor">${fmt(v)}</div></div>`).join('')}
    </div>
  </div>`;

function bloqueColindantes(colindantes) {
  if (!colindantes || !colindantes.length) {
    return `<div class="seccion-bloque"><div class="seccion-titulo">Cotos colindantes</div><div class="empty" style="padding:16px 0">Sin cotos colindantes registrados</div></div>`;
  }
  const filas = colindantes.map(c => `
    <tr>
      <td>${fmt(c.provincia)}</td>
      <td><span class="badge-green badge">${fmt(c.numero_coto)}</span></td>
      <td>${c.menos_500m ? '<span class="badge-orange badge">⚠ &lt;500 m</span>' : '<span style="color:var(--muted);font-size:12px">—</span>'}</td>
      <td style="color:var(--muted);font-size:12px">${fmt(c.notas)}</td>
    </tr>`).join('');
  return `<div class="seccion-bloque"><div class="seccion-titulo">Cotos colindantes (${colindantes.length})</div>
    <div class="table-wrap"><table class="colindantes-table">
      <thead><tr><th>Provincia</th><th>Nº Coto</th><th>A &lt;500 m</th><th>Notas</th></tr></thead>
      <tbody>${filas}</tbody>
    </table></div></div>`;
}

function renderFicha(d, tipo, colindantes = []) {
  const body     = document.getElementById('fichaBody');
  const acciones = document.getElementById('fichaAcciones');
  document.getElementById('lblFicha').textContent = { persona:'Persona', coto:'Coto', declaracion:'Declaración', documento:'Documento' }[tipo];

  if (tipo === 'persona') {
    body.innerHTML = bloque('Datos personales', [
      ['DNI/NIF',d.dni_nif],['Nombre',d.nombre],['Apellido 1',d.apellido1],['Apellido 2',d.apellido2],
      ['Teléfono',d.telefono],['Móvil',d.telefonomovil],['Email',d.email],
      ['Provincia',d.provincia],['Municipio',d.municipio],['CP',d.cp],
      ['Dirección',d.direccion],['Número',d.numero],['Notas',d.notas],
    ]);
    acciones.innerHTML = '';
  } else if (tipo === 'coto') {
    const esPJ = !d.titular_id;
    body.innerHTML =
      bloque('Datos del coto', [
        ['Matrícula',(d.letra_provincia||'')+'-'+(d.numero_matricula||'')],
        ['Provincia',d.provincia],['Municipio',d.municipio],['Notas',d.notas],
      ]) +
      (esPJ
        ? bloque('Titular — persona jurídica',[['Razón social',d.razon_social],['NIF',d.pj_nif],['Teléfono',d.pj_telefono],['Móvil',d.pj_telefonomovil],['Email',d.pj_email],['Dirección',d.pj_direccion],['Municipio',d.pj_municipio],['Provincia',d.pj_provincia],['CP',d.pj_cp]])
        : bloque('Titular — persona física',[['Nombre',`${d.tit_nombre||''} ${d.tit_apellido1||''} ${d.tit_apellido2||''}`],['DNI/NIF',d.tit_nif]])
      ) +
      bloqueColindantes(colindantes);
    acciones.innerHTML = '';
  } else if (tipo === 'declaracion') {
    body.innerHTML =
      bloque('Interesado',[['Nombre',`${d.int_nombre} ${d.int_apellido1}`],['DNI',d.int_dni]]) +
      bloque('Representante',[['Nombre',d.rep_nombre?`${d.rep_nombre} ${d.rep_apellido1}`:'—'],['DNI',d.rep_dni]]) +
      bloque('Organizador',[['Nombre',`${d.org_nombre} ${d.org_apellido1}`],['DNI',d.org_dni]]) +
      bloque('Coto',[['Matrícula',d.num_matricula],['Provincia',d.coto_provincia]]) +
      bloque('Calidades',[['En calidad de',d.en_calidad_de],['Tipo entidad',d.tipo_entidad],['Género',d.genero]]);
    acciones.innerHTML = `<button class="btn btn-success" onclick="descargar('pdf',${d.id})">⬇ PDF Junta CyL</button>`;
  } else if (tipo === 'documento') {
    body.innerHTML =
      bloque('Documento',[['Plantilla',d.plantilla],['Temporada',d.temporada],['Nº petición',d.num_peticion]]) +
      bloque('Condiciones',[['Especie',d.especie],['Modalidad',d.modalidad],['Cupo',d.cupo],['Fecha inicio',d.fecha_inicio],['Fecha fin',d.fecha_fin]]) +
      bloque('Titular del coto',[['Nombre',`${d.tit_nombre} ${d.tit_apellido1}`],['NIF',d.tit_nif]]) +
      bloque('Representante',[['Nombre',d.rep_nombre?`${d.rep_nombre} ${d.rep_apellido1}`:'— (certificado digital)'],['DNI',d.rep_dni]]) +
      bloque('Autorizado',[['Nombre',`${d.aut_nombre} ${d.aut_apellido1}`],['NIF',d.aut_nif]]) +
      bloque('Coto',[['Matrícula',d.num_matricula],['Provincia',d.coto_provincia]]);
    acciones.innerHTML = `<button class="btn btn-amber" onclick="descargar('word',${d.id})">⬇ Documento Word</button>`;
  }
}

async function descargar(tipo, id) {
  const btn = event.target;
  const url = tipo === 'pdf' ? `${API}generar_pdf.php?id=${id}` : `${API}generar_word.php?id=${id}`;
  const ext = tipo === 'pdf' ? '.pdf' : '.docx';
  btn.disabled = true; btn.innerHTML = `<span class="spinner"></span>Generando…`;
  try {
    const r = await fetch(url);
    if (!r.ok) { const e = await r.json().catch(()=>({msg:r.statusText})); toast('Error: '+(e.msg||'desconocido')); return; }
    const a = document.createElement('a');
    a.href = URL.createObjectURL(await r.blob());
    a.download = `documento_${id}${ext}`;
    a.click(); URL.revokeObjectURL(a.href);
    toast('Documento generado ✓');
  } catch { toast('Error al generar el documento'); }
  finally { btn.disabled = false; btn.innerHTML = tipo === 'pdf' ? '⬇ PDF Junta CyL' : '⬇ Documento Word'; }
}
