<?php require_once 'php/auth.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestión Cinegética</title>
<link rel="icon" href="../Imagenes/perdizBuena.png" type="image/x-icon">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<link rel="stylesheet" href="css/gestion.css">
</head>
<body>

<header>
  <div class="header-left">
    <div class="titulo">Gestión Cinegética</div>
    <div class="subtitulo">Cotos · Personas · Declaraciones Junta CyL · Modelos Word</div>
  </div>
  <div class="header-right">
    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
      <a href="usuarios.php" class="btn-logout" style="border-color:#1a5ca8;color:#1a5ca8" title="Gestión de usuarios">👤 Usuarios</a>
      <a href="papelera.php" class="btn-logout" style="border-color:#dc2626;color:#dc2626" title="Papelera">🗑 Papelera</a>
    <?php endif; ?>
    <div class="header-usuario">Hola, <span><?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span></div>
    <a href="logout.php" class="btn-logout">⬩ Cerrar sesión</a>
  </div>
</header>

<div class="contenedor">

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab activo" onclick="cambiarTab('personas')">Personas</button>
    <button class="tab" onclick="cambiarTab('cotos')">Cotos</button>
    <button class="tab" onclick="cambiarTab('declaraciones')">Declaraciones Junta</button>
    <button class="tab" onclick="cambiarTab('documentos')">Modelos en blanco</button>
  </div>

  <!-- ── BANNER PRÓXIMOS EVENTOS ── -->
  <div class="card proximos" id="cardProximos">
    <div class="card-header"><div class="ibadge">⚠️</div> Eventos que EMPIEZAN en los próximos 15 días</div>
    <div class="card-body">
      <div class="proximos-lista" id="proximosLista"></div>
    </div>
  </div>

  <!-- ── SECCIÓN BÚSQUEDA ── -->
  <div id="seccionBusqueda">
    <div class="card">
      <div class="card-header"><div class="ibadge">🔍</div> <span id="lblBuscar">Buscar persona</span></div>
      <div class="card-body">
        <div class="search-row">
          <input id="q" type="text" placeholder="Nombre, DNI, email, nº de coto…" autocomplete="off">
          <button class="btn btn-primary" id="btnBuscar" onclick="buscar()">Buscar</button>
        </div>
      </div>
    </div>

    <div class="card" id="cardResultados" style="display:none">
      <div class="card-header">
        <div class="ibadge">📋</div>
        <div class="card-header-row">
          <span id="lblResultados">Resultados</span>
          <button class="btn btn-primary btn-sm" id="btnNuevo" onclick="abrirModalNuevoRegistro()">+ Nuevo</button>
        </div>
      </div>
      <div class="table-wrap" style="max-height:380px;overflow-y:auto"><table><thead id="thead"></thead><tbody id="tbody"></tbody></table></div>
    </div>

    <div class="card ficha" id="fichaCard">
      <div class="card-header"><div class="ibadge">📄</div> <span id="lblFicha">Detalle</span></div>
      <div class="card-body" id="fichaBody"></div>
      <div class="acciones" id="fichaAcciones"></div>
    </div>
  </div>

  <!-- ── SECCIÓN PLANTILLAS (Modelos en blanco) ── -->
  <div id="seccionPlantillas" style="display:none">
    <div class="card">
      <div class="card-header">
        <div class="ibadge">📝</div>
        <div class="card-header-row">
          <span>Plantillas disponibles</span>
          <?php if (($_SESSION['usuario_rol'] ?? '') === 'admin'): ?>
          <button class="btn btn-primary btn-sm" onclick="abrirModalSubirPlantilla()">⬆ Subir plantilla</button>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-body" style="padding:0">
        <div id="plantillasLista"><div class="empty" style="padding:36px">Cargando plantillas…</div></div>
      </div>
    </div>
  </div>

  <!-- ── CALENDARIO (siempre visible) ── -->
  <div class="card">
    <div class="card-header">
      <div class="ibadge">📅</div>
      <div class="card-header-row" style="flex:1">
        <div class="cal-nav">
          <button class="btn btn-ghost btn-sm" onclick="cambiarMes(-1)">◀</button>
          <span class="cal-mes-label" id="calMesLabel"></span>
          <button class="btn btn-ghost btn-sm" onclick="cambiarMes(1)">▶</button>
        </div>
        <button class="btn btn-primary btn-sm" onclick="abrirModalNuevo()">+ Nuevo evento</button>
      </div>
    </div>
    <div class="card-body">
      <div class="cal-grid" id="calGrid"></div>
      <div class="cal-leyenda" id="calLeyenda">
        <div class="cal-leyenda-item"><div class="cal-leyenda-dot" style="background:var(--caceria-bg);border:1px solid var(--caceria)"></div> Cacería / Jornada</div>
        <div class="cal-leyenda-item"><div class="cal-leyenda-dot" style="background:var(--tramite-bg);border:1px solid var(--tramite)"></div> Trámite administrativo</div>
        <div class="cal-leyenda-item"><div class="cal-leyenda-dot" style="background:var(--precinto-bg);border:1px solid var(--precinto)"></div> Precintos</div>
      </div>
    </div>
  </div>

