/**
 * gestion-plantillas.js
 * Sistema de plantillas Word — subir, listar, generar, eliminar
 */

const MARCADORES_AUTO = [
  'coto_matricula','coto_provincia','coto_municipio',
  'titular_nombre','titular_nif','titular_telefono','titular_email',
  'titular_direccion','titular_municipio','titular_provincia','titular_cp',
  'razon_social','pj_nif','pj_telefono','pj_email',
  'pj_direccion','pj_municipio','pj_provincia','pj_cp',
  'persona_nombre','persona_nif','persona_telefono','persona_movil',
  'persona_email','persona_direccion','persona_municipio','persona_provincia','persona_cp',
  'representante_nombre','representante_dni',
  'autorizado_nombre','autorizado_nif',
  'organizador_nombre','organizador_nif','organizador_telefono','organizador_movil',
  'organizador_email','organizador_direccion','organizador_municipio','organizador_provincia','organizador_cp',
  'fecha_hoy','fecha_hoy_larga',
];

let plantillaSeleccionada = null;

function mostrarSeccionPlantillas(mostrar) {
  document.getElementById('seccionBusqueda').style.display   = mostrar ? 'none' : '';
  document.getElementById('seccionPlantillas').style.display = mostrar ? '' : 'none';
}

async function cargarPlantillas() {
  const contenedor = document.getElementById('plantillasLista');
  contenedor.innerHTML = '<div class="empty" style="padding:36px">Cargando…</div>';
  try {
    const j = await fetch(`${API}plantillas_api.php?tipo=word`).then(r => r.json());
    if (!j.ok) { contenedor.innerHTML = `<div class="empty" style="padding:36px">Error: ${j.msg}</div>`; return; }
    if (!j.data.length) {
      contenedor.innerHTML = '<div class="empty" style="padding:36px">No hay plantillas Word disponibles aún.<br><small>El administrador puede subir plantillas con el botón "⬆ Subir plantilla".</small></div>';
      return;
    }
    const isAdmin = ES_ADMIN;
    contenedor.innerHTML = `
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:#f8f9fb">
          <th style="padding:9px 16px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #dde1e7">Nombre</th>
          ${isAdmin ? `<th style="padding:9px 16px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #dde1e7">Marcadores detectados</th>` : ''}
          <th style="padding:9px 16px;text-align:left;font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #dde1e7">Subida</th>
          <th style="padding:9px 16px;border-bottom:1px solid #dde1e7"></th>
        </tr></thead>
        <tbody>
          ${j.data.map(p => {
            const marcAuto  = (p.marcadores||[]).filter(m => MARCADORES_AUTO.includes(m));
            const marcLibre = (p.marcadores||[]).filter(m => !MARCADORES_AUTO.includes(m));
            const fecha = new Date(p.created_at).toLocaleDateString('es-ES');
            const badgesAuto  = marcAuto.map(m  => `<span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600;background:#e1f5ee;color:#0f6e56;margin:1px">${m}</span>`).join('');
            const badgesLibre = marcLibre.map(m => `<span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600;background:#fff3e0;color:#854f0b;margin:1px">${m}</span>`).join('');
            const btnEliminar = isAdmin
              ? `<button class="btn btn-danger btn-sm" onclick="eliminarPlantilla(${p.id},'${p.nombre_visible.replace(/'/g,"\\'")}')">🗑</button>`
              : '';
            return `<tr style="border-bottom:1px solid #dde1e7">
              <td style="padding:10px 16px;font-weight:600">📄 ${p.nombre_visible}</td>
              ${isAdmin ? `<td style="padding:10px 16px;line-height:1.8">${badgesAuto}${badgesLibre || (marcAuto.length ? '' : '<span style="color:#6b7280;font-size:12px">Sin marcadores detectados</span>')}</td>` : ''}
              <td style="padding:10px 16px;color:#6b7280;font-size:12px">${fecha}</td>
              <td style="padding:10px 16px;white-space:nowrap;text-align:right">
                <button class="btn btn-amber btn-sm" onclick="abrirModalGenerar(${p.id})">✍ Usar</button>
                ${btnEliminar}
              </td>
            </tr>`;
          }).join('')}
        </tbody>
      </table>
      ${isAdmin ? `<div style="padding:10px 16px;font-size:11px;color:#6b7280">
        <span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600;background:#e1f5ee;color:#0f6e56;margin:1px">campo</span> = se rellena automáticamente desde BD &nbsp;|&nbsp;
        <span style="display:inline-block;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600;background:#fff3e0;color:#854f0b;margin:1px">campo</span> = lo rellenas tú al generar
      </div>` : ''}`;
  } catch { contenedor.innerHTML = `<div class="empty" style="padding:36px">Error de conexión</div>`; }
}

function abrirModalSubirPlantilla() {
  document.getElementById('spNombre').value = '';
  document.getElementById('spArchivo').value = '';
  document.getElementById('spError').style.display = 'none';
  document.getElementById('modalSubirPlantilla').classList.add('open');
}

async function guardarPlantilla() {
  const nombre  = document.getElementById('spNombre').value.trim();
  const archivo = document.getElementById('spArchivo').files[0];
  const errEl   = document.getElementById('spError');
  const btn     = document.getElementById('btnGuardarPlantilla');
  errEl.style.display = 'none';
  if (!nombre)  { errEl.textContent = 'El nombre es obligatorio'; errEl.style.display='block'; return; }
  if (!archivo) { errEl.textContent = 'Selecciona un archivo .docx'; errEl.style.display='block'; return; }
  const ext = archivo.name.split('.').pop().toLowerCase();
  if (ext !== 'docx') { errEl.textContent = 'Solo se admiten archivos .docx'; errEl.style.display='block'; return; }
  const fd = new FormData();
  fd.append('nombre_visible', nombre);
  fd.append('tipo', 'word');
  fd.append('archivo', archivo);
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>Subiendo…';
  try {
    const j = await fetch(`${API}plantillas_api.php`, { method:'POST', body: fd }).then(r => r.json());
    if (!j.ok) { errEl.textContent = j.msg; errEl.style.display='block'; return; }
    toast('Plantilla subida correctamente ✓');
    cerrarModal2('modalSubirPlantilla');
    cargarPlantillas();
  } catch { errEl.textContent = 'Error de conexión'; errEl.style.display='block'; }
  finally { btn.disabled = false; btn.textContent = 'Subir'; }
}

async function abrirModalGenerar(plantillaId) {
  const j = await fetch(`${API}plantillas_api.php?tipo=word`).then(r => r.json()).catch(()=>({ok:false}));
  if (!j.ok) { toast('Error al cargar la plantilla'); return; }
  plantillaSeleccionada = j.data.find(p => p.id === plantillaId);
  if (!plantillaSeleccionada) { toast('Plantilla no encontrada'); return; }
  document.getElementById('genNombrePlantilla').textContent = plantillaSeleccionada.nombre_visible;
  document.getElementById('genCoto').value     = '';
  document.getElementById('genPersona').value  = '';
  document.getElementById('genCamposLibres').innerHTML = '';
  document.getElementById('genError').style.display   = 'none';

  await cargarSelectCotos('genCoto');
  await cargarSelectPersonas('genPersona');

  const necesitaOrganizador = (plantillaSeleccionada.marcadores || [])
    .some(m => m.startsWith('organizador_'));
  const bloqueOrg = document.getElementById('genBloqueOrganizador');
  if (necesitaOrganizador) {
    bloqueOrg.style.display = '';
    document.getElementById('genOrganizador').value = '';
    await cargarSelectPersonas('genOrganizador');
  } else {
    bloqueOrg.style.display = 'none';
  }

  const libres = (plantillaSeleccionada.marcadores || [])
    .filter(m => !MARCADORES_AUTO.includes(m))
    .sort((a, b) => {
      const oa = (typeof CAMPOS_CONFIG !== 'undefined' && CAMPOS_CONFIG[a]?.orden != null) ? CAMPOS_CONFIG[a].orden : 999;
      const ob = (typeof CAMPOS_CONFIG !== 'undefined' && CAMPOS_CONFIG[b]?.orden != null) ? CAMPOS_CONFIG[b].orden : 999;
      return oa - ob;
    });
  const cont = document.getElementById('genCamposLibres');
  if (libres.length) {
    cont.innerHTML = `<div style="font-size:11px;font-weight:700;color:#854f0b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:10px">Campos a rellenar manualmente</div>` +
      libres.map(m => {
        const cfg = (typeof CAMPOS_CONFIG !== 'undefined' && CAMPOS_CONFIG[m]) ? CAMPOS_CONFIG[m] : { tipo: 'texto' };
        const label = m.replace(/_/g,' ');
        if (cfg.tipo === 'multiselect') {
          return `<div class="form-group">
            <label>${label} <span style="font-weight:400;color:#6b7280;text-transform:none;letter-spacing:0">(selecciona una o varias)</span></label>
            <div id="libre_${m}" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:4px">
              ${cfg.opciones.map(o => `
                <label style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:500;color:#374151;text-transform:none;letter-spacing:0;cursor:pointer;background:#f4f5f7;padding:4px 10px;border-radius:20px;border:1px solid #dde1e7">
                  <input type="checkbox" value="${o}" style="width:auto;border:none;padding:0"> ${o}
                </label>`).join('')}
            </div>
          </div>`;
        } else if (cfg.tipo === 'select') {
          return `<div class="form-group">
            <label>${label}</label>
            <select id="libre_${m}">
              <option value="">— Selecciona —</option>
              ${cfg.opciones.map(o => `<option value="${o}">${o}</option>`).join('')}
            </select>
          </div>`;
        } else if (cfg.tipo === 'fecha') {
          return `<div class="form-group">
            <label>${label}</label>
            <input type="date" id="libre_${m}">
          </div>`;
        } else {
          return `<div class="form-group">
            <label>${label}</label>
            <input type="text" id="libre_${m}" placeholder="${cfg.placeholder || '{{' + m + '}}'}" maxlength="${cfg.maxlength || 200}" autocomplete="off">
          </div>`;
        }
      }).join('');
  } else {
    cont.innerHTML = '<p style="font-size:12px;color:#6b7280;margin-bottom:0">✅ Todos los campos se rellenan automáticamente desde la BD.</p>';
  }
  document.getElementById('modalGenerar').classList.add('open');
}

async function cargarSelectCotos(selectId) {
  const sel = document.getElementById(selectId);
  if (sel.options.length > 1) return;
  sel.innerHTML = '<option value="">— Selecciona un coto —</option>';
  try {
    const j = await fetch(`${API}buscar.php?tipo=cotos&q=`).then(r => r.json());
    if (j.ok) j.data.forEach(c => {
      const o = document.createElement('option');
      o.value = c.id;
      o.textContent = `${c.num_matricula} — ${c.municipio||''} (${c.titular||''})`;
      sel.appendChild(o);
    });
  } catch {}
}

async function cargarSelectPersonas(selectId) {
  const sel = document.getElementById(selectId);
  if (sel.options.length > 1) return;
  sel.innerHTML = '<option value="">— Selecciona una persona —</option>';
  try {
    const j = await fetch(`${API}buscar.php?tipo=personas&q=`).then(r => r.json());
    if (j.ok) j.data.forEach(p => {
      const o = document.createElement('option');
      o.value = p.id;
      o.textContent = `${p.nombre} ${p.apellido1} ${p.apellido2||''} — ${p.dni_nif}`;
      sel.appendChild(o);
    });
  } catch {}
}

async function generarDocumento() {
  const cotoId        = document.getElementById('genCoto').value;
  const personaId     = document.getElementById('genPersona').value;
  const organizadorId = document.getElementById('genOrganizador')?.value || '';
  const errEl     = document.getElementById('genError');
  const btn       = document.getElementById('btnGenerarDoc');
  errEl.style.display = 'none';
  if (!cotoId && !personaId) {
    errEl.textContent = 'Selecciona al menos un coto o una persona';
    errEl.style.display = 'block'; return;
  }
  const libres = (plantillaSeleccionada.marcadores || []).filter(m => !MARCADORES_AUTO.includes(m));
  const camposLibres = {};
  for (const m of libres) {
    const el  = document.getElementById(`libre_${m}`);
    const cfg = (typeof CAMPOS_CONFIG !== 'undefined' && CAMPOS_CONFIG[m]) ? CAMPOS_CONFIG[m] : {};
    let valor = '';
    if (cfg.tipo === 'multiselect') {
      const seleccionados = Array.from(el?.querySelectorAll('input[type=checkbox]:checked') || []).map(cb => cb.value);
      valor = seleccionados.join(' y ');
    } else {
      valor = el?.value?.trim() || '';
      if (cfg.tipo === 'fecha' && valor) {
        const [y, mo, d] = valor.split('-');
        valor = `${d}/${mo}/${y}`;
      }
    }
    camposLibres[m] = valor;
  }
  const body = {
    plantilla_id:   plantillaSeleccionada.id,
    coto_id:        cotoId        ? parseInt(cotoId)        : 0,
    persona_id:     personaId     ? parseInt(personaId)     : 0,
    organizador_id: organizadorId ? parseInt(organizadorId) : 0,
    campos_libres:  camposLibres,
  };
  btn.disabled = true; btn.innerHTML = '<span class="spinner"></span>Generando…';
  try {
    const r = await fetch(`${API}generar_plantilla.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    if (!r.ok) {
      const e = await r.json().catch(() => ({ msg: r.statusText }));
      errEl.textContent = 'Error: ' + (e.msg || 'desconocido');
      errEl.style.display = 'block'; return;
    }
    const blob = await r.blob();
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = plantillaSeleccionada.nombre_visible.replace(/[^a-zA-Z0-9_\- ]/g,'_') + '_' + new Date().toISOString().slice(0,10) + '.docx';
    a.click();
    URL.revokeObjectURL(a.href);
    toast('Documento generado y descargado ✓');
    cerrarModal2('modalGenerar');
  } catch { errEl.textContent = 'Error de conexión'; errEl.style.display='block'; }
  finally { btn.disabled = false; btn.textContent = 'Generar y descargar'; }
}

async function eliminarPlantilla(id, nombre) {
  if (!confirm(`¿Eliminar la plantilla "${nombre}"?\nEsta acción no se puede deshacer.`)) return;
  try {
    const r = await fetch(`${API}plantillas_api.php?id=${id}`, { method:'DELETE' }).then(x => x.json());
    if (!r.ok) { toast('Error: ' + r.msg); return; }
    toast('Plantilla eliminada ✓');
    cargarPlantillas();
  } catch { toast('Error de conexión'); }
}