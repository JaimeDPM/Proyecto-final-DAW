/**
 * gestion-personas.js
 * Modal persona, validaciones, guardar, borrar
 */

// ── Helpers modales edición ──────────────────────────────────
function cerrarModal2(id) { document.getElementById(id).classList.remove('open'); }
function cerrarModalSiOverlay2(e, id) { if (e.target === document.getElementById(id)) cerrarModal2(id); }

function modalError(elId, msg) {
  const el = document.getElementById(elId);
  if (!msg) { el.style.display = 'none'; el.textContent = ''; return; }
  el.textContent = '⚠ ' + msg;
  el.style.display = 'block';
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// ── Botón Nuevo ──────────────────────────────────────────────
function abrirModalNuevoRegistro() {
  if (tabActual === 'personas') abrirModalPersona(null);
  else if (tabActual === 'cotos') abrirModalCoto(null);
}

// ── Modal Persona ────────────────────────────────────────────
function abrirModalPersona(datos) {
  document.getElementById('modalPersonaTitulo').textContent = datos ? 'Editar persona' : 'Nueva persona';
  const campos = ['Id','Nombre','Apellido1','Apellido2','Dni','Telefono','Movil','Email','Tipovia','Direccion','Numero','Portal','Escalera','Piso','Puerta','Municipio','Provincia','Cp','Notas'];
  const keys   = ['id','nombre','apellido1','apellido2','dni_nif','telefono','telefonomovil','email','tipovia','direccion','numero','portal','escalera','piso','puerta','municipio','provincia','cp','notas'];
  campos.forEach((c, i) => {
    const el = document.getElementById('p' + c);
    if (el) el.value = datos ? (datos[keys[i]] ?? '') : '';
  });
  document.getElementById('modalPersona').classList.add('open');
}

async function editarPersona(id) {
  try {
    const j = await fetch(`${API}editar.php?tipo=persona&id=${id}`).then(r => r.json());
    if (!j.ok) { toast('Error: ' + j.msg); return; }
    abrirModalPersona(j.data);
  } catch { toast('Error de conexión'); }
}

// ── Validaciones ────────────────────────────────────────────
function validarNifPersona(nif) {
  if (/^\d{8}[A-Z]$/.test(nif)) return true;
  if (/^[XYZ]\d{7}[A-Z]$/.test(nif)) return true;
  return false;
}
function validarTelFijo(tel) {
  return /^\d{9}$/.test(tel.replace(/\s/g, ''));
}
function validarTelMovil(tel) {
  return /^\d{9}$/.test(tel.replace(/\s/g, ''));
}
function validarCP(cp) {
  return /^\d{5}$/.test(cp);
}
function validarMatricula(mat) {
  return /^\d{5}$/.test(mat);
}
function validarCIF(cif) {
  return /^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/.test(cif);
}

async function guardarPersona() {
  const id = document.getElementById('pId').value;
  modalError('pError', '');
  const body = {
    nombre:        document.getElementById('pNombre').value.trim(),
    apellido1:     document.getElementById('pApellido1').value.trim(),
    apellido2:     document.getElementById('pApellido2').value.trim(),
    dni_nif:       document.getElementById('pDni').value.trim().toUpperCase(),
    telefono:      document.getElementById('pTelefono').value.trim(),
    telefonomovil: document.getElementById('pMovil').value.trim(),
    email:         document.getElementById('pEmail').value.trim(),
    tipovia:       document.getElementById('pTipovia').value.trim(),
    direccion:     document.getElementById('pDireccion').value.trim(),
    numero:        document.getElementById('pNumero').value.trim(),
    portal:        document.getElementById('pPortal').value.trim(),
    escalera:      document.getElementById('pEscalera').value.trim(),
    piso:          document.getElementById('pPiso').value.trim(),
    puerta:        document.getElementById('pPuerta').value.trim(),
    municipio:     document.getElementById('pMunicipio').value.trim(),
    provincia:     document.getElementById('pProvincia').value.trim(),
    cp:            document.getElementById('pCp').value.trim(),
    notas:         document.getElementById('pNotas').value.trim(),
  };
  if (!body.nombre || !body.apellido1 || !body.dni_nif || !body.email ||
      !body.tipovia || !body.direccion || !body.cp || !body.municipio ||
      !body.provincia || !body.telefono || !body.telefonomovil) {
    modalError('pError', 'Por favor rellena todos los campos obligatorios (*)'); return;
  }
  if (!validarNifPersona(body.dni_nif)) {
    modalError('pError', 'DNI/NIF no válido. Formato: 12345678A (DNI) o X1234567A (NIE)'); return;
  }
  if (!validarTelFijo(body.telefono)) {
    modalError('pError', 'Teléfono no válido. Debe tener exactamente 9 dígitos'); return;
  }
  if (!validarTelMovil(body.telefonomovil)) {
    modalError('pError', 'Móvil no válido. Debe tener exactamente 9 dígitos'); return;
  }
  if (!validarCP(body.cp)) {
    modalError('pError', 'Código postal no válido. Exactamente 5 dígitos'); return;
  }
  try {
    let r;
    if (id) {
      r = await fetch(`${API}editar.php?tipo=persona&id=${id}`, { method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }).then(x => x.json());
    } else {
      r = await fetch(`${API}nuevo.php?tipo=persona`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }).then(x => x.json());
    }
    if (!r.ok) { modalError('pError', r.msg); return; }
    toast(id ? 'Persona actualizada ✓' : 'Persona creada ✓');
    cerrarModal2('modalPersona');
    cargarInicial('personas');
  } catch { modalError('pError', 'Error de conexión'); }
}

// ── Borrar ───────────────────────────────────────────────────
async function borrarRegistro(tipo, id, nombre) {
  if (!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)) return;
  try {
    const r = await fetch(`${API}borrado_logico.php?tipo=${tipo}&id=${id}`, { method:'DELETE' }).then(x => x.json());
    if (!r.ok) { toast('⚠ ' + r.msg); return; }
    toast('Eliminado correctamente');
    cargarInicial(tabActual);
  } catch { toast('Error de conexión'); }
}