</div><!-- /contenedor -->

<div id="toast"></div>

<!-- ── VARIABLE PHP → JS (rol y usuario) ── -->
<script>
  const ES_ADMIN  = <?= json_encode(($_SESSION['usuario_rol'] ?? '') === 'admin') ?>;
  const USUARIO_ID = <?= json_encode((int)($_SESSION['usuario_id'] ?? 0)) ?>;
</script>

<!-- ── MODAL EVENTO ── -->
<div class="modal-overlay" id="modalOverlay" onclick="cerrarModalSiOverlay(event)">
  <div class="modal">
    <h3 id="modalTitulo">Nuevo evento</h3>
    <div class="form-group">
      <label>Título *</label>
      <input type="text" id="evTitulo" placeholder="Ej: Montería anual P-10078">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Tipo *</label>
        <select id="evTipo" onchange="onTipoEvento()">
          <option value="caceria">🦌 Cacería / Jornada</option>
          <option value="tramite">📋 Trámite administrativo</option>
          <option value="precinto">🔒 Precintos</option>
          <?php if (($_SESSION['usuario_rol'] ?? '') === 'admin'): ?>
          <option value="temporada">📅 Temporada</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Coto (opcional)</label>
        <select id="evCoto">
          <option value="">— General (todos) —</option>
        </select>
      </div>
    </div>
    <div class="form-group" id="bloqueIcono" style="display:none">
      <label>Icono identificativo</label>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:4px" id="iconoGrid"></div>
      <input type="hidden" id="evIcono">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Fecha inicio *</label>
        <input type="date" id="evFechaInicio">
      </div>
      <div class="form-group">
        <label>Fecha fin (opcional)</label>
        <input type="date" id="evFechaFin">
      </div>
    </div>
    <div class="form-group">
      <label>Comentario</label>
      <textarea id="evComentario" placeholder="Notas, condiciones, recordatorios…"></textarea>
    </div>
    <div class="form-group">
      <label class="check-row">
        <input type="checkbox" id="evRecurrente">
        <span>🔁 Se repite anualmente (misma fecha cada año)</span>
      </label>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger btn-sm" id="btnEliminarEvento" style="display:none;margin-right:auto" onclick="eliminarEvento()">Eliminar</button>
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
      <button class="btn btn-primary" id="btnGuardarEvento" onclick="guardarEvento()">Guardar</button>
    </div>
  </div>
</div>

<!-- ── MODAL EDITAR/NUEVO PERSONA ── -->
<div class="modal-overlay" id="modalPersona" onclick="cerrarModalSiOverlay2(event,'modalPersona')">
  <div class="modal" style="max-width:600px">
    <h3 id="modalPersonaTitulo">Nueva persona</h3>
    <input type="hidden" id="pId">
    <div class="form-row">
      <div class="form-group"><label>Nombre *</label><input type="text" id="pNombre"></div>
      <div class="form-group"><label>Apellido 1 *</label><input type="text" id="pApellido1"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Apellido 2</label><input type="text" id="pApellido2"></div>
      <div class="form-group"><label>DNI/NIF *</label><input type="text" id="pDni"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Teléfono *</label><input type="text" id="pTelefono"></div>
      <div class="form-group"><label>Móvil *</label><input type="text" id="pMovil"></div>
    </div>
    <div class="form-group"><label>Email *</label><input type="email" id="pEmail"></div>
    <div class="form-row">
      <div class="form-group"><label>Tipo vía *</label><input type="text" id="pTipovia" placeholder="Calle, Avenida…"></div>
      <div class="form-group"><label>Nombre de la vía *</label><input type="text" id="pDireccion"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Número</label><input type="text" id="pNumero"></div>
      <div class="form-group"><label>Portal</label><input type="text" id="pPortal"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Escalera</label><input type="text" id="pEscalera"></div>
      <div class="form-group"><label>Piso</label><input type="text" id="pPiso"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Puerta</label><input type="text" id="pPuerta"></div>
      <div class="form-group"><label>Municipio *</label><input type="text" id="pMunicipio"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Provincia *</label><input type="text" id="pProvincia"></div>
      <div class="form-group"><label>CP *</label><input type="text" id="pCp"></div>
    </div>
    <div class="form-group"><label>Notas</label><textarea id="pNotas"></textarea></div>
    <div id="pError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:8px"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="cerrarModal2('modalPersona')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarPersona()">Guardar</button>
    </div>
  </div>
