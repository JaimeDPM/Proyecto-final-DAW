/**
 * gestion-cotos.js
 * Modal coto, colindantes, guardar
 */

// Mapa provincia → letra
const PROVINCIAS_CYL = {
  'Ávila':'AV','Burgos':'BU','León':'LE','Palencia':'P',
  'Salamanca':'SA','Segovia':'SG','Soria':'SO','Valladolid':'VA','Zamora':'ZA'
};

function sincronizarLetra() {
  const prov = document.getElementById('cProvincia').value;
  document.getElementById('cLetra').value = PROVINCIAS_CYL[prov] || '';
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('cTitularId').addEventListener('change', function() {
    document.getElementById('bloqueJuridica').style.display = this.value ? 'none' : '';
  });
});

// ── Colindantes ──────────────────────────────────────────────
const PROVINCIAS_OPTS = ['Ávila','Burgos','León','Palencia','Salamanca','Segovia','Soria','Valladolid','Zamora']
  .map(p => `<option value="${p}">${p}</option>`).join('');

function añadirColindante(c = {}) {
  const tbody = document.getElementById('filasColindantes');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select class="col-provincia" style="width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:6px;font-size:12px">
      <option value="">—</option>${PROVINCIAS_OPTS}
    </select></td>
    <td><input type="text" class="col-numero" maxlength="5" placeholder="00000" style="width:70px;padding:4px 6px;border:1px solid var(--border);border-radius:6px;font-size:12px" value="${c.numero_coto||''}"></td>
    <td style="text-align:center"><input type="checkbox" class="col-500m" ${c.menos_500m ? 'checked' : ''}></td>
    <td><input type="text" class="col-notas" style="width:100%;padding:4px 6px;border:1px solid var(--border);border-radius:6px;font-size:12px" value="${c.notas||''}"></td>
    <td><button type="button" onclick="this.closest('tr').remove()" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:15px">✕</button></td>
  `;
  tr.querySelector('.col-provincia').value = c.provincia || '';
  tbody.appendChild(tr);
}

function recogerColindantes() {
  return Array.from(document.querySelectorAll('#filasColindantes tr')).map(tr => ({
    provincia:    tr.querySelector('.col-provincia').value,
    numero_coto:  tr.querySelector('.col-numero').value.trim(),
    menos_500m:   tr.querySelector('.col-500m').checked ? 1 : 0,
    notas:        tr.querySelector('.col-notas').value.trim(),
  })).filter(c => c.provincia || c.numero_coto);
}

// ── Modal Coto ───────────────────────────────────────────────
async function abrirModalCoto(datos) {
  document.getElementById('modalCotoTitulo').textContent = datos ? 'Editar coto' : 'Nuevo coto';
  const sel = document.getElementById('cTitularId');
  if (sel.options.length <= 1) {
    const j = await fetch(`${API}buscar.php?tipo=personas&q=`).then(r => r.json()).catch(()=>({ok:false}));
    if (j.ok) j.data.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = `${p.nombre} ${p.apellido1} — ${p.dni_nif}`;
      sel.appendChild(opt);
    });
  }
  const campos = ['Id','Letra','Matricula','Provincia','Municipio','Notas'];
  const keys   = ['id','letra_provincia','numero_matricula','provincia','municipio','notas'];
  campos.forEach((c, i) => {
    const el = document.getElementById('c' + c);
    if (el) el.value = datos ? (datos[keys[i]] ?? '') : '';
  });
  sel.value = datos?.titular_id ?? '';
  const pjCampos = ['RazonSocial','PjNif','PjTelefono','PjMovil','PjEmail','PjTipovia','PjDireccion','PjNumero','PjPortal','PjEscalera','PjPiso','PjPuerta','PjMunicipio','PjProvincia','PjCp'];
  const pjKeys   = ['razon_social','pj_nif','pj_telefono','pj_telefonomovil','pj_email','pj_tipovia','pj_direccion','pj_numero','pj_portal','pj_escalera','pj_piso','pj_puerta','pj_municipio','pj_provincia','pj_cp'];
  pjCampos.forEach((c, i) => {
    const el = document.getElementById('c' + c);
    if (el) el.value = datos ? (datos[pjKeys[i]] ?? '') : '';
  });
  document.getElementById('bloqueJuridica').style.display = (datos?.titular_id) ? 'none' : '';
  sincronizarLetra();
  document.getElementById('filasColindantes').innerHTML = '';
  if (datos?.id) {
    const jc = await fetch(`${API}obtener.php?tipo=colindantes&id=${datos.id}`).then(r => r.json()).catch(()=>({ok:false}));
    if (jc.ok && jc.data) jc.data.forEach(c => añadirColindante(c));
  }
  document.getElementById('modalCoto').classList.add('open');
}

async function editarCoto(id) {
  try {
    const j = await fetch(`${API}editar.php?tipo=coto&id=${id}`).then(r => r.json());
    if (!j.ok) { toast('Error: ' + j.msg); return; }
    abrirModalCoto(j.data);
  } catch { toast('Error de conexión'); }
}

async function guardarCoto() {
  const id        = document.getElementById('cId').value;
  const titularId = document.getElementById('cTitularId').value;
  modalError('cError', '');
  const body = {
    letra_provincia:  document.getElementById('cLetra').value.trim().toUpperCase(),
    numero_matricula: document.getElementById('cMatricula').value.trim(),
    provincia:        document.getElementById('cProvincia').value.trim(),
    municipio:        document.getElementById('cMunicipio').value.trim(),
    titular_id:       titularId || null,
    razon_social:     document.getElementById('cRazonSocial').value.trim(),
    pj_nif:           document.getElementById('cPjNif').value.trim().toUpperCase(),
    pj_telefono:      document.getElementById('cPjTelefono').value.trim(),
    pj_telefonomovil: document.getElementById('cPjMovil').value.trim(),
    pj_email:         document.getElementById('cPjEmail').value.trim(),
    pj_tipovia:       document.getElementById('cPjTipovia').value.trim(),
    pj_direccion:     document.getElementById('cPjDireccion').value.trim(),
    pj_numero:        document.getElementById('cPjNumero').value.trim(),
    pj_portal:        document.getElementById('cPjPortal').value.trim(),
    pj_escalera:      document.getElementById('cPjEscalera').value.trim(),
    pj_piso:          document.getElementById('cPjPiso').value.trim(),
    pj_puerta:        document.getElementById('cPjPuerta').value.trim(),
    pj_municipio:     document.getElementById('cPjMunicipio').value.trim(),
    pj_provincia:     document.getElementById('cPjProvincia').value.trim(),
    pj_cp:            document.getElementById('cPjCp').value.trim(),
    notas:            document.getElementById('cNotas').value.trim(),
  };
  if (!body.letra_provincia || !body.numero_matricula || !body.provincia || !body.municipio) {
    modalError('cError', 'Letra, matrícula, provincia y municipio son obligatorios'); return;
  }
  if (!validarMatricula(body.numero_matricula)) {
    modalError('cError', 'Nº de matrícula no válido. Debe tener exactamente 5 dígitos'); return;
  }
  if (!/^[A-Z]{1,2}$/.test(body.letra_provincia)) {
    modalError('cError', 'Letra de provincia no válida. Ej: P, LE, BU'); return;
  }
  if (!titularId) {
    if (!body.razon_social || !body.pj_nif) {
      modalError('cError', 'Razón social y NIF son obligatorios para la entidad titular'); return;
    }
    if (!body.pj_tipovia || !body.pj_direccion || !body.pj_cp || !body.pj_municipio || !body.pj_provincia) {
      modalError('cError', 'Tipo vía, nombre de la vía, CP, municipio y provincia son obligatorios para la entidad titular'); return;
    }
    if (!body.pj_telefono || !body.pj_telefonomovil || !body.pj_email) {
      modalError('cError', 'Teléfono, móvil y email son obligatorios para la entidad titular'); return;
    }
    if (body.pj_nif && !validarCIF(body.pj_nif)) {
      modalError('cError', 'CIF de la entidad no válido. Formato: G12345678'); return;
    }
    if (!validarTelFijo(body.pj_telefono)) {
      modalError('cError', 'Teléfono no válido. Debe tener exactamente 9 dígitos'); return;
    }
    if (!validarTelMovil(body.pj_telefonomovil)) {
      modalError('cError', 'Móvil no válido. Debe tener exactamente 9 dígitos'); return;
    }
    if (!validarCP(body.pj_cp)) {
      modalError('cError', 'Código postal no válido. Exactamente 5 dígitos'); return;
    }
  }
  const colindantes = recogerColindantes();
  for (const c of colindantes) {
    if (!c.provincia) {
      modalError('cError', 'La provincia es obligatoria en todos los cotos colindantes'); return;
    }
    if (!c.numero_coto) {
      modalError('cError', 'El Nº de matrícula es obligatorio en todos los cotos colindantes'); return;
    }
    if (!/^\d{5}$/.test(c.numero_coto)) {
      modalError('cError', `Nº de coto colindante "${c.numero_coto}" no válido. Debe tener exactamente 5 dígitos`); return;
    }
  }
  try {
    let r;
    if (id) {
      r = await fetch(`${API}editar.php?tipo=coto&id=${id}`, { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({...body, colindantes}) }).then(x => x.json());
    } else {
      r = await fetch(`${API}nuevo.php?tipo=coto`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({...body, colindantes}) }).then(x => x.json());
    }
    if (!r.ok) { modalError('cError', r.msg); return; }
    toast(id ? 'Coto actualizado ✓' : 'Coto creado ✓');
    cerrarModal2('modalCoto');
    listadoCotos = []; // forzar recarga del selector de cotos en eventos
    cargarCotos();
    cargarInicial('cotos');
  } catch { modalError('cError', 'Error de conexión'); }
}