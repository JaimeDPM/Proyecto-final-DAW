/**
 * campos_config.js
 * ─────────────────────────────────────────────────────────────
 * Configuración de campos para el auto-relleno de plantillas Word.
 *
 * Tipos disponibles:
 *   'select'     → desplegable con opciones fijas
 *   'multiselect' → selección múltiple (resultado unido por 'y')
 *   'fecha'   → selector de fecha (datepicker)
 *   'texto'   → input de texto libre con límite de caracteres
 *
 * Para añadir un campo nuevo, copia uno de los bloques y edítalo.
 * Para añadir una opción a un desplegable, añádela al array 'opciones'.
 * ─────────────────────────────────────────────────────────────
 */

const CAMPOS_CONFIG = {

  // ── Cargo del titular ─────────────────────────────────────
  cargo_titular: {
    tipo: 'select',
    opciones: [
      'titular',
      'arrendatario',
      'presidente',
      'representante legal',
      'secretario',
    ]
  },

  // ── Especie ───────────────────────────────────────────────
  especie: {
    tipo: 'multiselect',
    opciones: [
      'Jabalí',
      'Corzo',
      'Ciervo',
      'Gamo',
      'Zorro',
      'Lobo',
      'Liebre',
      'Conejo',
      'Perdiz roja',
      'Codorniz',
    ]
  },

  // ── Modalidad de caza ─────────────────────────────────────
  modalidad: {
    tipo: 'select',
    opciones: [
      'Rececho',
      'Aguardo',
      'Espera',
      'Batida',
      'Montería',
      'Gancho',
      'Ojeo',
    ]
  },

  // ── Fechas ────────────────────────────────────────────────
  fecha_inicio: {
    tipo: 'fecha',
    orden: 1
  },

  fecha_fin: {
    tipo: 'fecha',
    orden: 2
  },

  fecha_comunicacion: {
    tipo: 'fecha',
    orden: 3
  },

  fecha_firma: {
    tipo: 'fecha',
    orden: 4
  },

  // ── Temporada (se calcula dinámicamente: anterior, actual y siguiente) ───
  temporada: (() => {
    const hoy  = new Date();
    const anyo = hoy.getMonth() >= 8 ? hoy.getFullYear() : hoy.getFullYear() - 1;
    return {
      tipo: 'select',
      opciones: [
        `${anyo - 1}/${anyo}`,
        `${anyo}/${anyo + 1}`,
        `${anyo + 1}/${anyo + 2}`,
      ]
    };
  })(),

  // ── Número de expediente ──────────────────────────────────
  num_expediente: {
    tipo: 'texto',
    placeholder: 'Nº de expediente asignado por la Junta',
    maxlength: 30
  },

  // ── Especies (formato con preposición, multiselección) ───
  especies: {
    tipo: 'multiselect',
    opciones: [
      'al jabalí',
      'al corzo',
      'al ciervo',
      'al gamo',
      'al zorro',
      'al lobo',
      'a la liebre',
      'al conejo',
      'a la perdiz roja',
      'a la codorniz',
    ]
  },

  // ── Modalidad con detalle ─────────────────────────────────
  modalidad_detalle: {
    tipo: 'select',
    opciones: [
      'una montería',
      'un gancho',
      'una batida al jabalí',
      'un rececho',
      'un aguardo',
      'una espera',
      'un ojeo',
    ]
  },

  // ── Cupo ──────────────────────────────────────────────────
  cupo: {
    tipo: 'texto',
    placeholder: 'Ej: 3 ejemplares',
    maxlength: 50
  },

  // ── Número de petición ────────────────────────────────────
  num_peticion: {
    tipo: 'texto',
    placeholder: 'Nº de petición asignado por la Junta',
    maxlength: 30
  },

};