</div>

<!-- ── MODAL EDITAR/NUEVO COTO ── -->
<div class="modal-overlay" id="modalCoto" onclick="cerrarModalSiOverlay2(event,'modalCoto')">
  <div class="modal" style="max-width:600px">
    <h3 id="modalCotoTitulo">Nuevo coto</h3>
    <input type="hidden" id="cId">
    <div class="form-row">
      <div class="form-group">
        <label>Provincia *</label>
        <select id="cProvincia" onchange="sincronizarLetra()">
          <option value="">— Selecciona provincia —</option>
          <option value="Ávila">Ávila</option>
          <option value="Burgos">Burgos</option>
          <option value="León">León</option>
          <option value="Palencia">Palencia</option>
          <option value="Salamanca">Salamanca</option>
          <option value="Segovia">Segovia</option>
          <option value="Soria">Soria</option>
          <option value="Valladolid">Valladolid</option>
          <option value="Zamora">Zamora</option>
        </select>
      </div>
      <div class="form-group"><label>Nº matrícula *</label><input type="text" id="cMatricula"></div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Letra provincia</label>
        <input type="text" id="cLetra" readonly style="background:#f4f5f7;color:#6b7280;cursor:not-allowed">
      </div>
      <div class="form-group"><label>Municipio *</label><input type="text" id="cMunicipio"></div>
    </div>
    <div class="form-group">
      <label>Titular (persona física)</label>
      <select id="cTitularId">
        <option value="">— Persona jurídica —</option>
      </select>
    </div>
    <div id="bloqueJuridica">
      <div class="form-row">
        <div class="form-group"><label>Razón social *</label><input type="text" id="cRazonSocial"></div>
        <div class="form-group"><label>NIF *</label><input type="text" id="cPjNif"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Teléfono *</label><input type="text" id="cPjTelefono"></div>
        <div class="form-group"><label>Móvil *</label><input type="text" id="cPjMovil"></div>
      </div>
      <div class="form-group"><label>Email *</label><input type="email" id="cPjEmail"></div>
      <div class="form-row">
        <div class="form-group"><label>Tipo vía *</label><input type="text" id="cPjTipovia" placeholder="Calle, Avenida…"></div>
        <div class="form-group"><label>Nombre de la vía *</label><input type="text" id="cPjDireccion"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Número</label><input type="text" id="cPjNumero"></div>
        <div class="form-group"><label>Portal</label><input type="text" id="cPjPortal"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Escalera</label><input type="text" id="cPjEscalera"></div>
        <div class="form-group"><label>Piso</label><input type="text" id="cPjPiso"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Puerta</label><input type="text" id="cPjPuerta"></div>
        <div class="form-group"><label>Municipio *</label><input type="text" id="cPjMunicipio"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Provincia *</label><input type="text" id="cPjProvincia"></div>
        <div class="form-group"><label>CP *</label><input type="text" id="cPjCp"></div>
      </div>
    </div>
    <div class="form-group"><label>Notas</label><textarea id="cNotas"></textarea></div>
    <div style="margin-top:16px">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <label style="font-weight:600;font-size:13px">Cotos colindantes</label>
        <button type="button" class="btn btn-ghost btn-sm" onclick="añadirColindante()">+ Añadir</button>
      </div>
      <table class="colindantes-table" id="tablaColindantes">
        <thead>
          <tr>
            <th>Provincia</th><th>Nº coto</th><th>&lt;500m</th><th>Notas</th><th></th>
          </tr>
        </thead>
        <tbody id="filasColindantes"></tbody>
      </table>
    </div>
    <div id="cError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:8px"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="cerrarModal2('modalCoto')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarCoto()">Guardar</button>
    </div>
  </div>
</div>

<!-- ── MODAL SUBIR PLANTILLA (solo admin) ── -->
<div class="modal-overlay" id="modalSubirPlantilla" onclick="cerrarModalSiOverlay2(event,'modalSubirPlantilla')">
  <div class="modal" style="max-width:480px">
    <h3>⬆ Subir plantilla Word</h3>
    <div class="form-group">
      <label>Nombre de la plantilla *</label>
      <input type="text" id="spNombre" placeholder="Ej: Autorización control poblacional">
    </div>
    <div class="form-group">
      <label>Archivo .docx *</label>
      <input type="file" id="spArchivo" accept=".docx" style="padding:6px 0;border:none;font-size:13px">
      <div style="font-size:11px;color:#6b7280;margin-top:4px">
        El archivo debe tener los marcadores <code style="background:#f4f5f7;padding:1px 5px;border-radius:4px">{{nombre_campo}}</code> donde quieras que se auto-rellene el texto.
      </div>
    </div>
    <div id="spError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:8px"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="cerrarModal2('modalSubirPlantilla')">Cancelar</button>
      <button class="btn btn-primary" id="btnGuardarPlantilla" onclick="guardarPlantilla()">Subir</button>
    </div>
  </div>
</div>

<!-- ── MODAL GENERAR DOCUMENTO ── -->
<div class="modal-overlay" id="modalGenerar" onclick="cerrarModalSiOverlay2(event,'modalGenerar')">
  <div class="modal" style="max-width:540px;max-height:90vh;overflow-y:auto">
    <h3>✍ Generar documento</h3>
    <div style="font-size:12px;color:#6b7280;margin-bottom:16px">
      Plantilla: <strong id="genNombrePlantilla"></strong>
    </div>
    <div class="form-group">
      <label>Coto</label>
      <select id="genCoto"><option value="">— Selecciona un coto —</option></select>
      <div style="font-size:11px;color:#6b7280;margin-top:3px">Los datos del coto y su titular se rellenarán automáticamente.</div>
    </div>
    <div class="form-group">
      <label>Persona (representante / autorizado / firmante)</label>
      <select id="genPersona"><option value="">— Selecciona una persona —</option></select>
      <div style="font-size:11px;color:#6b7280;margin-top:3px">Sus datos personales se rellenarán automáticamente.</div>
    </div>
    <div id="genBloqueOrganizador" style="display:none">
      <div class="form-group">
        <label>Organizador / Capitán de la cacería</label>
        <select id="genOrganizador"><option value="">— Selecciona una persona —</option></select>
        <div style="font-size:11px;color:#6b7280;margin-top:3px">Sus datos se rellenarán automáticamente en los campos {{organizador_*}}.</div>
      </div>
    </div>
    <div id="genCamposLibres" style="margin-top:4px"></div>
    <div id="genError" style="display:none;background:#fee2e2;color:#dc2626;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:600;margin-bottom:8px;margin-top:8px"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="cerrarModal2('modalGenerar')">Cancelar</button>
      <button class="btn btn-amber" id="btnGenerarDoc" onclick="generarDocumento()">Generar y descargar</button>
    </div>
  </div>
</div>

<!-- ── MODAL DÍA (todos los eventos de un día) ── -->
<div class="modal-overlay" id="modalDia" onclick="cerrarModalSiOverlay2(event,'modalDia')">
  <div class="modal" style="max-width:420px">
    <h3>📅 Eventos del <span id="modalDiaFecha"></span></h3>
    <div id="modalDiaLista" style="display:flex;flex-direction:column;gap:6px;margin-top:8px"></div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="cerrarModal2('modalDia')">Cerrar</button>
    </div>
  </div>
</div>

<!-- ── SCRIPTS (orden importante) ── -->
<script src="js/gestion_core.js"></script>
<script src="js/gestion_calendario.js"></script>
<script src="js/gestion_personas.js"></script>
<script src="js/gestion_cotos.js"></script>
<script src="js/gestion_plantillas.js"></script>
<script src="js/campos_config.js"></script>
</body>
</html>