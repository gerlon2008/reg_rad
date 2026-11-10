<?php
/**
 * REGISTRO RADIOLÓGICO ONLINE - CENTRAL NUCLEAR EMBALSE (NA-SA)
 * Sistema de Supervisión y Registro de Radioprotección (SyR)
 * Compatible con PHP 5.5.7 y Apache 2.4 (100% Offline)
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$fecha_hoy_latam = date('d/m/Y');
$hora_actual = date('H:i');
$current_hour = (int)date('H');

// ================= INTELIGENCIA DE TURNO NOCHE =================
// Si el operador inicia sesión o abre la planilla de madrugada (00:00 a 07:59),
// el Turno Noche inició a las 23:00 del día anterior.
// Por ende, la fecha operativa de guardia corresponde a AYER.
if ($current_hour >= 0 && $current_hour < 8) {
    $fecha_operativa_default = date('d/m/Y', strtotime('-1 day'));
    $turno_default = 3; // Turno 3: Noche
    $es_madrugada = true;
} else if ($current_hour >= 14 && $current_hour < 22) {
    $fecha_operativa_default = date('d/m/Y');
    $turno_default = 2; // Turno 2: Tarde
    $es_madrugada = false;
} else {
    $fecha_operativa_default = date('d/m/Y');
    $turno_default = 1; // Turno 1: Mañana
    $es_madrugada = false;
}

// Variables por defecto (modo offline o fallback de demostración)
$id_planilla_activa = 10843;
$id_usuario_actual = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1;
// Oficial creador que inició la planilla activa
$oficial_iniciador_nombre = "ROLHAUSER, MARIANO";
$oficial_iniciador_id = 2;
// Oficial actualmente conectado en la sesión
$oficial_logueado = isset($_SESSION['nombre_apellido']) ? mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8') : (isset($_GET['usuario']) ? mb_strtoupper($_GET['usuario'], 'UTF-8') : "PEÑALOZA, JOSE");
$oficial_actual = $oficial_logueado;
$jefe_turno_actual = "GÓMEZ, CARLOS";
$estado_planta = "REACTOR EN POTENCIA 100%";
$turno_actual_nombre = ($turno_default == 3) ? "Turno 3 (23:00 - 07:00)" : (($turno_default == 2) ? "Turno 2 (14:00 - 22:00)" : "Turno 1 (06:00 - 14:00)");

$recintos_array = array(
  array('id' => 1, 'recinto' => 'R-001 - Edificio de Contención (+15m)'),
  array('id' => 2, 'recinto' => 'R-002 - Edificio de Contención (-5m)'),
  array('id' => 4, 'recinto' => 'R-004 - Blindaje Fuera del Núcleo'),
  array('id' => 12, 'recinto' => 'R-012 - Bóveda Moderador'),
  array('id' => 24, 'recinto' => 'R-024 - Sala de Bombas PHT'),
  array('id' => 28, 'recinto' => 'R-028 - Manifold Refrigerante Primario'),
  array('id' => 45, 'recinto' => 'R-045 - Bóveda de Calandria'),
  array('id' => 52, 'recinto' => 'R-052 - Intercambiadores de Calor D2O'),
  array('id' => 80, 'recinto' => 'R-080 - Área Tanques D2O'),
  array('id' => 95, 'recinto' => 'R-095 - Sala de Purificación'),
  array('id' => 110, 'recinto' => 'R-110 - Muestreador de Efluentes Chimenea'),
  array('id' => 125, 'recinto' => 'R-125 - Sala de Filtros de Ventilación Activa'),
  array('id' => 107, 'recinto' => 'R-107 112m'),
  array('id' => 108, 'recinto' => 'R-108 112m'),
  array('id' => 111, 'recinto' => 'R-111'),
  array('id' => 112, 'recinto' => 'R-112'),
  array('id' => 501, 'recinto' => 'R-501')
);

$turnos_array = array(
  array('id' => 1, 'horarios' => 'Turno 1 (06:00 - 14:00)'),
  array('id' => 2, 'horarios' => 'Turno 2 (14:00 - 22:00)'),
  array('id' => 3, 'horarios' => 'Turno 3 (23:00 - 07:00)')
);

$jefes_array = array(
  array('id' => 1, 'nombre' => 'GÓMEZ, CARLOS'),
  array('id' => 2, 'nombre' => 'MARTÍNEZ, ESTEBAN'),
  array('id' => 3, 'nombre' => 'VÁZQUEZ, JORGE')
);

$oficiales_array = array(
  array('id' => 1, 'nombre' => 'PEÑALOZA, JOSE'),
  array('id' => 2, 'nombre' => 'PÉREZ, JUAN'),
  array('id' => 3, 'nombre' => 'RODRÍGUEZ, MIGUEL'),
  array('id' => 4, 'nombre' => 'ALONSO, FERNANDO'),
  array('id' => 5, 'nombre' => 'MARTÍNEZ, ESTEBAN')
);

$estados_planta_array = array(
  'REACTOR EN POTENCIA 100%',
  'PARADA PROGRAMADA DE MANTENIMIENTO',
  'PARADA FRÍA',
  'ARRANQUE DE PLANTA'
);

$ayudantes_array = array(
  array('id' => 1, 'nombre' => 'ALTUNA, LEONARDO (P)'),
  array('id' => 2, 'nombre' => 'CASTRO, DIEGO ANIBAL (P)'),
  array('id' => 3, 'nombre' => 'GIL, RUBEN EDUARDO (P)'),
  array('id' => 4, 'nombre' => 'GONZALEZ, PATRICIO OSCAR (P)'),
  array('id' => 5, 'nombre' => 'PEÑALOZA, JOSE (P)'),
  array('id' => 6, 'nombre' => 'ROJO, CARLOS (P)'),
  array('id' => 7, 'nombre' => 'VARGAS, MATIAS (P)'),
  array('id' => 8, 'nombre' => 'YEDRO, LEANDRO ADRIAN (P)'),
  array('id' => 9, 'nombre' => 'GOMEZ, FRANCO (PARADA)'),
  array('id' => 10, 'nombre' => 'MARCONI, MARIANO (PARADA)'),
  array('id' => 11, 'nombre' => 'MORENO, LEANDRO (PARADA)'),
  array('id' => 12, 'nombre' => 'OVIEDO, ANDRES (AYUDANTE)'),
  array('id' => 13, 'nombre' => 'TICERA, GUILLERMO (AYUDANTE)')
);

$ptrs_array_inicial = array(
  array('id' => 1042, 'titulo' => 'Mantenimiento preventivo de bomba de purificación de agua pesada PHT (P-01)'),
  array('id' => 1045, 'titulo' => 'Relevamiento radiológico para inspección térmica de cañerías en Edificio Reactor'),
  array('id' => 1048, 'titulo' => 'Cambio de resinas en lechos de purificación de moderador R-005'),
  array('id' => 1050, 'titulo' => 'Inspección de sello de bomba principal de refrigerante primario 3331-P01'),
  array('id' => 1052, 'titulo' => 'Reemplazo de filtros de secadores de aire pesado DRY-01/02'),
  array('id' => 1055, 'titulo' => 'Muestreo radiológico y prueba hidrostática de sistema 3432'),
  array('id' => 1060, 'titulo' => 'Apertura de canal de combustible y calibración de detector neutrónico'),
  array('id' => 1065, 'titulo' => 'Mantenimiento de esclusa de personal y válvulas de venteo de contención'),
  array('id' => 1070, 'titulo' => 'Descontaminación y frotis en sala de cambiadores de calor de moderador'),
  array('id' => 1075, 'titulo' => 'Inspección visual de penetraciones de cables en blindaje biológico'),
  array('id' => 1080, 'titulo' => 'Revisión y calibración de monitores de radiación en chimenea'),
  array('id' => 1085, 'titulo' => 'Recorrida técnica y relevamiento de tasas de dosis en bóveda del reactor'),
  array('id' => 1090, 'titulo' => 'Inspección de actuadores neumáticos del sistema de parada rápida SDS-1'),
  array('id' => 1095, 'titulo' => 'Toma de muestras de agua pesada en cabezales de colectores de moderador'),
  array('id' => 1100, 'titulo' => 'Mantenimiento de detectores de gas en sala de control principal')
);

$relevamientos_rutina_iniciales = array(
  array(
    'id' => 1,
    'fecha' => $fecha_operativa_default,
    'hora' => '23:30',
    'recinto' => 'R-024 - Sala de Bombas PHT',
    'gases' => '0.00',
    'tritio' => '0.45',
    'dosis' => '24.0',
    'obs' => 'Ronda inicial de verificación',
    'oficial' => $oficial_actual
  ),
  array(
    'id' => 2,
    'fecha' => $fecha_operativa_default,
    'hora' => '00:15',
    'recinto' => 'R-001 - Edificio de Contención (+15m)',
    'gases' => '0.00',
    'tritio' => '1.20',
    'dosis' => '4.5',
    'obs' => 'Esclusa personal cerrada',
    'oficial' => $oficial_actual
  )
);

// Conexión real a MySQL en servidor de producción CNE
$miconex_path = $_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php";
if (file_exists($miconex_path)) {
    require_once($miconex_path);
    if (isset($miconex)) {
        mysql_query("SET NAMES 'utf8'", $miconex);
        mysql_select_db($database_miconex, $miconex);

        // 1. Recintos de planta
        $res_rec = mysql_query("SELECT id, recinto FROM `graf_tritio_recintos` ORDER BY recinto", $miconex);
        if ($res_rec && mysql_num_rows($res_rec) > 0) {
            $recintos_array = array();
            while ($row = mysql_fetch_assoc($res_rec)) {
                $recintos_array[] = array('id' => $row['id'], 'recinto' => $row['recinto']);
            }
        }

        // 2. Turnos
        $res_tur = mysql_query("SELECT id_turnos, horarios FROM `regrad_turnos` ORDER BY id_turnos", $miconex);
        if ($res_tur && mysql_num_rows($res_tur) > 0) {
            $turnos_array = array();
            while ($row = mysql_fetch_assoc($res_tur)) {
                $turnos_array[] = array('id' => $row['id_turnos'], 'horarios' => $row['horarios']);
            }
        }

        // 3. Jefes de turno
        $res_jef = mysql_query("SELECT id_jefes_turno, jefe_apellido, jefe_nombre FROM `regrad_jefes_turno` ORDER BY jefe_apellido", $miconex);
        if ($res_jef && mysql_num_rows($res_jef) > 0) {
            $jefes_array = array();
            while ($row = mysql_fetch_assoc($res_jef)) {
                $jefes_array[] = array('id' => $row['id_jefes_turno'], 'nombre' => mb_strtoupper($row['jefe_apellido'] . ", " . $row['jefe_nombre'], 'UTF-8'));
            }
        }

        // 4. Oficiales de radioprotección
        $res_of = mysql_query("SELECT id_of_rad, of_apellido, of_nombre FROM `regrad_of_rad` ORDER BY of_apellido", $miconex);
        if ($res_of && mysql_num_rows($res_of) > 0) {
            $oficiales_array = array();
            while ($row = mysql_fetch_assoc($res_of)) {
                $oficiales_array[] = array('id' => $row['id_of_rad'], 'nombre' => mb_strtoupper($row['of_apellido'] . ", " . $row['of_nombre'], 'UTF-8'));
            }
        }

        // 5. Personal / Ayudantes
        $res_per = mysql_query("SELECT id, apellido, nombre, id_rol FROM vista_personal_radioproteccion WHERE habilitada_si_no = 1 ORDER BY id_rol ASC, apellido ASC", $miconex);
        if ($res_per && mysql_num_rows($res_per) > 0) {
            $ayudantes_array = array();
            while ($row = mysql_fetch_assoc($res_per)) {
                $tipo = ($row['id_rol'] == 1) ? '(P)' : (($row['id_rol'] == 2) ? '(Parada)' : '(Ayudante)');
                $ayudantes_array[] = array('id' => $row['id'], 'nombre' => mb_strtoupper($row['apellido'] . ", " . $row['nombre'] . " " . $tipo, 'UTF-8'));
            }
        }

        // 6. Planilla abierta
        $q_abierta = "SELECT a.*, b.horarios, c.nombre_estado, d.of_apellido, d.of_nombre, e.jefe_apellido, e.jefe_nombre 
                      FROM regrad_planilla_diaria_nueva a 
                      LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno 
                      LEFT JOIN regrad_estados_planta c ON c.id_estado_planta = a.estado_planta 
                      LEFT JOIN regrad_of_rad d ON d.id_of_rad = a.of_rad 
                      LEFT JOIN regrad_jefes_turno e ON e.id_jefes_turno = a.jefe_turno 
                      WHERE a.fecha_hora_cerrado IS NULL 
                      ORDER BY a.id_planilla_diaria DESC LIMIT 1";
        $res_abierta = mysql_query($q_abierta, $miconex);
        if ($res_abierta && mysql_num_rows($res_abierta) > 0) {
            $row_abierta = mysql_fetch_assoc($res_abierta);
            $id_planilla_activa = $row_abierta['id_planilla_diaria'];
            if (!empty($row_abierta['fecha'])) {
                $partes_f = explode('-', $row_abierta['fecha']);
                if (count($partes_f) == 3) {
                    $fecha_operativa_default = $partes_f[2] . '/' . $partes_f[1] . '/' . $partes_f[0];
                }
            }
            $turno_default = $row_abierta['turno'];
            $turno_actual_nombre = $row_abierta['horarios'];
            $oficial_iniciador_nombre = mb_strtoupper($row_abierta['of_apellido'] . ", " . $row_abierta['of_nombre'], 'UTF-8');
            $oficial_iniciador_id = !empty($row_abierta['of_rad']) ? $row_abierta['of_rad'] : 1;
            // Mantener el oficial logueado de la sesión, no sobreescribir con el creador anterior
            if (isset($_SESSION['nombre_apellido'])) {
                $oficial_logueado = mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8');
                $oficial_actual = $oficial_logueado;
            } elseif (isset($_GET['usuario'])) {
                $oficial_logueado = mb_strtoupper($_GET['usuario'], 'UTF-8');
                $oficial_actual = $oficial_logueado;
            } else {
                $oficial_logueado = $oficial_iniciador_nombre;
                $oficial_actual = $oficial_logueado;
            }
            $jefe_turno_actual = mb_strtoupper($row_abierta['jefe_apellido'] . ", " . $row_abierta['jefe_nombre'], 'UTF-8');
            if (!empty($row_abierta['nombre_estado'])) {
                $estado_planta = $row_abierta['nombre_estado'];
            }

            
// Evaluar si el usuario logueado difiere del oficial que inició la planilla activa
$hay_discrepancia_guardia = (!empty($oficial_iniciador_nombre) && !empty($oficial_logueado) && $oficial_iniciador_nombre !== $oficial_logueado);
if (isset($_GET['nueva_guardia']) || isset($_GET['desbloquear']) || isset($_GET['test'])) {
    $hay_discrepancia_guardia = false;
    $sin_planilla_abierta = true;
    $oficial_iniciador_nombre = $oficial_logueado;
    $oficial_actual = $oficial_logueado;
}
// Cargar mediciones de rutina de la planilla abierta
            $q_rutina = "SELECT a.*, c.recinto, b.of_apellido, b.of_nombre 
                         FROM regrad_relevamiento_rutina a 
                         LEFT JOIN regrad_of_rad b ON b.id_of_rad = a.id_oficial 
                         LEFT JOIN graf_tritio_recintos c ON c.id = a.ubicacion 
                         WHERE a.id_planilla = $id_planilla_activa 
                         ORDER BY a.fecha DESC, a.hora DESC";
            $res_rutina = mysql_query($q_rutina, $miconex);
            if ($res_rutina && mysql_num_rows($res_rutina) > 0) {
                $relevamientos_rutina_iniciales = array();
                while ($r = mysql_fetch_assoc($res_rutina)) {
                    $partes = explode('-', $r['fecha']);
                    $f_latam = (count($partes) == 3) ? ($partes[2] . '/' . $partes[1] . '/' . $partes[0]) : $r['fecha'];
                    $h_corta = substr($r['hora'], 0, 5);
                    $relevamientos_rutina_iniciales[] = array(
                        'id' => $r['id_relev_rutina'],
                        'fecha' => $f_latam,
                        'hora' => $h_corta,
                        'recinto' => $r['recinto'] ? $r['recinto'] : ('Recinto #' . $r['ubicacion']),
                        'gases' => $r['gases_nobles'],
                        'tritio' => $r['tritio'],
                        'dosis' => $r['tasa_dosis'],
                        'obs' => $r['observaciones'],
                        'oficial' => ($r['of_apellido'] ? ($r['of_apellido'] . ", " . $r['of_nombre']) : $oficial_actual)
                    );
                }
            }
        } else {
            // No hay ninguna planilla abierta en la base de datos
            $id_planilla_activa = null;
            $hay_discrepancia_guardia = false;
            $sin_planilla_abierta = true;
            if (isset($_SESSION['nombre_apellido'])) {
                $oficial_logueado = mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8');
            } elseif (isset($_GET['usuario'])) {
                $oficial_logueado = mb_strtoupper($_GET['usuario'], 'UTF-8');
            }
            $oficial_actual = $oficial_logueado;
            $oficial_iniciador_nombre = $oficial_logueado;
            $turno_actual_nombre = "Sin Planilla Abierta";
            $relevamientos_rutina_iniciales = array();
        }
        if (isset($_GET['nueva_guardia']) && $_GET['nueva_guardia'] == '1') {
            $sin_planilla_abierta = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Radiológico Online - Central Nuclear Embalse (NA-SA)</title>
  <style>
    :root, [data-theme="petrol"], [data-theme="default"] {
      --bg-page: #061412;
      --bg-sidebar: #091c18;
      --bg-card: #0e2722;
      --bg-card-subtle: #163630;
      --bg-input: #081a16;
      --border-card: #1f4d43;
      --border-subtle: #12332c;

      --text-main: #edfcf6;
      --text-muted: #8ec7b9;
      --text-dim: #52857a;

      --accent-blue: #059669;
      --accent-cyan: #10b981;  /* Verde Esmeralda Radiológico */
      --accent-green: #34d399; /* Menta nominal */
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;

      --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }

    /* 🌊 TEMA ALTERNATIVO: AZUL ABISAL (Deep Navy) */
    [data-theme="navy"] {
      --bg-page: #07111e;
      --bg-sidebar: #0b1a2e;
      --bg-card: #10223b;
      --bg-card-subtle: #172f50;
      --bg-input: #091626;
      --border-card: #20416b;
      --border-subtle: #142a46;
      --text-main: #f0f6fc;
      --text-muted: #93b0d4;
      --text-dim: #5b7b9e;
      --accent-blue: #3b82f6;
      --accent-cyan: #38bdf8;
      --accent-green: #22c55e;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
    }

    /* 🍇 TEMA ALTERNATIVO: CIRUELA OBSIDIANA (Deep Plum) */
    [data-theme="plum"] {
      --bg-page: #110b17;
      --bg-sidebar: #181021;
      --bg-card: #21162d;
      --bg-card-subtle: #2d1f3d;
      --bg-input: #150e1e;
      --border-card: #433157;
      --border-subtle: #271c33;
      --text-main: #f5f3ff;
      --text-muted: #c4b5fd;
      --text-dim: #8572a8;
      --accent-blue: #8b5cf6;
      --accent-cyan: #38bdf8;
      --accent-green: #34d399;
      --accent-amber: #fbbf24;
      --accent-red: #ef4444;
    }

    /* ⚙️ TEMA ALTERNATIVO: TITANIO PIZARRA (Steel Slate) */
    [data-theme="titanio"] {
      --bg-page: #0f1319;
      --bg-sidebar: #151a23;
      --bg-card: #1b212c;
      --bg-card-subtle: #242c3a;
      --bg-input: #12161f;
      --border-card: #2e3849;
      --border-subtle: #202733;
      --text-main: #f1f5f9;
      --text-muted: #94a3b8;
      --text-dim: #64748b;
      --accent-blue: #4f86f7;
      --accent-cyan: #38bdf8;
      --accent-green: #22c55e;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
    }

    /* ⚡ TEMA ALTERNATIVO: ÁMBAR SCADA (Warm Amber) */
    [data-theme="ambar"] {
      --bg-page: #0a0b0d;
      --bg-sidebar: #111216;
      --bg-card: #16181f;
      --bg-card-subtle: #22252e;
      --bg-input: #0e1014;
      --border-card: #343744;
      --border-subtle: #20222a;
      --text-main: #f8fafc;
      --text-muted: #a1a1aa;
      --text-dim: #71717a;
      --accent-blue: #d97706;
      --accent-cyan: #f59e0b;
      --accent-green: #10b981;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    html {
      width: 100%;
      height: 100%;
      overflow: hidden;
      margin: 0;
      padding: 0;
    }

    body {
      width: 100%;
      height: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
      font-family: var(--font-sans);
      background-color: var(--bg-page);
      color: var(--text-main);
      font-size: 13px;
      line-height: 1.45;
      display: flex;
    }

    /* ================= SIDEBAR ================= */
    .sidebar {
      width: 270px;
      background-color: var(--bg-sidebar);
      border-right: 1px solid var(--border-card);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
    }

    .sidebar-brand {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .badge-nasa {
      background: linear-gradient(135deg, #047857, #10b981);
      color: #ffffff;
      font-family: var(--font-mono);
      font-weight: 900;
      font-size: 14px;
      padding: 5px 10px;
      border-radius: 6px;
      letter-spacing: 1px;
    }

    .brand-text h2 { font-size: 13.5px; font-weight: 700; color: #ffffff; }
    .brand-text span { font-size: 11px; color: var(--accent-cyan); font-family: var(--font-mono); }

    .plant-status {
      margin: 14px 16px;
      padding: 10px 12px;
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.3);
      border-radius: 6px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--accent-green);
      box-shadow: 0 0 8px var(--accent-green);
      animation: pulse-dot 2s infinite ease-in-out;
    }

    @keyframes pulse-dot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }

    .status-text {
      font-size: 11.5px;
      font-weight: 700;
      color: #34d399;
      letter-spacing: 0.5px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .sidebar-menu {
      flex: 1;
      padding: 10px 12px;
      overflow-y: auto;
    }

    .menu-header {
      font-size: 10px;
      font-weight: 800;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 8px 10px 4px;
    }

    .menu-btn {
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 9px 12px;
      margin-bottom: 4px;
      background: transparent;
      border: 1px solid transparent;
      border-radius: 6px;
      color: var(--text-muted);
      font-size: 12.5px;
      font-weight: 500;
      text-align: left;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .menu-btn:hover {
      background: var(--bg-card-subtle);
      color: #ffffff;
      border-color: var(--border-subtle);
    }

    .menu-btn.active {
      background: rgba(6, 182, 212, 0.15);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
      font-weight: 700;
    }

    .menu-badge {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-size: 10px;
      font-family: var(--font-mono);
      padding: 2px 6px;
      border-radius: 10px;
    }

    .sidebar-user {
      padding: 14px 18px;
      border-top: 1px solid var(--border-card);
      background: var(--bg-page);
      font-size: 12px;
    }

    .user-name { font-weight: 700; color: #ffffff; }
    .user-role { color: var(--text-dim); font-size: 10.5px; font-family: var(--font-mono); }

    .session-indicator {
      margin-top: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 10.5px;
      color: var(--text-muted);
      font-family: var(--font-mono);
    }

    .session-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--accent-green);
    }

    /* ================= MAIN CONTENT ================= */
    .main-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow-x: hidden !important;
      overflow-y: hidden;
      background: var(--bg-page);
    }

    .topbar {
      height: 52px;
      background: var(--bg-card);
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      flex-shrink: 0;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .theme-switcher-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 11px;
      margin-left: 10px;
    }
    .theme-pill {
      color: var(--text-muted);
      text-decoration: none;
      padding: 2px 7px;
      border-radius: 4px;
      transition: all 0.12s ease;
      font-family: var(--font-mono);
      font-size: 10.5px;
    }
    .theme-pill:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.08);
    }
    .theme-pill.active {
      background: var(--accent-cyan);
      color: #000 !important;
      font-weight: 700;
    }

    .topbar-title {
      font-size: 14px;
      font-weight: 700;
      color: #ffffff;
    }

    .shift-tag {
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 11.5px;
      padding: 3px 10px;
      border-radius: 4px;
    }

    .btn-new-shift {
      background: rgba(6, 182, 212, 0.15);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      padding: 4px 10px;
      border-radius: 4px;
      cursor: pointer;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
      transition: all 0.15s ease;
    }
    .btn-new-shift:hover { background: var(--accent-cyan); color: #000; }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 18px;
      font-family: var(--font-mono);
      font-size: 12px;
    }

    .clock-pill {
      color: var(--accent-cyan);
      background: var(--bg-page);
      padding: 4px 12px;
      border-radius: 4px;
      border: 1px solid var(--border-card);
      font-weight: 700;
    }

    .workspace {
      flex: 1;
      overflow-y: auto;
      padding: 22px 28px 40px;
    }

    /* ================= KPIS SUPERIORES (6 TARJETAS COMPLETAS) ================= */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 14px;
      margin-bottom: 22px;
    }

    .kpis-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .kpis-title {
      font-size: 11px;
      font-weight: 800;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .btn-update-all-kpis {
      background: rgba(6, 182, 212, 0.12);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }
    .btn-update-all-kpis:hover {
      background: var(--accent-cyan);
      color: #000;
    }

    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 12px 14px;
      position: relative;
      overflow: hidden;
      cursor: pointer;
      transition: transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
      user-select: none;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
      border-color: var(--accent-cyan);
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.5);
    }

    .kpi-top-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 6px;
    }

    .kpi-action-icon {
      font-size: 10px;
      font-family: var(--font-mono);
      color: var(--text-dim);
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2px 6px;
      border-radius: 4px;
      transition: all 0.15s ease;
    }

    .kpi-card:hover .kpi-action-icon {
      color: var(--accent-cyan);
      border-color: var(--accent-cyan);
      background: rgba(6, 182, 212, 0.15);
      font-weight: 700;
    }

    .kpi-multi-values {
      display: grid;
      grid-template-columns: 1.1fr 1fr 1fr;
      gap: 6px;
      align-items: end;
      margin-top: 3px;
    }

    .kpi-sub-item {
      display: flex;
      flex-direction: column;
    }

    .kpi-sub-label {
      font-size: 9px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }

    .kpi-sub-val-row {
      display: flex;
      align-items: baseline;
      gap: 3px;
    }

    .kpi-sub-val {
      font-size: 16.5px;
      font-weight: 800;
      font-family: var(--font-mono);
      line-height: 1.1;
    }

    .kpi-sub-unit {
      font-size: 9px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }

    .kpi-modal-tabs {
      display: flex;
      gap: 6px;
      margin-bottom: 16px;
      border-bottom: 1px solid var(--border-card);
      padding-bottom: 8px;
    }

    .kpi-tab-btn {
      background: transparent;
      border: 1px solid transparent;
      color: var(--text-muted);
      font-size: 11.5px;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.15s ease;
    }

    .kpi-tab-btn:hover {
      background: var(--bg-card-subtle);
      color: #ffffff;
    }

    .kpi-tab-btn.active {
      background: rgba(6, 182, 212, 0.15);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
      font-weight: 700;
    }

    /* ================= SEARCHABLE DROPDOWN (BUSCADOR DE RECINTOS TIPO SELECT2) ================= */
    .searchable-select-wrapper {
      position: relative;
      width: 100%;
    }

    .searchable-dropdown {
      display: none;
      position: absolute;
      top: calc(100% + 4px);
      left: 0;
      right: 0;
      max-height: 240px;
      overflow-y: auto;
      background: #0f172a;
      border: 1px solid var(--accent-cyan);
      border-radius: 6px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.85);
      z-index: 500;
    }

    .searchable-dropdown.open {
      display: block;
    }

    .searchable-item {
      padding: 8px 12px;
      font-size: 12px;
      color: #f1f5f9;
      cursor: pointer;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: background 0.1s ease;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .searchable-item:last-child {
      border-bottom: none;
    }

    .searchable-item:hover,
    .searchable-item.highlighted {
      background: rgba(6, 182, 212, 0.22);
      color: #ffffff;
    }

    .searchable-item mark {
      background: transparent;
      color: var(--accent-cyan);
      font-weight: 800;
      text-decoration: underline;
    }

    .searchable-empty {
      padding: 12px;
      font-size: 11.5px;
      color: var(--text-dim);
      text-align: center;
      font-style: italic;
    }

    /* ================= VANILLA DARK DATEPICKER & TIMEPICKER ================= */
    .latam-datepicker-popup,
    .latam-timepicker-popup {
      display: none;
      position: fixed !important;
      background: #0f172a !important;
      border: 1px solid var(--accent-cyan) !important;
      border-radius: 8px;
      padding: 12px;
      box-shadow: 0 14px 35px rgba(0, 0, 0, 0.95);
      z-index: 99999 !important;
      width: 260px;
      user-select: none;
    }

    .dp-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .dp-month-title {
      font-size: 12px;
      font-weight: 700;
      color: #ffffff;
      font-family: var(--font-sans);
    }

    .dp-nav-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-weight: bold;
      width: 24px;
      height: 24px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
    }
    .dp-nav-btn:hover {
      background: var(--accent-cyan);
      color: #000;
    }

    .dp-grid-weekdays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      text-align: center;
      font-size: 10px;
      font-weight: 700;
      color: var(--text-dim);
      margin-bottom: 6px;
    }

    .dp-grid-days {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 3px;
    }

    .dp-day-cell {
      height: 28px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-family: var(--font-mono);
      color: #e2e8f0;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.12s ease;
    }

    .dp-day-cell:hover {
      background: rgba(6, 182, 212, 0.25);
      color: var(--accent-cyan);
    }

    .dp-day-cell.other-month {
      color: #475569;
      opacity: 0.5;
    }

    .dp-day-cell.today {
      border: 1px solid rgba(6, 182, 212, 0.6);
      font-weight: 700;
    }

    .dp-day-cell.selected {
      background: var(--accent-cyan) !important;
      color: #000000 !important;
      font-weight: 800;
    }

    .dp-quick-footer {
      display: flex;
      justify-content: space-between;
      gap: 6px;
      margin-top: 10px;
      padding-top: 8px;
      border-top: 1px solid var(--border-card);
    }

    .dp-quick-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-size: 10px;
      font-weight: 700;
      padding: 4px 8px;
      border-radius: 4px;
      cursor: pointer;
      flex: 1;
      text-align: center;
      transition: all 0.12s ease;
    }
    .dp-quick-btn:hover {
      background: rgba(6, 182, 212, 0.2);
      border-color: var(--accent-cyan);
    }

    /* TIMEPICKER POPUP */
    .tp-presets {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 4px;
      margin-bottom: 8px;
    }

    .tp-preset-btn {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      font-family: var(--font-mono);
      font-size: 10px;
      padding: 4px 2px;
      border-radius: 4px;
      text-align: center;
      cursor: pointer;
    }
    .tp-preset-btn:hover {
      background: rgba(6, 182, 212, 0.2);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
    }

    .tp-selector-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 6px;
    }

    .tp-col-title {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
      margin-bottom: 4px;
      text-align: center;
    }

    .tp-col-scroll {
      max-height: 120px;
      overflow-y: auto;
      border: 1px solid var(--border-card);
      border-radius: 4px;
      background: #090d16;
    }

    .tp-item {
      padding: 4px;
      text-align: center;
      font-family: var(--font-mono);
      font-size: 11px;
      color: #94a3b8;
      cursor: pointer;
    }
    .tp-item:hover, .tp-item.active {
      background: var(--accent-cyan);
      color: #000;
      font-weight: 700;
    }

    .kpi-card::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 3px;
      background: var(--card-color, var(--accent-blue));
    }

    .kpi-label {
      font-size: 10.5px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    .kpi-value-row {
      display: flex;
      align-items: baseline;
      gap: 6px;
    }

    .kpi-value {
      font-size: 22px;
      font-weight: 800;
      font-family: var(--font-mono);
      color: #ffffff;
    }

    .kpi-unit {
      font-size: 11px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }

    .kpi-footer {
      margin-top: 6px;
      font-size: 10.5px;
      color: var(--text-dim);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* ================= SECCIÓN / PANELES ACTIVOS ================= */
        /* ================= ANIMACIONES CINEMATOGRÁFICAS HUD / SCADA (EFECTO 'GUAU') ================= */
    .section-panel {
      display: none;
    }

    .section-panel.active {
      display: block;
      animation: cyber-hologram-assemble 0.42s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      position: relative;
      perspective: 1000px;
    }

    /* Ensamblaje holográfico con barrido de matriz cuántica */
    @keyframes cyber-hologram-assemble {
      0% {
        opacity: 0;
        transform: perspective(900px) rotateX(5deg) translateY(20px) scale(0.98);
        filter: blur(8px) brightness(1.7) contrast(1.3);
        box-shadow: 0 0 40px rgba(16, 185, 129, 0.55);
      }
      30% {
        opacity: 0.75;
        filter: blur(2.5px) brightness(1.35) contrast(1.15);
      }
      65% {
        transform: perspective(900px) rotateX(-1deg) translateY(-2px) scale(1.002);
        filter: blur(0.5px) brightness(1.1);
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.25);
      }
      100% {
        opacity: 1;
        transform: perspective(900px) rotateX(0deg) translateY(0) scale(1);
        filter: none;
        box-shadow: none;
      }
    }

    /* Haz de barrido láser cian acotado con transform sin desbordar el viewport */
    .section-panel {
      overflow-x: hidden;
    }

    .section-panel.active {
      overflow-x: hidden;
    }

    .section-panel .panel-box {
      overflow: hidden !important;
      position: relative !important;
    }

    .section-panel.active .panel-box::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 3px;
      background: linear-gradient(90deg, transparent 0%, rgba(16, 185, 129, 0.4) 20%, var(--accent-cyan) 50%, #ffffff 70%, var(--accent-cyan) 85%, transparent 100%);
      transform: translateX(-100%);
      animation: cyber-laser-sweep 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
      box-shadow: 0 0 12px var(--accent-cyan), 0 0 24px var(--accent-cyan);
      z-index: 10;
      pointer-events: none;
    }

    @keyframes cyber-laser-sweep {
      0% { transform: translateX(-100%); opacity: 1; }
      70% { opacity: 1; }
      100% { transform: translateX(100%); opacity: 0; }
    }

    /* Escaneo de rejilla táctica cyber-grid al activar panel */
    .section-panel.active::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(rgba(16, 185, 129, 0.04) 1px, transparent 1px),
                  linear-gradient(90deg, rgba(16, 185, 129, 0.04) 1px, transparent 1px);
      background-size: 24px 24px;
      pointer-events: none;
      opacity: 0;
      animation: cyber-grid-fade 0.5s ease-out forwards;
      border-radius: 8px;
    }

    @keyframes cyber-grid-fade {
      0% { opacity: 0.8; }
      60% { opacity: 0.4; }
      100% { opacity: 0; }
    }

    /* Botón del Sidebar con pulso de shockwave y resplandor HUD */
    .menu-btn {
      transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
    }

    .menu-btn.active {
      background: rgba(16, 185, 129, 0.18) !important;
      border-color: var(--accent-cyan) !important;
      color: #ffffff !important;
      font-weight: 700;
      box-shadow: inset 4px 0 0 var(--accent-cyan), 0 0 20px rgba(16, 185, 129, 0.35);
      transform: translateX(6px);
    }

    .menu-btn.active::after {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.12), transparent);
      animation: cyber-btn-shimmer 0.7s ease-out forwards;
      pointer-events: none;
    }

    @keyframes cyber-btn-shimmer {
      0% { transform: translateX(-100%); }
      100% { transform: translateX(100%); }
    }

    /* Botón X de limpieza en inputs */
    .btn-clear-ctrl {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      background: transparent;
      border: none;
      color: var(--text-dim);
      font-size: 16px;
      line-height: 1;
      cursor: pointer;
      padding: 3px 6px;
      border-radius: 4px;
      display: none;
      z-index: 5;
      transition: all 0.15s ease;
    }

    .btn-clear-ctrl:hover {
      color: #ef4444;
      background: rgba(239, 68, 68, 0.2);
    }

    .panel-box {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      overflow: hidden;
      margin-bottom: 20px;
    }

    .panel-header {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(255, 255, 255, 0.02);
    }

    .panel-title {
      font-size: 13.5px;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .panel-body {
      padding: 18px;
    }

    /* ================= BARRA DE ENTRADA RÁPIDA (RUTINA) ================= */
    .entry-bar {
      display: grid;
      grid-template-columns: 120px 85px 2fr 100px 100px 100px 2.2fr auto;
      gap: 10px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      padding: 12px 14px;
      border-radius: 6px;
      margin-bottom: 18px;
      align-items: end;
    }

    .field-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .field-label {
      font-size: 10.5px;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
    }

    .field-control {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: 4px;
      color: #ffffff;
      font-size: 12.5px;
      padding: 6px 9px;
      outline: none;
      transition: border-color 0.15s ease;
      width: 100%;
    }

    .field-control:focus {
      border-color: var(--accent-cyan);
      box-shadow: 0 0 0 2px rgba(6, 182, 212, 0.2);
    }

    .field-control.mono {
      font-family: var(--font-mono);
      font-size: 12px;
    }

    .btn-register {
      background: var(--accent-cyan);
      border: none;
      color: #000000;
      font-family: var(--font-mono);
      font-weight: 800;
      font-size: 11.5px;
      padding: 8px 14px;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      height: 31px;
      transition: background 0.15s ease;
      white-space: nowrap;
    }
    .btn-register:hover { background: #22d3ee; }

    /* ================= TABLAS DE DATOS ================= */
    .data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 12px;
      text-align: left;
    }

    .data-table th {
      background: var(--bg-sidebar);
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 10px;
      letter-spacing: 0.5px;
      padding: 10px 12px;
      border-bottom: 2px solid var(--border-card);
    }

    /* Contenedor con scroll y encabezados fijos (Sticky Table Headers) */
    .table-scroll-wrapper {
      max-height: calc(100vh - 430px);
      min-height: 280px;
      overflow-y: auto;
      overflow-x: auto;
      border: 1px solid var(--border-card);
      border-radius: 6px;
      background: var(--bg-card);
      position: relative;
    }

    .table-scroll-wrapper::-webkit-scrollbar {
      width: 7px;
      height: 7px;
    }
    .table-scroll-wrapper::-webkit-scrollbar-track {
      background: rgba(4, 15, 13, 0.8);
    }
    .table-scroll-wrapper::-webkit-scrollbar-thumb {
      background: var(--border-card);
      border-radius: 4px;
    }
    .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
      background: var(--accent-cyan);
    }

    .table-scroll-wrapper table.data-table thead th {
      position: sticky !important;
      top: 0 !important;
      z-index: 20 !important;
      background: #091c18 !important;
      box-shadow: 0 1px 0 var(--border-card);
    }

    /* Filas intercaladas (Zebra Striping de Alto Contraste) */
    .data-table tbody tr:nth-child(odd) td {
      background-color: rgba(14, 39, 34, 0.45);
    }

    .data-table tbody tr:nth-child(even) td {
      background-color: rgba(25, 66, 58, 0.65);
    }

    .data-table td {
      padding: 9px 12px;
      border-bottom: 1px solid rgba(31, 77, 67, 0.45);
      color: var(--text-main);
      transition: background-color 0.1s ease;
    }

    /* Resaltado interactivo de fila completa */
    .data-table tbody tr:hover td {
      background-color: rgba(16, 185, 129, 0.22) !important;
      color: #ffffff;
    }

    .data-table td.num {
      font-family: var(--font-mono);
      text-align: right;
    }

    .badge-amber {
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: var(--accent-amber);
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
    }

    .badge-red {
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid rgba(239, 68, 68, 0.5);
      color: var(--accent-red);
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 700;
    }

            /* Estilos para botón de apertura y filas clickeables de actas */
    .btn-acta-open {
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 3px;
      transition: all 0.18s ease;
      white-space: nowrap;
    }

    .btn-acta-open:hover {
      background: var(--accent-cyan);
      color: #02120e;
      border-color: var(--accent-cyan);
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.45);
      transform: scale(1.04);
    }

    .acta-clickable-row {
      cursor: pointer;
      transition: background-color 0.15s ease;
    }

    .acta-clickable-row:hover td {
      background-color: rgba(16, 185, 129, 0.08) !important;
    }

    /* Pill interactiva de Historial en filas de medición guardadas */
    .btn-recinto-hist-pill {
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: var(--accent-cyan);
      font-family: var(--font-mono);
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      white-space: nowrap;
    }

    .btn-recinto-hist-pill:hover {
      background: var(--accent-cyan);
      color: #02120e;
      border-color: var(--accent-cyan);
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.45);
      transform: scale(1.04);
    }

    .recinto-link {
      cursor: pointer;
      transition: all 0.15s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .recinto-link:hover {
      color: var(--accent-cyan) !important;
      text-shadow: 0 0 8px rgba(16, 185, 129, 0.5);
    }

    .btn-del {
      background: transparent;
      border: 1px solid var(--border-card);
      color: var(--text-dim);
      border-radius: 4px;
      padding: 3px 7px;
      font-size: 11px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-del:hover {
      background: rgba(239, 68, 68, 0.2);
      border-color: var(--accent-red);
      color: var(--accent-red);
    }

    
    /* ================= ANIMACIONES FUTURISTAS & EFECTOS HUD ================= */
    @keyframes hud-scanline {
      0% { transform: translateX(-100%); opacity: 0; }
      50% { opacity: 0.9; }
      100% { transform: translateX(250%); opacity: 0; }
    }

    .kpi-card::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, transparent, #34d399, #10b981, transparent);
      animation: hud-scanline 5s infinite ease-in-out;
      pointer-events: none;
    }

    @keyframes cyber-pulse {
      0%, 100% { box-shadow: 0 0 6px rgba(16, 185, 129, 0.4); transform: scale(1); }
      50% { box-shadow: 0 0 16px rgba(52, 211, 153, 0.85), 0 0 4px #10b981; transform: scale(1.08); }
    }

    .status-dot {
      animation: cyber-pulse 2.2s infinite ease-in-out !important;
    }

    .session-dot {
      animation: cyber-pulse 2.8s infinite ease-in-out !important;
    }

    /* Brackets tácticos en esquinas de paneles */
    .panel-box {
      position: relative;
    }

    .panel-box::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 10px; height: 10px;
      border-top: 2px solid var(--accent-cyan);
      border-left: 2px solid var(--accent-cyan);
      pointer-events: none;
      opacity: 0.6;
    }

    .panel-box::after {
      content: "";
      position: absolute;
      top: 0; right: 0;
      width: 10px; height: 10px;
      border-top: 2px solid var(--accent-cyan);
      border-right: 2px solid var(--accent-cyan);
      pointer-events: none;
      opacity: 0.6;
    }

    /* Efecto shimmer en botón primario */
    .btn-register {
      position: relative;
      overflow: hidden;
      box-shadow: 0 0 10px rgba(16, 185, 129, 0.25);
      transition: all 0.2s ease;
    }
    .btn-register:hover {
      box-shadow: 0 0 18px rgba(52, 211, 153, 0.6);
      transform: translateY(-1px);
    }
    .btn-register::after {
      content: "";
      position: absolute;
      top: -50%; left: -60%;
      width: 40%; height: 200%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
      transform: rotate(25deg);
      transition: all 0.6s ease;
    }
    .btn-register:hover::after {
      left: 140%;
    }

    @keyframes row-slide-in {
      from { opacity: 0; transform: translateY(-8px); background: rgba(16, 185, 129, 0.25); }
      to { opacity: 1; transform: translateY(0); background: transparent; }
    }

    .data-table tbody tr.new-row {
      animation: row-slide-in 0.45s ease-out;
    }

    /* Forzar mayúsculas en nombres y opciones */
    .user-name, #topbar-jefe-turno, #sidebar-user-name, select option, #tasks-table td {
      text-transform: uppercase;
    }

    
    /* ================= SYNC ENGINE (OUTBOX OFFLINE & AUTO-RETRY) ================= */
    .sync-pill {
      font-size: 11px;
      font-family: var(--font-mono);
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 4px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      user-select: none;
    }
    .sync-pill.synced {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #34d399;
    }
    .sync-pill.pending {
      background: rgba(245, 158, 11, 0.2);
      border: 1px solid var(--accent-amber);
      color: var(--accent-amber);
      animation: cyber-pulse 2s infinite ease-in-out;
    }
    .sync-pill.syncing {
      background: rgba(6, 182, 212, 0.2);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
    }
    .sync-badge {
      font-size: 10px;
      font-family: var(--font-mono);
      padding: 1px 5px;
      border-radius: 3px;
    }
    .sync-badge.synced {
      color: #34d399;
    }
    .sync-badge.pending {
      color: var(--accent-amber);
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.3);
    }

    /* ================= MODALES Y TOASTS ================= */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.75);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      width: 540px;
      max-width: 90vw;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.8);
      overflow: hidden;
    }

    .modal-header {
      padding: 14px 20px;
      background: var(--bg-card-subtle);
      border-bottom: 1px solid var(--border-card);
      font-size: 13.5px;
      font-weight: 700;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .modal-header-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-modal-close {
      background: transparent;
      border: none;
      color: var(--text-dim);
      font-size: 16px;
      cursor: pointer;
      padding: 2px 6px;
      border-radius: 4px;
      line-height: 1;
      transition: all 0.15s ease;
    }

    .btn-modal-close:hover {
      background: rgba(255, 255, 255, 0.1);
      color: #ffffff;
    }

    .modal-body {
      padding: 20px;
      line-height: 1.5;
      font-size: 13px;
    }

    .modal-footer {
      padding: 14px 20px;
      background: var(--bg-card-subtle);
      border-top: 1px solid var(--border-card);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }

    .btn-modal-cancel {
      background: transparent;
      border: 1px solid var(--border-card);
      color: var(--text-muted);
      padding: 7px 14px;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
    }

    .btn-modal-confirm {
      background: var(--accent-cyan);
      border: none;
      color: #000;
      font-weight: 700;
      padding: 7px 16px;
      border-radius: 4px;
      font-size: 12px;
      cursor: pointer;
    }

    .toast-popup {
      display: none;
      position: fixed;
      bottom: 24px;
      right: 28px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--accent-cyan);
      color: #ffffff;
      padding: 12px 18px;
      border-radius: 6px;
      font-size: 12.5px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
      z-index: 2000;
    }
    /* ========================================================== */
    /* TOOLTIPS PERSONALIZADOS CYBERPUNK RADIOLÓGICOS             */
    /* ========================================================== */
    /* ========================================================== */
    /* BANNER Y MODAL DE RELEVO DE GUARDIA / PLANILLA PENDIENTE   */
    /* ========================================================== */
    .banner-relevo-guardia {
      background: rgba(245, 158, 11, 0.12);
      border-bottom: 1px solid rgba(245, 158, 11, 0.4);
      padding: 8px 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      font-size: 11.5px;
      font-family: var(--font-mono);
      animation: cyber-hologram-assemble 0.25s ease-out;
      position: sticky;
      top: 0;
      z-index: 90;
    }
    .banner-relevo-content {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #fffbeb;
      line-height: 1.4;
    }
    .banner-relevo-icon {
      font-size: 16px;
      flex-shrink: 0;
    }
    .banner-relevo-actions {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-shrink: 0;
    }
    .btn-banner-cierre {
      background: var(--accent-amber);
      color: #031510;
      border: none;
      padding: 4px 10px;
      border-radius: 5px;
      font-weight: 800;
      font-size: 11px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
    }
    .btn-banner-cierre:hover {
      background: #fbbf24;
      transform: translateY(-1px);
    }
    .btn-banner-detalles {
      background: rgba(245, 158, 11, 0.15);
      color: var(--accent-amber);
      border: 1px solid rgba(245, 158, 11, 0.4);
      padding: 4px 9px;
      border-radius: 5px;
      font-weight: 700;
      font-size: 11px;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .btn-banner-detalles:hover {
      background: rgba(245, 158, 11, 0.3);
    }

    .btn-handover-opt {
      width: 100%;
      text-align: left;
      padding: 10px 14px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1);
      display: flex;
      flex-direction: column;
      gap: 3px;
      border: 1px solid var(--border-card);
      background: rgba(0, 0, 0, 0.3);
      font-family: var(--font-sans);
    }
    .btn-handover-opt:hover {
      transform: translateX(3px);
    }
    .btn-handover-opt.primary {
      border-color: rgba(245, 158, 11, 0.6);
      background: rgba(245, 158, 11, 0.12);
    }
    .btn-handover-opt.primary:hover {
      background: rgba(245, 158, 11, 0.22);
      border-color: var(--accent-amber);
    }
    .btn-handover-opt.secondary {
      border-color: rgba(16, 185, 129, 0.4);
      background: rgba(16, 185, 129, 0.08);
    }
    .btn-handover-opt.secondary:hover {
      background: rgba(16, 185, 129, 0.18);
      border-color: var(--accent-cyan);
    }
    .btn-handover-opt.tertiary {
      border-color: var(--border-card);
      background: rgba(255, 255, 255, 0.03);
    }
    .btn-handover-opt.tertiary:hover {
      background: rgba(255, 255, 255, 0.07);
    }
    .btn-handover-opt .opt-title {
      font-weight: 800;
      font-size: 12px;
      color: #ffffff;
    }
    .btn-handover-opt.primary .opt-title {
      color: var(--accent-amber);
    }
    .btn-handover-opt.secondary .opt-title {
      color: var(--accent-cyan);
    }
    .btn-handover-opt .opt-desc {
      font-size: 11px;
      color: var(--text-muted);
      line-height: 1.35;
    }

    .kpi-multi-values.two-cols {
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

        .btn-ghost {
      background: rgba(245, 158, 11, 0.08) !important;
      color: var(--accent-amber) !important;
      border: 1px solid rgba(245, 158, 11, 0.4) !important;
      border-radius: 6px;
      cursor: pointer;
      font-family: var(--font-sans);
      transition: all 0.15s ease;
    }
    .btn-ghost:hover {
      background: rgba(245, 158, 11, 0.2) !important;
      border-color: var(--accent-amber) !important;
    }

    .kpi-field-alert {
      display: none;
      flex-direction: column;
      gap: 6px;
      background: rgba(245, 158, 11, 0.1);
      border: 1px solid rgba(245, 158, 11, 0.45);
      border-left: 4px solid var(--accent-amber);
      color: #fffbeb;
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 11px;
      font-family: var(--font-mono);
      line-height: 1.4;
      margin-top: 10px;
      margin-bottom: 12px;
      animation: cyber-hologram-assemble 0.22s ease-out;
    }

    .kpi-field-alert.danger {
      background: rgba(239, 68, 68, 0.12);
      border-color: rgba(239, 68, 68, 0.55);
      border-left-color: var(--accent-red);
      color: #fee2e2;
    }

    .field-control.input-alert-spike {
      border-color: var(--accent-amber) !important;
      box-shadow: 0 0 10px rgba(245, 158, 11, 0.4) !important;
    }

    .field-control.input-alert-limit {
      border-color: var(--accent-red) !important;
      box-shadow: 0 0 10px rgba(239, 68, 68, 0.5) !important;
    }
    .kpi-pending-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      background: rgba(245, 158, 11, 0.12);
      border: 1px dashed rgba(245, 158, 11, 0.6);
      color: #fbbf24;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 800;
      font-family: var(--font-mono);
      letter-spacing: 0.3px;
    }

    .kpi-card.is-pending {
      border-color: rgba(245, 158, 11, 0.45) !important;
      background: linear-gradient(180deg, rgba(245, 158, 11, 0.04) 0%, var(--bg-card) 100%);
    }

    .kpi-card.is-pending .kpi-label {
      color: #fde68a !important;
    }

    .kpi-card.is-pending .kpi-action-icon {
      border-color: rgba(245, 158, 11, 0.5);
      color: #fbbf24;
      background: rgba(245, 158, 11, 0.15);
    }

    #cyber-tooltip {
      position: fixed;
      z-index: 999999;
      pointer-events: none;
      opacity: 0;
      transform: translateY(4px);
      transition: opacity 0.12s cubic-bezier(0.4, 0, 0.2, 1), transform 0.12s cubic-bezier(0.4, 0, 0.2, 1);
      background: rgba(8, 26, 22, 0.96);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid var(--accent-cyan, #10b981);
      color: var(--text-main, #edfcf6);
      font-family: var(--font-mono, monospace);
      font-size: 11px;
      line-height: 1.45;
      padding: 6px 10px;
      border-radius: 5px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.8), 0 0 10px rgba(16, 185, 129, 0.25);
      max-width: 320px;
      word-wrap: break-word;
      white-space: normal;
      display: block;
    }

    #cyber-tooltip.visible {
      opacity: 1;
      transform: translateY(0);
    }

    #cyber-tooltip.alert {
      border-color: var(--accent-amber, #f59e0b);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.85), 0 0 14px rgba(245, 158, 11, 0.4);
      background: rgba(26, 18, 7, 0.97);
      color: #fffbeb;
    }

    #cyber-tooltip.danger {
      border-color: var(--accent-red, #ef4444);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.85), 0 0 14px rgba(239, 68, 68, 0.45);
      background: rgba(28, 10, 10, 0.97);
      color: #fef2f2;
    }
  </style>
</head>
<body>

  <!-- ================= SIDEBAR FIJO (HMI SYR) ================= -->
  <aside class="sidebar" <?php if ($hay_discrepancia_guardia) echo 'style="pointer-events: none; opacity: 0.35;"'; ?>>
    <div class="sidebar-brand">
      <div class="badge-nasa">NA-SA</div>
      <div class="brand-text">
        <h2>Registro Radiológico</h2>
        <span>SyR • Central Nuclear Embalse</span>
      </div>
    </div>

    <div class="plant-status">
      <div class="status-dot"></div>
      <div class="status-text" id="sidebar-plant-status"><?php echo htmlspecialchars($estado_planta, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <nav class="sidebar-menu">
      <div class="menu-header">Módulos de Turno</div>
      <button class="menu-btn active" data-section="sec-rutina">
        <span>📋 RELEVAMIENTO DE RUTINA</span>
        <span class="menu-badge" id="menu-badge-rutina"><?php echo count($relevamientos_rutina_iniciales); ?></span>
      </button>
      <button class="menu-btn" data-section="sec-gaseosos">
        <span>💨 EMISIÓN EFLUENTES GASEOSOS</span>
        <span class="menu-badge">RO 8.4%</span>
      </button>
      <button class="menu-btn" data-section="sec-liquidos">
        <span>💧 EMISIÓN DE EFLUENTES LÍQUIDOS</span>
        <span class="menu-badge">Nominal</span>
      </button>
      <button class="menu-btn" data-section="sec-contaminacion">
        <span>🧽 CONT. SUPERFICIAL &amp; DRYERS</span>
        <span class="menu-badge">0</span>
      </button>
      <button class="menu-btn" data-section="sec-actas" id="btn-sidebar-actas">
        <span>📖 LIBRO DE ACTAS &amp; TAREAS PTR</span>
        <span class="menu-badge" id="menu-badge-actas">2</span>
      </button>

      <div class="menu-header" style="margin-top: 14px;">Administración &amp; Cierre</div>
      <button class="menu-btn" data-section="sec-historial">
        <span>📁 HISTORIAL DE PLANILLAS</span>
      </button>
      <button class="menu-btn" data-section="sec-cierre">
        <span>🔒 CIERRE DE TURNO</span>
      </button>
    </nav>

    <div class="sidebar-user">
      <!-- Turno Activo & Botón de Configuración -->
      <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-bottom: 10px; background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 6px 10px;">
        <div style="display: flex; flex-direction: column; min-width: 0;">
          <span style="font-size: 8.5px; text-transform: uppercase; color: var(--text-dim); font-weight: 700; letter-spacing: 0.5px;">Turno de Guardia</span>
          <span class="shift-tag" id="sidebar-shift-tag" style="border: none; background: transparent; padding: 0; font-size: 11px; font-weight: 700; color: var(--accent-cyan); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <button type="button" class="btn-new-shift" onclick="openShiftModal()" style="padding: 3px 8px; font-size: 10.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; height: 26px; border-radius: 4px; white-space: nowrap; flex-shrink: 0;" title="Configurar turno, jefe u oficial">
          ⚙️ <span>Configurar</span>
        </button>
      </div>

      <div class="user-name" id="sidebar-user-name"><?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?></div>
      <div class="user-role">Oficial de Radioprotección</div>
      <div class="session-indicator">
        <div class="session-dot" id="session-status-dot"></div>
        <span id="session-status-text">Sistema en línea</span>
      </div>
    </div>
  </aside>

  <!-- ================= CONTENIDO PRINCIPAL ================= -->
  <main class="main-content">
    
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title" id="current-section-title">RELEVAMIENTO DE RUTINA</span>
        <div class="theme-switcher-bar" id="theme-switcher-bar">
        <span style="font-size:10px; color:var(--text-dim); font-weight:700; text-transform:uppercase;">🎨 FONDO:</span>
        <button type="button" class="theme-pill active" data-theme="petrol" onclick="setAppTheme('petrol')">🌲 Verde Radiológico (Oficial)</button>
        <button type="button" class="theme-pill" data-theme="navy" onclick="setAppTheme('navy')">🌊 Azul Abisal</button>
        <button type="button" class="theme-pill" data-theme="plum" onclick="setAppTheme('plum')">🍇 Ciruela</button>
        <button type="button" class="theme-pill" data-theme="titanio" onclick="setAppTheme('titanio')">⚙️ Titanio</button>
        <button type="button" class="theme-pill" data-theme="ambar" onclick="setAppTheme('ambar')">⚡ Ámbar</button>
      </div>
      
      
      </div>
      <div class="topbar-right">
        <div class="sync-pill synced" id="sync-status-indicator" onclick="SyncEngine.flush(true)" title="Estado de sincronización: Haga clic para forzar reintento">
          ☁️ Servidor sincronizado
        </div>
        <span>•</span>
        <span>Jefe: <strong style="color: #ffffff;" id="topbar-jefe-turno"><?php echo htmlspecialchars($jefe_turno_actual, ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <span>•</span>
        <span>Fecha: <strong style="color: #ffffff;" id="topbar-latam-date"><?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <div class="clock-pill" id="live-clock"><?php echo $hora_actual; ?>:00 hs</div>
      </div>
    </header>

    <!-- BANNER DE ADVERTENCIA: PLANILLA ANTERIOR SIN CERRAR -->
    <div id="banner-relevo-guardia" class="banner-relevo-guardia" style="display: <?php echo $hay_discrepancia_guardia ? 'flex' : 'none'; ?>;">
      <div class="banner-relevo-content">
        <span class="banner-relevo-icon">⚠️</span>
        <span class="banner-relevo-text">
          <strong>PLANILLA ANTERIOR SIN CERRAR:</strong> La planilla activa (#<span id="banner-plan-id"><?php echo $id_planilla_activa; ?></span>) pertenece a <strong><?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?></strong> (<em><?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?></em>). Para operar debe cerrarse definitivamente.
        </span>
      </div>
      <div class="banner-relevo-actions">
        <button type="button" class="btn-banner-cierre" onclick="mostrarPasoAdvertenciaCierre()">
          🔒 Cerrar Planilla Anterior y Abrir Nueva
        </button>
        <button type="button" class="btn-banner-detalles" onclick="verPlanillaSoloLectura()" title="Ver en modo consulta sin modificar datos">
          👁️ Modo Auditoría
        </button>
      </div>
    </div>

    <!-- WORKSPACE SCROLLABLE -->
    <div class="workspace" <?php if ($hay_discrepancia_guardia) echo 'style="filter: blur(5px); pointer-events: none; user-select: none; opacity: 0.25;"'; ?>>

      <!-- ================= BARRA SUPERIOR & 6 KPIS SUPERIORES ================= -->
      <div class="kpis-toolbar">
        <div class="kpis-title">
          <span>⚡ Métricas de Guardia &amp; Chimenea</span>
          <span style="color: var(--text-dim); font-size: 10px; font-weight: normal;">[ Haga clic en cualquier tarjeta para actualizarla directamente ]</span>
        </div>
        <button type="button" class="btn-update-all-kpis" onclick="openKpiModal('all')">
          <span>✏️ Actualizar Métricas de Chimenea</span>
        </button>
      </div>

      <section class="kpi-grid">
        
        <!-- KPI 1: Picos Radiológicos del Turno (Dosis, Tritio, Gases) -->
        <div class="kpi-card" style="--card-color: var(--accent-amber);" onclick="openKpiModal('dosis')" title="Haga clic para ver relevamientos de rutina">
          <div class="kpi-top-row">
            <span class="kpi-label">Picos Radiológicos</span>
            <span class="kpi-action-icon">📋 Ver</span>
          </div>
          <div class="kpi-multi-values">
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Dosis</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-max-dosis" style="color: var(--accent-amber);">24.0</span>
                <span class="kpi-sub-unit">µSv/h</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Tritio</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-max-tritio" style="color: var(--accent-cyan);">1.20</span>
                <span class="kpi-sub-unit">DAC</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Gases</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-max-gases" style="color: #c084fc;">0.00</span>
                <span class="kpi-sub-unit">DAC</span>
              </div>
            </div>
          </div>
          <div class="kpi-footer" id="kpi-max-recinto">📍 R-024 (PHT) • R-001</div>
        </div>

        <!-- KPI 2: RO Gaseoso -->
        <div class="kpi-card" style="--card-color: var(--accent-green);" onclick="openKpiModal('ro')" title="Haga clic para actualizar Restricción Operacional">
          <div class="kpi-top-row">
            <span class="kpi-label">% Restricción Operacional</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-ro-val" style="color: var(--accent-green);">8.42</span>
            <span class="kpi-unit">% RO</span>
          </div>
          <div class="kpi-footer" id="kpi-ro-footer">Chimenea en rango nominal</div>
        </div>

        <!-- KPI 3: Ventilación Chimenea (General y Reactor unificados) -->
        <div class="kpi-card" id="card-kpi-vent" style="--card-color: var(--accent-blue);" onclick="openKpiModal('tritio')" title="Haga clic para actualizar Ventilación General y Reactor">
          <div class="kpi-top-row">
            <span class="kpi-label">Ventilación Chimenea</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-multi-values two-cols">
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">General</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-vent-gral" style="color: #60a5fa;">1.45</span>
                <span class="kpi-sub-unit" id="kpi-vent-gral-unit">GBq</span>
              </div>
            </div>
            <div class="kpi-sub-item">
              <span class="kpi-sub-label">Reactor</span>
              <div class="kpi-sub-val-row">
                <span class="kpi-sub-val" id="kpi-vent-reactor" style="color: #22d3ee;">3.80</span>
                <span class="kpi-sub-unit" id="kpi-vent-reactor-unit">GBq</span>
              </div>
            </div>
          </div>
          <div class="kpi-footer" id="kpi-vent-footer">Caudal y extracción activa</div>
        </div>

        <!-- KPI 4: Condición Meteorológica -->
        <div class="kpi-card" id="card-kpi-met" style="--card-color: #f59e0b;" onclick="openKpiModal('met')" title="Haga clic para ver o actualizar Condición Meteorológica">
          <div class="kpi-top-row">
            <span class="kpi-label">🌤️ Meteorología</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-met-val" style="color: #f59e0b; font-size: 15px; font-weight: 800;">Sur • 14.5</span>
            <span class="kpi-unit" id="kpi-met-unit">km/h</span>
          </div>
          <div class="kpi-footer" id="kpi-met-footer">Pasquill Clase D (Neutral)</div>
        </div>

        <!-- KPI 5: Emisión C-14 (Métrica de Planta) -->
        <div class="kpi-card" style="--card-color: #a855f7;" onclick="openKpiModal('c14')" title="Haga clic para actualizar Emisión Carbono-14">
          <div class="kpi-top-row">
            <span class="kpi-label">Emisión Carbono-14</span>
            <span class="kpi-action-icon">✏️ Cargar</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-c14">12.4</span>
            <span class="kpi-unit">MBq</span>
          </div>
          <div class="kpi-footer" id="kpi-c14-footer">Muestreo acumulado turno</div>
        </div>

        <!-- KPI 6: Dedo Frío 73120 (Métrica de Planta con Últimos 5 Datos) -->
        <div class="kpi-card" style="--card-color: #ec4899;" onclick="openKpiModal('dedofrio')" title="Haga clic para ver histórico y actualizar Dedo Frío 73120">
          <div class="kpi-top-row">
            <span class="kpi-label">Dedo Frío 73120</span>
            <span class="kpi-action-icon">📊 Últimos 5</span>
          </div>
          <div class="kpi-value-row">
            <span class="kpi-value" id="kpi-dedo-frio">&lt; 12</span>
            <span class="kpi-unit">ppm</span>
          </div>
          <!-- Micro-barra visual de los últimos 5 registros criogénicos -->
          <div class="kpi-mini-history" style="display: flex; gap: 3px; margin: 2px 0 3px;">
            <span title="Hoy 04:15 (ALTUNA): < 12 ppm" style="background: rgba(236,72,153,0.3); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px; font-weight: 800; border: 1px solid rgba(236,72,153,0.5);">&lt;12</span>
            <span title="Ayer 20:30 (ROLHAUSER): < 10 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">&lt;10</span>
            <span title="Ayer 12:45 (GATICA): 15 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">15</span>
            <span title="Ayer 05:00 (CISTERNA): < 11 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">&lt;11</span>
            <span title="07/11 21:10 (PASCUAL): 14 ppm" style="background: rgba(236,72,153,0.15); color: #f472b6; font-family: var(--font-mono); font-size: 8.5px; padding: 1px 4px; border-radius: 3px;">14</span>
          </div>
          <div class="kpi-footer" id="kpi-dedo-frio-footer">Histórico 5 guardias nominal</div>
        </div>

      </section>

      <!-- ================= SECCIÓN 1: RELEVAMIENTO DE RUTINA ================= -->
      <section class="section-panel active" id="sec-rutina">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title">
              <span>📋</span> RELEVAMIENTO DE RUTINA
            </div>
            <span style="font-size: 11.5px; font-family: var(--font-mono); color: var(--text-muted);">
              [ Ingrese los valores y pulse <span style="color: var(--accent-cyan);">Enter</span> para registrar ]
            </span>
          </div>
          <div class="panel-body">
            
            <!-- Barra de Carga Rápida -->
            <div class="entry-bar">
              <div class="field-group">
                <label class="field-label">Fecha (LatAm)</label>
                <input type="text" id="q-fecha" class="field-control mono latam-datepicker-input" value="<?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?>" placeholder="DD/MM/AAAA">
              </div>

              <div class="field-group">
                <label class="field-label">Hora <button id="btn-now" type="button" style="background:none; border:none; color:var(--accent-cyan); font-size:10px; cursor:pointer;" title="Hora actual">⚡Ahora</button></label>
                <input type="text" id="q-hora" class="field-control mono latam-timepicker-input" value="<?php echo $hora_actual; ?>" placeholder="HH:MM">
              </div>

              <div class="field-group" style="position: relative;">
                <label class="field-label">Recinto de Planta <span style="color:var(--accent-cyan); font-size:9.5px; text-transform:none;">[🔍 Buscar]</span></label>
                <div class="searchable-select-wrapper" id="recinto-search-wrapper" style="position: relative;">
                  <input type="text" id="q-recinto" class="field-control" placeholder="Escriba código o nombre (ej: 108, R-001, G.V.)..." autocomplete="off" style="padding-right: 28px;">
                  <button type="button" class="btn-clear-ctrl" id="btn-clear-recinto" title="Borrar recinto seleccionado" onclick="clearRecintoSelection()">&times;</button>
                  <input type="hidden" id="q-recinto-id" value="">
                  <div class="searchable-dropdown" id="recinto-dropdown"></div>
                </div>
              </div>

              <div class="field-group">
                <label class="field-label">Gases (DAC)</label>
                <input type="text" id="q-gases" class="field-control mono" placeholder="0.00">
              </div>

              <div class="field-group">
                <label class="field-label">Tritio (DAC)</label>
                <input type="text" id="q-tritio" class="field-control mono" placeholder="0.00">
              </div>

              <div class="field-group">
                <label class="field-label">Dosis (µSv/h)</label>
                <input type="text" id="q-dosis" class="field-control mono" placeholder="0.0">
              </div>

              <div class="field-group">
                <label class="field-label">Observaciones</label>
                <input type="text" id="q-obs" class="field-control" placeholder="Detalles de la medición...">
              </div>

              <button class="btn-register" id="btn-add" type="button">
                <span>+ AGREGAR</span>
              </button>
            </div>

            <!-- Pastilla HUD: Últimos 3 valores registrados en este recinto -->
            <div id="recinto-history-panel" style="display: none; margin-bottom: 14px; background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-cyan); border-radius: 6px; padding: 8px 12px; font-size: 11px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 5px;">
                <span style="font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; font-size: 10px;">
                  📍 Últimos 3 Registros en: <strong id="rh-recinto-label" style="color: #ffffff;">-</strong>
                </span>
                <span id="rh-trend-badge" style="font-size: 10px; font-weight: 700; color: var(--text-muted); font-family: var(--font-mono);">
                  🟢 Nivel habitual
                </span>
              </div>
              <div id="rh-chips-list" style="display: flex; gap: 8px; flex-wrap: wrap;">
                <!-- Chips inyectados por JS -->
              </div>
            </div>

            <!-- Tabla de Mediciones (Encabezados Sticky fijos al scrollear) -->
            <div class="table-scroll-wrapper" id="rutina-scroll-wrapper">
              <table class="data-table" id="rutina-table">
              <thead>
                <tr>
                  <th style="width: 105px;">Fecha</th>
                  <th style="width: 75px; text-align: right;">Hora</th>
                  <th>Recinto / Ubicación</th>
                  <th style="width: 105px; text-align: right;">Gases (DAC)</th>
                  <th style="width: 105px; text-align: right;">Tritio (DAC)</th>
                  <th style="width: 115px; text-align: right;">Tasa Dosis (µSv/h)</th>
                  <th>Observaciones</th>
                  <th style="width: 140px;">Oficial</th>
                  <th style="width: 60px; text-align: center;">Acción</th>
                </tr>
              </thead>
              <tbody id="table-body">
                <?php foreach ($relevamientos_rutina_iniciales as $row) { 
                  $dNum = (float)$row['dosis'];
                  $tNum = (float)$row['tritio'];
                  $gNum = (float)$row['gases'];
                  
                  $dosisBadge = ($dNum > 100) ? '<span class="badge-red">' . number_format($dNum, 1) . '</span>' : (($dNum > 20) ? '<span class="badge-amber">' . number_format($dNum, 1) . '</span>' : number_format($dNum, 1));
                  $tritioBadge = ($tNum > 1.0) ? '<span class="badge-amber">' . number_format($tNum, 2) . '</span>' : number_format($tNum, 2);
                ?>
                <tr data-dosis="<?php echo $dNum; ?>" data-tritio="<?php echo $tNum; ?>" data-gases="<?php echo $gNum; ?>" data-recinto="<?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>">
                  <td><?php echo htmlspecialchars($row['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="num"><?php echo htmlspecialchars($row['hora'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td style="font-weight: 700; color: #ffffff;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                      <span class="recinto-link" onclick="openRecintoHistoryModal('<?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>')" title="Ver las últimas 3 mediciones de este recinto">
                        <?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                      <button type="button" class="btn-recinto-hist-pill" onclick="openRecintoHistoryModal('<?php echo htmlspecialchars($row['recinto'], ENT_QUOTES, 'UTF-8'); ?>')" title="Ver últimas mediciones de este recinto">
                        <span>⏱️ 3 Últimos</span>
                      </button>
                    </div>
                  </td>
                  <td class="num"><?php echo number_format($gNum, 2); ?></td>
                  <td class="num"><?php echo $tritioBadge; ?></td>
                  <td class="num"><?php echo $dosisBadge; ?></td>
                  <td style="color: var(--text-muted);"><?php echo htmlspecialchars((!empty($row['obs']) && $row['obs'] !== 'Sin observaciones') ? $row['obs'] : '--Sin observaciones--', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['oficial'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td style="text-align: center;"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
                </tr>
                <?php } ?>
              </tbody>
              </table>
            </div>

          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 2: EFLUENTES GASEOSOS & 7923 ================= -->
      <section class="section-panel" id="sec-gaseosos">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>💨</span> EMISIÓN EFLUENTES GASEOSOS</div>
            </div>
            <div class="panel-body">
              <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 16px;">
                <div class="field-group">
                  <label class="field-label">Ventilación General (GBq)</label>
                  <input type="text" class="field-control mono" id="input-vent-gral" value="1.45">
                </div>
                <div class="field-group">
                  <label class="field-label">Ventilación Reactor (GBq)</label>
                  <input type="text" class="field-control mono" id="input-vent-reactor" value="3.80">
                </div>
                <div class="field-group">
                  <label class="field-label">Emisión Carbono-14 (MBq)</label>
                  <input type="text" class="field-control mono" id="input-c14" value="12.4">
                </div>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                <div class="field-group">
                  <label class="field-label">Dedo Frío 73120 (ppm)</label>
                  <input type="text" class="field-control mono" id="input-dedo-frio" value="18.5">
                </div>
                <div class="field-group">
                  <label class="field-label">Actividad Concentrador 7923 (MBq)</label>
                  <input type="text" class="field-control mono" value="45.2">
                </div>
              </div>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-register" style="flex: 1;" onclick="updateChimneyKPIs()">
                  💾 ASENTAR MEDICIONES DE CHIMENEA
                </button>
                <button type="button" class="btn-ghost" onclick="clearChimneyKPIs()" style="color: var(--accent-amber); border-color: rgba(245, 158, 11, 0.4); padding: 0 12px;" title="Borrar mediciones de chimenea">
                  🗑️ Borrar
                </button>
              </div>
            </div>
          </div>

          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>🌤️</span> CONDICIÓN METEOROLÓGICA</div>
            </div>
            <div class="panel-body">
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label">Dirección del Viento</label>
                <select id="input-met-dir" class="field-control">
                  <option value="S">Sur (S) - 180°</option>
                  <option value="SE">Sudeste (SE) - 135°</option>
                  <option value="E">Este (E) - 90°</option>
                  <option value="NE">Noreste (NE) - 45°</option>
                  <option value="N">Norte (N) - 0°</option>
                  <option value="SO">Sudoeste (SO) - 225°</option>
                  <option value="O">Oeste (O) - 270°</option>
                  <option value="NO">Noroeste (NO) - 315°</option>
                </select>
              </div>
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label">Velocidad del Viento (km/h)</label>
                <input type="text" id="input-met-vel" class="field-control mono" value="14.5">
              </div>
              <div class="field-group" style="margin-bottom: 14px;">
                <label class="field-label">Estabilidad Pasquill</label>
                <select id="input-met-pasquill" class="field-control">
                  <option value="Clase D (Neutral - Nocturno nublado)">Clase D (Neutral - Nocturno nublado)</option>
                  <option value="Clase E (Ligeramente estable)">Clase E (Ligeramente estable)</option>
                  <option value="Clase F (Estable - Noche despejada)">Clase F (Estable - Noche despejada)</option>
                  <option value="Clase A (Extremadamente inestable)">Clase A (Extremadamente inestable)</option>
                  <option value="Clase B (Moderadamente inestable)">Clase B (Moderadamente inestable)</option>
                  <option value="Clase C (Ligeramente inestable)">Clase C (Ligeramente inestable)</option>
                </select>
              </div>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn-register" style="flex: 1;" onclick="saveInlineMeteorology()">
                  💾 GUARDAR METEOROLOGÍA
                </button>
                <button type="button" class="btn-ghost" onclick="clearInlineMeteorology()" style="color: var(--accent-amber); border-color: rgba(245, 158, 11, 0.4); padding: 0 12px;" title="Borrar datos o restablecer como pendiente">
                  🗑️ Borrar
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 3: EFLUENTES LÍQUIDOS ================= -->
      <section class="section-panel" id="sec-liquidos">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>💧</span> EMISIÓN DE EFLUENTES LÍQUIDOS</div>
          </div>
          <div class="panel-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tanque de Descarga</th>
                  <th style="text-align: right;">Volumen (m³)</th>
                  <th style="text-align: right;">Actividad Tritio (MBq/m³)</th>
                  <th style="text-align: right;">Actividad Gamma (MBq/m³)</th>
                  <th>Autorización / Estado</th>
                  <th style="text-align: center;">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="font-weight: 700; color: #fff;">TK-01 (Tanque Retención Activo A)</td>
                  <td class="num">45.0</td>
                  <td class="num">120.5</td>
                  <td class="num">0.85</td>
                  <td><span style="color: var(--accent-green); font-weight: 700;">● AUTORIZADO</span></td>
                  <td style="text-align: center;"><button class="btn-register" style="padding: 4px 10px; height: auto;" onclick="showToast('Verificación de tanque TK-01 emitida')">Verificar</button></td>
                </tr>
                <tr>
                  <td style="font-weight: 700; color: #fff;">TK-02 (Tanque Retención Activo B)</td>
                  <td class="num">12.0</td>
                  <td class="num">85.0</td>
                  <td class="num">0.40</td>
                  <td><span style="color: var(--text-muted);">En llenado</span></td>
                  <td style="text-align: center;"><button class="btn-register" style="padding: 4px 10px; height: auto;" onclick="showToast('Muestreo solicitado')">Muestrear</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 4: FROTIS & DRYERS ================= -->
      <section class="section-panel" id="sec-contaminacion">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>🧽</span> MEDICIÓN DE CONT. SUPERFICIAL Y TASA DE DOSIS EQUIVALENTE</div>
            </div>
            <div class="panel-body">
              <div class="field-group" style="margin-bottom: 12px; position: relative;">
                <label class="field-label">Recinto de Muestreo <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar]</span></label>
                <div class="searchable-select-wrapper" id="frotis-recinto-wrapper">
                  <input type="text" id="frotis-recinto" class="field-control" placeholder="Escriba código o nombre de recinto..." autocomplete="off" style="padding-right: 28px;">
                  <button type="button" class="btn-clear-ctrl" id="btn-clear-frotis-recinto" title="Borrar recinto" style="display:none;">&times;</button>
                  <input type="hidden" id="frotis-recinto-id" value="">
                  <div class="searchable-dropdown" id="frotis-recinto-dropdown"></div>
                </div>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="field-group">
                  <label class="field-label">Cont. Alfa (Bq/cm²)</label>
                  <input type="text" class="field-control mono" placeholder="0.00" value="0.02">
                </div>
                <div class="field-group">
                  <label class="field-label">Cont. Beta-Gamma (Bq/cm²)</label>
                  <input type="text" class="field-control mono" placeholder="0.00" value="0.15">
                </div>
              </div>
              <button class="btn-register" style="width: 100%;" onclick="showToast('✔️ Frotis de contaminación asentado');">
                + REGISTRAR MEDICIÓN DE FROTIS
              </button>
            </div>
          </div>

          <div class="panel-box">
            <div class="panel-header">
              <div class="panel-title"><span>🔄</span> EFICIENCIA DRYERS</div>
            </div>
            <div class="panel-body">
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label">Secador en Servicio</label>
                <select class="field-control">
                  <option value="DRY-01">Dryer D2O N° 1 (Edificio Reactor)</option>
                  <option value="DRY-02">Dryer D2O N° 2 (Área Auxiliar)</option>
                </select>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="field-group">
                  <label class="field-label">Punto de Rocío (°C)</label>
                  <input type="text" class="field-control mono" value="-35.2">
                </div>
                <div class="field-group">
                  <label class="field-label">Eficiencia de Recuperación (%)</label>
                  <input type="text" class="field-control mono" value="98.5">
                </div>
              </div>
              <button class="btn-register" style="width: 100%;" onclick="showToast('✔️ Eficiencia de Dryers registrada');">
                + GUARDAR CONTROL DE DRYERS
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 5: TAREAS & PTR ================= -->
      <!-- ================= SECCIÓN 5: LIBRO DE ACTAS & NOVEDADES DE GUARDIA ================= -->
      <section class="section-panel" id="sec-actas" data-alias="sec-tareas">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>📖</span> LIBRO DE ACTAS &amp; TAREAS PTR</div>
            <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);" id="actas-counter-badge">
              0 actas asentadas en este turno
            </span>
          </div>
          <div class="panel-body">
            
            <!-- FORMULARIO DE CARGA DE ACTA -->
            <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-card); border-radius: 8px; padding: 14px; margin-bottom: 18px;">
              <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; margin-bottom: 10px; letter-spacing: 0.5px;">
                + Cargar Nueva Acta / Registro de Guardia
              </div>

              <!-- Texto del Acta -->
              <div class="field-group" style="margin-bottom: 12px;">
                <label class="field-label" style="font-weight: 700; color: #ffffff;">Descripción del Evento / Novedad / Acta <span style="color: var(--accent-red);">*</span></label>
                <textarea id="acta-texto" class="field-control" style="height: 75px; padding: 8px 10px; resize: vertical; font-size: 12px;" placeholder="Describa el trabajo realizado, novedad de guardia, inspección o evento..."></textarea>
              </div>

              <!-- Metadata: Oficial, Ayudante, Recinto, PTR con Buscadores Inteligentes y Crucecita X -->
              <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 12px;">
                <div class="field-group" style="position: relative;">
                  <label class="field-label">Oficial Responsable <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar]</span></label>
                  <div class="searchable-select-wrapper" id="acta-oficial-wrapper">
                    <input type="text" id="acta-oficial" class="field-control" placeholder="Buscar oficial..." value="<?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" style="font-weight:700; color:#ffffff; padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-oficial" title="Borrar oficial" style="display: <?php echo !empty($oficial_actual) ? 'block' : 'none'; ?>;">&times;</button>
                    <input type="hidden" id="acta-oficial-id" value="<?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="searchable-dropdown" id="acta-oficial-dropdown"></div>
                  </div>
                </div>

                <div class="field-group" style="position: relative;">
                  <label class="field-label">Personal que Asistió / Ayudante <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar]</span></label>
                  <div class="searchable-select-wrapper" id="acta-ayudante-wrapper">
                    <input type="text" id="acta-ayudante" class="field-control" placeholder="-- Sin ayudante / Buscar... --" autocomplete="off" style="padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-ayudante" title="Borrar ayudante" style="display:none;">&times;</button>
                    <input type="hidden" id="acta-ayudante-id" value="">
                    <div class="searchable-dropdown" id="acta-ayudante-dropdown"></div>
                  </div>
                </div>

                <div class="field-group" style="position: relative;">
                  <label class="field-label">Recinto Asociado <span style="color:var(--text-dim); font-size:9.5px;">[🔍 Buscar]</span></label>
                  <div class="searchable-select-wrapper" id="acta-recinto-wrapper">
                    <input type="text" id="acta-recinto" class="field-control" placeholder="General de Planta / Escriba para buscar..." autocomplete="off" style="padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-recinto" title="Borrar recinto" style="display:none;">&times;</button>
                    <input type="hidden" id="acta-recinto-id" value="">
                    <div class="searchable-dropdown" id="acta-recinto-dropdown"></div>
                  </div>
                </div>

                <div class="field-group" style="position: relative;">
                  <label class="field-label">N° PTR Asociado <span style="color:var(--accent-cyan); font-size:9.5px;">[🔍 Buscar PTR]</span></label>
                  <div class="searchable-select-wrapper" id="acta-ptr-wrapper">
                    <input type="text" id="acta-ptr" class="field-control mono" placeholder="Buscar N° o título de PTR activo..." autocomplete="off" style="padding-right: 28px;">
                    <button type="button" class="btn-clear-ctrl" id="btn-clear-acta-ptr" title="Borrar PTR" style="display:none;">&times;</button>
                    <input type="hidden" id="acta-ptr-id" value="">
                    <div class="searchable-dropdown" id="acta-ptr-dropdown"></div>
                  </div>
                </div>
              </div>

              <!-- Parámetros Radiológicos Opcionales & Flags -->
              <div style="display: grid; grid-template-columns: repeat(4, 1fr) 2fr; gap: 12px; align-items: flex-end;">
                <div class="field-group">
                  <label class="field-label">Dosis (µSv/h)</label>
                  <input type="text" id="acta-dosis" class="field-control mono" placeholder="0.0">
                </div>
                <div class="field-group">
                  <label class="field-label">Tritio (DAC)</label>
                  <input type="text" id="acta-tritio" class="field-control mono" placeholder="0.0">
                </div>
                <div class="field-group">
                  <label class="field-label">Cont. β+γ (Bq/cm²)</label>
                  <input type="text" id="acta-cont-beta" class="field-control mono" placeholder="0.0">
                </div>
                <div class="field-group">
                  <label class="field-label">Cont. α (Bq/cm²)</label>
                  <input type="text" id="acta-cont-alfa" class="field-control mono" placeholder="0.0">
                </div>

                <!-- Checkboxes de Seguridad -->
                <div style="display: flex; gap: 14px; align-items: center; padding-bottom: 6px;">
                  <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" id="acta-derrame" style="accent-color: var(--accent-amber); cursor: pointer;">
                    <span>Derrame D2O</span>
                  </label>
                  <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; cursor: pointer; color: var(--text-main);">
                    <input type="checkbox" id="acta-aerosoles" onchange="toggleAerosolesInput(this)" style="accent-color: var(--accent-cyan); cursor: pointer;">
                    <span>Aerosoles</span>
                  </label>
                  <input type="text" id="acta-aerosoles-dac" class="field-control mono" placeholder="DAC aer." style="display: none; width: 80px; height: 30px; font-size: 11px;">
                </div>
              </div>

              <div style="margin-top: 14px; text-align: right;">
                <button type="button" class="btn-register" onclick="asentarNuevaActa()">
                  💾 ASENTAR ACTA EN LIBRO DE GUARDIA
                </button>
              </div>
            </div>

            <!-- TABLA DE ACTAS ASENTADAS EN EL TURNO (Encabezados Sticky fijos) -->
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
              📋 Actas Registradas en Esta Guardia:
            </div>
            <div class="table-scroll-wrapper" id="actas-scroll-wrapper" style="max-height: 300px; min-height: 180px;">
              <table class="data-table" id="tabla-actas-guardia">
              <thead>
                <tr>
                  <th style="width: 65px;">Hora</th>
                  <th style="width: 80px;">PTR</th>
                  <th>Recinto</th>
                  <th>Oficial</th>
                  <th>Asistió</th>
                  <th style="text-align: left;">Descripción / Novedad</th>
                  <th style="width: 90px; text-align: right;">Dosis (µSv/h)</th>
                  <th style="width: 90px;">D2O / Aer.</th>
                  <th style="width: 50px; text-align: center;">Acción</th>
                </tr>
              </thead>
                            <tbody id="tbody-actas-guardia">
                <tr class="acta-clickable-row" onclick="abrirModalDetalleActaFromRow(this)"
                    data-hora="23:35" data-ptr="1042" data-recinto="R-005 - Sistema D2O"
                    data-oficial="ALTUNA, LEONARDO (P)" data-ayudante="GOMEZ, FACUNDO"
                    data-texto="Mantenimiento preventivo de bomba de purificación de agua pesada. Verificación de sellos mecánicos y líneas de recirculación. Sin presencia de goteo visible ni aerosoles en aire de sala. Se autoriza retiro de herramientas bajo control dosimétrico."
                    data-dosis="18.5" data-tritio="1.80" data-derrame="0" data-aerosoles="0.00">
                  <td class="mono">23:35</td>
                  <td class="mono" style="color: var(--accent-cyan); font-weight: 700;">1042</td>
                  <td>R-005 - Sistema D2O</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td>GOMEZ, FACUNDO</td>
                  <td class="text-left" style="max-width: 320px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                      <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px; display: inline-block;">
                        Mantenimiento preventivo de bomba de purificación. Sin goteo ni aerosoles...
                      </span>
                      <button type="button" class="btn-acta-open" title="Abrir y ver acta completa">🔍 Ver</button>
                    </div>
                  </td>
                  <td class="mono" style="text-align: right; color: var(--accent-amber); font-weight: 700;">18.5</td>
                  <td><span style="color: var(--text-dim);">-</span></td>
                  <td style="text-align: center;" onclick="event.stopPropagation();"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
                </tr>
                <tr class="acta-clickable-row" onclick="abrirModalDetalleActaFromRow(this)"
                    data-hora="02:10" data-ptr="-" data-recinto="Planta General"
                    data-oficial="ALTUNA, LEONARDO (P)" data-ayudante="-"
                    data-texto="Inspección de precintos y ronda perimetral de zona controlada. Todas las puertas de acceso verificadas intactas y enclavamientos normales. Monitores de área 7923 registrando fondo natural sin anomalías."
                    data-dosis="0.0" data-tritio="0.00" data-derrame="0" data-aerosoles="0.00">
                  <td class="mono">02:10</td>
                  <td class="mono" style="color: var(--text-dim);">-</td>
                  <td>Planta General</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td>-</td>
                  <td class="text-left" style="max-width: 320px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                      <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px; display: inline-block;">
                        Inspección de precintos y ronda perimetral de zona controlada...
                      </span>
                      <button type="button" class="btn-acta-open" title="Abrir y ver acta completa">🔍 Ver</button>
                    </div>
                  </td>
                  <td class="mono" style="text-align: right; color: var(--text-dim);">0.0</td>
                  <td><span style="color: var(--text-dim);">-</span></td>
                  <td style="text-align: center;" onclick="event.stopPropagation();"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
                </tr>
              </tbody>
              </table>
            </div>

          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 7: HISTORIAL ================= -->
      <section class="section-panel" id="sec-historial">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>📁</span> HISTORIAL DE PLANILLAS ANTERIORES</div>
          </div>
          <div class="panel-body">
            <table class="data-table">
              <thead>
                <tr>
                  <th>N° Planilla</th>
                  <th>Fecha (DD/MM/AAAA)</th>
                  <th>Turno</th>
                  <th>Oficial a Cargo</th>
                  <th>Jefe de Turno</th>
                  <th>Estado</th>
                  <th style="text-align: center;">Reporte PDF</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="num">#10842</td>
                  <td>08/11/2026</td>
                  <td>Turno 1 (06:00 - 14:00)</td>
                  <td>Rodríguez, Miguel</td>
                  <td>Gómez, Carlos</td>
                  <td><span style="color: var(--accent-green); font-weight:700;">Cerrada</span></td>
                  <td style="text-align: center;"><button class="btn-del" style="color: var(--accent-cyan); border-color: var(--accent-cyan);" onclick="showToast('Generando vista oficial de planilla #10842')">📄 Ver PDF</button></td>
                </tr>
                <tr>
                  <td class="num">#10841</td>
                  <td>07/11/2026</td>
                  <td>Turno 3 (23:00 - 07:00)</td>
                  <td>Pérez, Juan</td>
                  <td>Martínez, Esteban</td>
                  <td><span style="color: var(--accent-green); font-weight:700;">Cerrada</span></td>
                  <td style="text-align: center;"><button class="btn-del" style="color: var(--accent-cyan); border-color: var(--accent-cyan);" onclick="showToast('Generando vista oficial de planilla #10841')">📄 Ver PDF</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ================= SECCIÓN 8: CIERRE ================= -->
      <section class="section-panel" id="sec-cierre">
        <div class="panel-box">
          <div class="panel-header">
            <div class="panel-title"><span>🔒</span> FORMALIZACIÓN Y CIERRE DE PLANILLA DIARIA</div>
          </div>
          <div class="panel-body">
            <p style="color: var(--text-muted); line-height: 1.6; margin-bottom: 20px;">
              Al formalizar el cierre del turno:
              <br>• Se congela la planilla y se bloquea la edición de mediciones de rutina.
              <br>• Se estampa la firma digital del <strong>Oficial de Radioprotección (<?php echo htmlspecialchars($oficial_actual, ENT_QUOTES, 'UTF-8'); ?>)</strong> y del <strong>Jefe de Turno (<?php echo htmlspecialchars($jefe_turno_actual, ENT_QUOTES, 'UTF-8'); ?>)</strong>.
              <br>• Se emite el reporte oficial inmutable en formato PDF para el archivo de SyR CNE.
            </p>
            <button class="btn-register" style="padding: 10px 24px; font-size: 13px;" onclick="closeShiftFormal()">
              🔒 FORMALIZAR Y CERRAR TURNO ACTUAL
            </button>
          </div>
        </div>
      </section>

    </div>
  </main>

  <!-- ================= MODAL INTELIGENTE DE APERTURA DE TURNO (SOLUCIÓN TURNO NOCHE) ================= -->
  <div class="modal-overlay" id="shift-config-modal">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <span style="color: var(--accent-cyan);">⚙️</span> Configuración y Apertura de Turno
        </div>
        <button type="button" class="btn-modal-close" onclick="closeShiftModal()" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body">
        
        <div class="field-group" style="margin-bottom: 12px;">
          <label class="field-label">Seleccione Turno de Trabajo</label>
          <select id="modal-select-turno" class="field-control" style="font-weight: 700; color: var(--accent-cyan);">
            <?php foreach ($turnos_array as $tur) { ?>
              <option value="<?php echo $tur['id']; ?>" <?php if($turno_default == $tur['id']) echo "selected"; ?>>
                <?php echo htmlspecialchars($tur['horarios'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php } ?>
          </select>
        </div>

        <div class="field-group" style="margin-bottom: 12px;">
          <label class="field-label">Fecha Operativa de Guardia (DD/MM/AAAA)</label>
          <input type="text" id="modal-fecha-operativa" class="field-control mono latam-datepicker-input" style="font-size: 14px; font-weight: 800; color: #ffffff;" value="<?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <!-- Cartel inteligente de detección automática de madrugada -->
        <div id="night-shift-alert" style="padding: 12px; border-radius: 6px; background: rgba(6, 182, 212, 0.12); border: 1px solid var(--accent-cyan); font-size: 11.5px; color: var(--accent-cyan); margin-bottom: 14px; <?php if(!$es_madrugada) echo 'display:none;'; ?>">
          <strong>ℹ️ Detección Inteligente de Turno Noche:</strong>
          <br>
          La hora actual es de madrugada (<strong><?php echo $hora_actual; ?> hs</strong>). Dado que el Turno Noche inició a las 23:00 hs de ayer, el sistema asignó automáticamente la fecha operativa a <strong>AYER (<?php echo htmlspecialchars($fecha_operativa_default, ENT_QUOTES, 'UTF-8'); ?>)</strong> para evitar el error de desfase de día.
          <div style="margin-top: 8px; display: flex; gap: 8px;">
            <button type="button" style="background: rgba(255,255,255,0.1); border: 1px solid var(--border-card); color: #fff; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;" onclick="setShiftDate('today')">Usar fecha de Hoy</button>
            <button type="button" style="background: var(--accent-cyan); border: none; color: #000; font-weight:700; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;" onclick="setShiftDate('yesterday')">Usar fecha de Ayer</button>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="field-group">
            <label class="field-label">Oficial a Cargo</label>
            <select class="field-control" id="modal-select-oficial">
              <?php foreach ($oficiales_array as $of) { ?>
                <option value="<?php echo htmlspecialchars($of['nombre'], ENT_QUOTES, 'UTF-8'); ?>" <?php if($of['nombre'] == $oficial_actual) echo "selected"; ?>>
                  <?php echo htmlspecialchars($of['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php } ?>
            </select>
          </div>
          <div class="field-group">
            <label class="field-label">Jefe de Turno</label>
            <select class="field-control" id="modal-select-jefe">
              <?php foreach ($jefes_array as $jef) { ?>
                <option value="<?php echo htmlspecialchars($jef['nombre'], ENT_QUOTES, 'UTF-8'); ?>" <?php if($jef['nombre'] == $jefe_turno_actual) echo "selected"; ?>>
                  <?php echo htmlspecialchars($jef['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php } ?>
            </select>
          </div>
          <div id="alert-tab-met" class="kpi-field-alert"></div>
        </div>

        <div class="field-group">
          <label class="field-label">Estado de Planta</label>
          <select class="field-control" id="modal-select-planta">
            <?php foreach ($estados_planta_array as $est) { ?>
              <option value="<?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?>" <?php if($est == $estado_planta) echo "selected"; ?>>
                <?php echo htmlspecialchars($est, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php } ?>
          </select>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" onclick="closeShiftModal()">CANCELAR</button>
        <button class="btn-modal-confirm" onclick="applyShiftConfig()">GUARDAR Y APLICAR AL TURNO</button>
      </div>
    </div>
  </div>

    <!-- ================= MODAL DE CARGA Y ACTUALIZACIÓN DE MÉTRICAS DE PLANTA / CHIMENEA ================= -->
  <div class="modal-overlay" id="kpi-metricas-modal">
    <div class="modal-box" style="width: 620px;">
      <div class="modal-header">
        <div class="modal-header-left">
          <span style="color: var(--accent-cyan);">⚡</span>
          <span id="kpi-modal-title">Carga / Actualización de Métricas de Chimenea &amp; Planta</span>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeKpiModal()" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body" style="padding: 16px 20px;">
        
        <!-- Pestañas internas del modal -->
        <div class="kpi-modal-tabs">
          <button type="button" class="kpi-tab-btn active" data-tab="tab-tritio" onclick="switchKpiTab('tab-tritio')">💨 Tritio Chimenea</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-c14" onclick="switchKpiTab('tab-c14')">⚛️ Carbono 14</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-dedofrio" onclick="switchKpiTab('tab-dedofrio')">❄️ Dedo Frío 73120</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-ro" onclick="switchKpiTab('tab-ro')">📈 % RO Chimenea</button>
          <button type="button" class="kpi-tab-btn" data-tab="tab-met" onclick="switchKpiTab('tab-met')">🌤️ Meteorología</button>
        </div>

        <!-- TAB 1: Tritio Chimenea (Ventilación General y Reactor) -->
        <div class="kpi-tab-content active" id="tab-tritio">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Descarga de tritio gaseoso por ventilación activa de chimenea:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Hora Desde (HH:MM)</label>
              <input type="text" id="kpi-in-tritio-desde" class="field-control mono latam-timepicker-input" value="23:00">
            </div>
            <div class="field-group">
              <label class="field-label">Hora Hasta (HH:MM)</label>
              <input type="text" id="kpi-in-tritio-hasta" class="field-control mono latam-timepicker-input" value="07:00">
            </div>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Ventilación General (GBq)</label>
              <input type="text" id="kpi-in-vent-gral" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #60a5fa;" value="1.45">
            </div>
            <div class="field-group">
              <label class="field-label">Ventilación Reactor (GBq)</label>
              <input type="text" id="kpi-in-vent-reactor" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #22d3ee;" value="3.80">
            </div>
          </div>
          <div id="alert-tab-tritio" class="kpi-field-alert"></div>

          <!-- TABLA DE LOS ÚLTIMOS REGISTROS DE VENTILACIÓN -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Histórico de Ventilación Chimenea
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Guardias Anteriores</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right; color: #60a5fa;">V. General</th>
                  <th style="padding: 5px 8px; text-align: right; color: #22d3ee;">V. Reactor</th>
                  <th style="padding: 5px 8px; text-align: center;">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">23:00</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: #60a5fa; font-weight: 700;">1.45 GBq</td>
                  <td class="mono" style="text-align: right; color: #22d3ee; font-weight: 700;">3.80 GBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">15:00</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: #60a5fa;">1.30 GBq</td>
                  <td class="mono" style="text-align: right; color: #22d3ee;">3.65 GBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">07:00</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: #60a5fa;">1.40 GBq</td>
                  <td class="mono" style="text-align: right; color: #22d3ee;">3.70 GBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 2: Carbono 14 -->
        <div class="kpi-tab-content" id="tab-c14" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Muestreo integrado y concentración de Carbono-14 en efluentes:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Hora de Medición (HH:MM)</label>
              <input type="text" id="kpi-in-c14-hora" class="field-control mono latam-timepicker-input" value="06:00">
            </div>
            <div class="field-group">
              <label class="field-label">Actividad C-14 (MBq)</label>
              <input type="text" id="kpi-in-c14-val" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #c084fc;" value="12.4">
            </div>
          </div>
          <div id="alert-tab-c14" class="kpi-field-alert"></div>

          <!-- TABLA DE LOS ÚLTIMOS REGISTROS DE CARBONO 14 -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Histórico de Emisión Carbono-14
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Límite Op: &lt; 50 MBq</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right; color: #c084fc;">Actividad MBq</th>
                  <th style="padding: 5px 8px; text-align: center;">Estado</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">06:00</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: #c084fc; font-weight: 700;">12.4 MBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">07/11/2026</td>
                  <td class="mono">22:00</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: #c084fc;">11.8 MBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">07/11/2026</td>
                  <td class="mono">14:00</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: #c084fc;">12.0 MBq</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 3: Dedo Frío 73120 -->
        <div class="kpi-tab-content" id="tab-dedofrio" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 12px;">
            Concentración de agua pesada D2O en gas de muestreo criogénico 73120:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 12px; margin-bottom: 10px;">
            <div class="field-group">
              <label class="field-label">Hora de Medición (HH:MM)</label>
              <input type="text" id="kpi-in-dedofrio-hora" class="field-control mono latam-timepicker-input" value="04:30">
            </div>
            <div class="field-group">
              <label class="field-label">Concentración (ppm)</label>
              <input type="text" id="kpi-in-dedofrio-val" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #f472b6;" value="12">
            </div>
          </div>
          <label style="display: flex; align-items: center; gap: 8px; font-size: 11.5px; color: var(--text-muted); cursor: pointer; margin-top: 4px; margin-bottom: 14px;">
            <input type="checkbox" id="kpi-in-dedofrio-menor" checked style="accent-color: var(--accent-cyan);"> Valor por debajo del límite de detección ( &lt; ppm )
          </label>

          <div id="alert-tab-dedofrio" class="kpi-field-alert"></div>
          <!-- TABLA DE LOS ÚLTIMOS 5 DATOS HISTÓRICOS DE DEDO FRÍO -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Últimos 5 Registros Criogénicos (Dedo Frío 73120)
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Límite Op: &lt; 50 ppm</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right;">PPM</th>
                  <th style="padding: 5px 8px; text-align: center;">Estado</th>
                </tr>
              </thead>
              <tbody id="tbody-dedofrio-modal-5">
                <tr>
                  <td class="mono">Hoy</td>
                  <td class="mono">04:15</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: #f472b6; font-weight: 700;">&lt; 12</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">Ayer</td>
                  <td class="mono">20:30</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">&lt; 10</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">Ayer</td>
                  <td class="mono">12:45</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">15</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">Ayer</td>
                  <td class="mono">05:00</td>
                  <td>CISTERNA, GUSTAVO</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">&lt; 11</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">07/11</td>
                  <td class="mono">21:10</td>
                  <td>PASCUAL, SEBASTIAN</td>
                  <td class="mono" style="text-align: right; color: #f472b6;">14</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: % Restricción Operacional -->
        <div class="kpi-tab-content" id="tab-ro" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Porcentaje de Restricción Operacional acumulado del turno (Límite normativo ARN / NASA):
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">% RO Asignado / Calculado</label>
              <input type="text" id="kpi-in-ro-val" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #34d399;" value="8.42">
            </div>
            <div class="field-group">
              <label class="field-label">Condición / Estado</label>
              <input type="text" id="kpi-in-ro-desc" class="field-control" value="Chimenea en rango nominal">
            </div>
          </div>
          <div id="alert-tab-ro" class="kpi-field-alert"></div>

          <!-- TABLA DE LOS ÚLTIMOS REGISTROS DE % RO -->
          <div style="background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 10px 12px; margin-top: 10px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span style="font-size: 10.5px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase;">
                📊 Histórico de Restricción Operacional (% RO)
              </span>
              <span style="font-size: 9.5px; color: var(--text-dim); font-family: var(--font-mono);">Límite Alerta: 15%</span>
            </div>
            <table class="data-table" style="font-size: 11px;">
              <thead>
                <tr>
                  <th style="padding: 5px 8px;">Fecha</th>
                  <th style="padding: 5px 8px;">Hora</th>
                  <th style="padding: 5px 8px;">Oficial Responsable</th>
                  <th style="padding: 5px 8px; text-align: right; color: var(--accent-green);">% RO</th>
                  <th style="padding: 5px 8px; text-align: center;">Condición</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">23:00</td>
                  <td style="font-weight: 700;">ALTUNA, LEONARDO (P)</td>
                  <td class="mono" style="text-align: right; color: var(--accent-green); font-weight: 700;">8.42%</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal (&lt; 15%)</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">15:00</td>
                  <td>ROLHAUSER, MARIANO</td>
                  <td class="mono" style="text-align: right; color: var(--accent-green);">8.10%</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
                <tr>
                  <td class="mono">08/11/2026</td>
                  <td class="mono">07:00</td>
                  <td>GATICA, CARLOS</td>
                  <td class="mono" style="text-align: right; color: var(--accent-green);">8.25%</td>
                  <td style="text-align: center;"><span class="sync-badge">● Nominal</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 5: Condición Meteorológica -->
        <div class="kpi-tab-content" id="tab-met" style="display: none;">
          <div style="font-size: 11.5px; color: var(--text-muted); margin-bottom: 14px;">
            Condiciones meteorológicas en emplazamiento de Central Nuclear Embalse:
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
            <div class="field-group">
              <label class="field-label">Dirección del Viento</label>
              <select id="kpi-in-met-dir" class="field-control">
                <option value="S">Sur (S) - 180°</option>
                <option value="SE">Sudeste (SE) - 135°</option>
                <option value="E">Este (E) - 90°</option>
                <option value="NE">Noreste (NE) - 45°</option>
                <option value="N">Norte (N) - 0°</option>
                <option value="SO">Sudoeste (SO) - 225°</option>
                <option value="O">Oeste (O) - 270°</option>
                <option value="NO">Noroeste (NO) - 315°</option>
              </select>
            </div>
            <div class="field-group">
              <label class="field-label">Velocidad del Viento (km/h)</label>
              <input type="text" id="kpi-in-met-vel" class="field-control mono" style="font-size: 14px; font-weight: 700; color: #f59e0b;" value="14.5" placeholder="Ej: 14.5">
            </div>
          </div>
          <div class="field-group" style="margin-bottom: 12px;">
            <label class="field-label">Estabilidad Pasquill</label>
            <select id="kpi-in-met-pasquill" class="field-control">
              <option value="Clase D (Neutral - Nocturno nublado)">Clase D (Neutral - Nocturno nublado)</option>
              <option value="Clase E (Ligeramente estable)">Clase E (Ligeramente estable)</option>
              <option value="Clase F (Estable - Noche despejada)">Clase F (Estable - Noche despejada)</option>
              <option value="Clase A (Extremadamente inestable)">Clase A (Extremadamente inestable)</option>
              <option value="Clase B (Moderadamente inestable)">Clase B (Moderadamente inestable)</option>
              <option value="Clase C (Ligeramente inestable)">Clase C (Ligeramente inestable)</option>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <button type="button" class="btn-ghost" onclick="clearCurrentKpiTabMetric()" style="color: var(--accent-amber); border-color: rgba(245, 158, 11, 0.4); font-size: 11px; padding: 6px 12px; display: inline-flex; align-items: center; gap: 6px;" title="Eliminar el valor cargado en esta pestaña y restablecer como pendiente">
          <span>🗑️ Eliminar medición (Restablecer a Pendiente)</span>
        </button>
        <div style="display: flex; gap: 10px;">
          <button type="button" class="btn-modal-cancel" onclick="closeKpiModal()">CANCELAR</button>
          <button type="button" class="btn-modal-confirm" onclick="saveKpiMetrics()">💾 GUARDAR Y APLICAR A KPIS</button>
        </div>
      </div>
    </div>
  </div>

  <!-- NOTIFICACIÓN TOAST -->
  <div class="toast-popup" id="toast"></div>

  <!-- MODAL DE ALERTA DE SEGURIDAD RADIOLÓGICA -->
  <div class="modal-overlay" id="confirm-modal">
    <div class="modal-box">
      <div class="modal-header" id="confirm-modal-header" style="color: var(--accent-red);">
        <div class="modal-header-left" id="confirm-modal-title">
          ⚠️ ALERTA DE SEGURIDAD RADIOLÓGICA
        </div>
        <button type="button" class="btn-modal-close" onclick="document.getElementById('confirm-modal').style.display='none'" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body" id="modal-desc">
        La tasa de dosis ingresada supera los 100 µSv/h. ¿Confirma este valor o desea corregirlo?
      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" id="btn-modal-cancel">CANCELAR</button>
        <button class="btn-modal-confirm" id="btn-modal-confirm" style="background: var(--accent-red);">CONFIRMAR VALOR</button>
      </div>
    </div>
  </div>

  <!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN DE REGISTRO -->
  <div class="modal-overlay" id="delete-confirm-modal" style="display: none;" onclick="if(event.target===this) closeDeleteModal();">
    <div class="modal-box" style="max-width: 520px; border-left: 4px solid var(--accent-red); animation: cyber-hologram-assemble 0.25s ease-out;">
      <div class="modal-header" style="color: var(--accent-red); display: flex; justify-content: space-between; align-items: center;">
        <div class="modal-header-left" id="delete-modal-title" style="font-weight: 800; font-size: 13px; letter-spacing: 0.5px;">
          🗑️ CONFIRMAR ELIMINACIÓN DE REGISTRO
        </div>
        <button type="button" class="btn-modal-close" onclick="closeDeleteModal()" title="Cerrar modal (Esc)">✕</button>
      </div>
      <div class="modal-body" id="delete-modal-body" style="padding: 16px 0 10px;">
        <!-- Inyectado dinámicamente -->
      </div>
      <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
        <button type="button" class="btn-modal-cancel" id="btn-delete-cancel" onclick="closeDeleteModal()">CANCELAR</button>
        <button type="button" class="btn-modal-confirm" id="btn-delete-confirm" onclick="confirmDeleteRow()" style="background: var(--accent-red); color: #ffffff; font-weight: 800;">
          🗑️ SÍ, ELIMINAR REGISTRO
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL DE BLOQUEO DE SEGURIDAD: PLANILLA DE OTRO OFICIAL -->
  <div class="modal-overlay modal-gatekeeper" id="modal-traspaso-guardia" style="display: <?php echo $hay_discrepancia_guardia ? 'flex' : 'none'; ?>; z-index: 999999; background: rgba(3, 15, 12, 0.94); backdrop-filter: blur(12px);">
    <div class="modal-box" style="max-width: 590px; border-left: 4px solid var(--accent-amber); box-shadow: 0 10px 40px rgba(0,0,0,0.85); animation: cyber-hologram-assemble 0.28s ease-out;">
      
      <!-- VISTA 1: AVISO Y BLOQUEO DE ACCESO -->
      <div id="handover-paso-gatekeeper">
        <div class="modal-header" style="color: var(--accent-amber); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; letter-spacing: 0.5px;">
            <span>⛔</span> <span>ACCESO BLOQUEADO: PLANILLA DE OTRO OFICIAL</span>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 10px; color: var(--accent-amber); background: rgba(245,158,11,0.15); padding: 2px 8px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(245,158,11,0.3);">SEGURIDAD SyR</span>
            <button type="button" class="btn-modal-close" onclick="closeHandoverModal(true)" title="Cerrar y desbloquear para pruebas">✕</button>
          </div>
        </div>
        <div class="modal-body" style="padding: 16px 0 10px;">
          <div style="font-size: 12.5px; color: #ffffff; line-height: 1.5; margin-bottom: 14px;">
            La planilla activa abierta en el sistema no pertenece a su usuario:
          </div>

          <!-- Ficha comparativa -->
          <div style="background: rgba(0, 0, 0, 0.5); border: 1px solid var(--border-card); border-radius: 6px; padding: 12px 14px; margin-bottom: 14px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 11.5px;">
              <div style="border-right: 1px solid var(--border-subtle); padding-right: 10px;">
                <div style="color: var(--text-dim); font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">
                  👤 Oficial de la Planilla Abierta
                </div>
                <div style="color: var(--accent-amber); font-weight: 800; font-size: 13px;">
                  <?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="color: var(--text-muted); font-size: 11px; margin-top: 2px;">
                  Planilla <strong>#<?php echo $id_planilla_activa; ?></strong> • <?php echo htmlspecialchars($turno_actual_nombre, ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </div>
              <div>
                <div style="color: var(--text-dim); font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">
                  👤 Oficial Entrante (Su Sesión)
                </div>
                <div style="color: var(--accent-cyan); font-weight: 800; font-size: 13px;">
                  <?php echo htmlspecialchars($oficial_logueado, ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="color: var(--text-muted); font-size: 11px; margin-top: 2px;">
                  Estado: 🟢 Sesión iniciada
                </div>
              </div>
            </div>
          </div>

          <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 6px; padding: 10px 12px; font-size: 11.5px; color: #fffbeb; line-height: 1.45; margin-bottom: 16px;">
            📌 <strong>Regla Operativa CNE:</strong> No está permitido ingresar ni asentar mediciones en la planilla de otro oficial. <strong>Debe cerrarse la planilla anterior para poder abrir una nueva.</strong>
          </div>

          <!-- Solo 2 Opciones Claras -->
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <button type="button" class="btn-handover-opt primary" onclick="mostrarPasoAdvertenciaCierre()">
              <div class="opt-title">🔒 Cerrar Planilla Anterior y Abrir Nueva</div>
              <div class="opt-desc">Cierra definitivamente la planilla de <?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?> y habilita la apertura de su nueva planilla limpia.</div>
            </button>

            <button type="button" class="btn-handover-opt secondary" onclick="verPlanillaSoloLectura()">
              <div class="opt-title">👁️ Ingresar en Modo Auditoría / Solo Lectura</div>
              <div class="opt-desc">Inspecciona los registros cargados por el oficial anterior sin poder modificar datos ni ingresar nuevas mediciones.</div>
            </button>

            <button type="button" class="btn-handover-opt tertiary" onclick="desbloquearParaNuevoTurno()" style="border-color: rgba(56, 189, 248, 0.4); background: rgba(56, 189, 248, 0.08);">
              <div class="opt-title" style="color: var(--accent-cyan);">⚡ Desbloquear Consola para Mi Guardia (Modo Pruebas)</div>
              <div class="opt-desc">Habilita la consola y el formulario inmediatamente para su sesión (<?php echo htmlspecialchars($oficial_logueado, ENT_QUOTES, 'UTF-8'); ?>) sin modificar la base de datos.</div>
            </button>
          </div>
        </div>
      </div>

      <!-- VISTA 2: ADVERTENCIA EXPLÍCITA DE CIERRE DEFINITIVO -->
      <div id="handover-paso-advertencia" style="display: none;">
        <div class="modal-header" style="color: #ef4444; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(239, 68, 68, 0.3); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 13px; letter-spacing: 0.5px;">
            <span>⚠️</span> <span>ADVERTENCIA: CIERRE DEFINITIVO DE PLANILLA</span>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 10px; color: #fca5a5; background: rgba(239,68,68,0.2); padding: 2px 8px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(239,68,68,0.4);">ACCIÓN IRREVERSIBLE</span>
            <button type="button" class="btn-modal-close" onclick="volverPasoGatekeeper()" title="Volver atrás">✕</button>
          </div>
        </div>
        <div class="modal-body" style="padding: 16px 0 10px;">
          <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.45); border-radius: 6px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 13px; font-weight: 800; color: #ffffff; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
              <span>⚠️</span> ¿Confirma el cierre definitivo de la Planilla #<?php echo $id_planilla_activa; ?>?
            </div>
            <div style="font-size: 12px; color: #fecaca; line-height: 1.55;">
              La planilla activa pertenece a <strong><?php echo htmlspecialchars($oficial_iniciador_nombre, ENT_QUOTES, 'UTF-8'); ?></strong> y será cerrada en este momento:
              <ul style="margin: 8px 0 4px 18px; padding: 0; list-style-type: disc; color: #ffffff; font-size: 11.5px; line-height: 1.6;">
                <li><strong>No se va a poder seguir editando</strong> ni se podrán agregar nuevas mediciones a esta planilla.</li>
                <li>Quedará formalmente archivada con marca temporal y firma en la base de datos de CNE.</li>
                <li>Se habilitará de inmediato la <strong>apertura limpia de su nueva planilla</strong> para su guardia (<strong style="color: var(--accent-cyan);"><?php echo htmlspecialchars($oficial_logueado, ENT_QUOTES, 'UTF-8'); ?></strong>).</li>
              </ul>
            </div>
          </div>

          <div id="cierre-loading-indicator" style="display: none; background: rgba(0,0,0,0.6); border: 1px solid var(--accent-cyan); border-radius: 6px; padding: 12px; text-align: center; font-size: 12px; color: var(--accent-cyan); margin-bottom: 14px;">
            <span class="inline-spinner" style="margin-right: 8px;">⏳</span> Cerrando planilla anterior en base de datos y preparando nueva planilla...
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 10px;">
            <button type="button" class="btn-ghost" onclick="volverPasoGatekeeper()" id="btn-cancelar-cierre-def" style="height: 36px; padding: 0 16px; font-weight: 700; font-size: 12px;">
              ← Volver
            </button>
            <button type="button" class="btn-confirm-critical" id="btn-ejecutar-cierre-def" onclick="ejecutarCierreDefinitivoYCrearNueva()" style="background: #dc2626; color: #ffffff; font-weight: 800; border: none; padding: 0 18px; height: 36px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 12px; box-shadow: 0 2px 10px rgba(220, 38, 38, 0.4);">
              🔒 SÍ, CERRAR PLANILLA Y ABRIR NUEVA
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= POPUPS FLOTANTES DE DATEPICKER Y TIMEPICKER LATAM ================= -->
  <div id="latam-datepicker-popup" class="latam-datepicker-popup">
    <div class="dp-header">
      <button type="button" class="dp-nav-btn" id="dp-btn-prev">&lt;</button>
      <span class="dp-month-title" id="dp-month-display">Noviembre 2026</span>
      <button type="button" class="dp-nav-btn" id="dp-btn-next">&gt;</button>
    </div>
    <div class="dp-grid-weekdays">
      <div>Lu</div><div>Ma</div><div>Mi</div><div>Ju</div><div>Vi</div><div>Sá</div><div>Do</div>
    </div>
    <div class="dp-grid-days" id="dp-days-container"></div>
    <div class="dp-quick-footer">
      <button type="button" class="dp-quick-btn" id="dp-btn-yesterday">⬅️ Ayer</button>
      <button type="button" class="dp-quick-btn" id="dp-btn-today">📅 Hoy</button>
      <button type="button" class="dp-quick-btn" id="dp-btn-close" style="color:var(--text-dim);">Cerrar</button>
    </div>
  </div>

  <div id="latam-timepicker-popup" class="latam-timepicker-popup">
    <div style="font-size:11px; font-weight:700; color:#fff; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
      <span>⏰ Seleccionar Hora</span>
      <span id="tp-close-btn" style="cursor:pointer; color:var(--text-dim);">✕</span>
    </div>
    <div class="tp-presets">
      <div class="tp-preset-btn" onclick="applyQuickTime('00:00')">00:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('04:00')">04:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('07:00')">07:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('11:00')">11:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('14:00')">14:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('19:00')">19:00</div>
      <div class="tp-preset-btn" onclick="applyQuickTime('23:00')">23:00</div>
      <div class="tp-preset-btn" onclick="applyNowTime()" style="color:var(--accent-cyan); font-weight:bold;">⚡Ahora</div>
    </div>
    <div class="tp-selector-row">
      <div>
        <div class="tp-col-title">Hora</div>
        <div class="tp-col-scroll" id="tp-hours-list"></div>
      </div>
      <div>
        <div class="tp-col-title">Minutos</div>
        <div class="tp-col-scroll" id="tp-minutes-list"></div>
      </div>
    </div>
  </div>

  <script>
    let activePlanillaId = <?php echo $id_planilla_activa ? $id_planilla_activa : 'null'; ?>;

    // ================= NAVEGACIÓN ENTRE SECCIONES (BOTONES LATERALES) =================
    const menuButtons = document.querySelectorAll('.menu-btn');
    const sections = document.querySelectorAll('.section-panel');
    const sectionTitleEl = document.getElementById('current-section-title');

    menuButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        let targetSec = btn.getAttribute('data-section');
        if (!targetSec) return;
        if (targetSec === 'sec-tareas') targetSec = 'sec-actas';

        menuButtons.forEach(b => b.classList.remove('active'));
        sections.forEach(s => s.classList.remove('active'));

        btn.classList.add('active');
        const activeSec = document.getElementById(targetSec);
        if (activeSec) activeSec.classList.add('active');

        const cleanTitle = btn.querySelector('span').textContent.replace(/[^\w\s&]/gi, '').trim();
        sectionTitleEl.textContent = cleanTitle;
      });
    });

    // ================= INTELIGENCIA DE TURNO NOCHE & APERTURA =================
    function openShiftModal() {
      document.getElementById('shift-config-modal').style.display = 'flex';
      checkNightShiftLogic();
    }

    function closeShiftModal() {
      document.getElementById('shift-config-modal').style.display = 'none';
    }

    function checkNightShiftLogic() {
      const turno = document.getElementById('modal-select-turno').value;
      const alertBox = document.getElementById('night-shift-alert');
      const now = new Date();
      const currentHour = now.getHours();

      if (turno === "3" && currentHour >= 0 && currentHour < 8) {
        alertBox.style.display = 'block';
      } else {
        alertBox.style.display = 'none';
      }
    }

    document.getElementById('modal-select-turno').addEventListener('change', checkNightShiftLogic);

    function setShiftDate(type) {
      const now = new Date();
      if (type === 'yesterday') {
        const y = new Date(now);
        y.setDate(y.getDate() - 1);
        const d = String(y.getDate()).padStart(2, '0');
        const m = String(y.getMonth() + 1).padStart(2, '0');
        const a = y.getFullYear();
        document.getElementById('modal-fecha-operativa').value = `${d}/${m}/${a}`;
        showToast('Fecha fijada a AYER (' + `${d}/${m}/${a}` + ')');
      } else {
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const a = now.getFullYear();
        document.getElementById('modal-fecha-operativa').value = `${d}/${m}/${a}`;
        showToast('Fecha fijada a HOY (' + `${d}/${m}/${a}` + ')');
      }
    }

    function applyShiftConfig() {
      const fecha = document.getElementById('modal-fecha-operativa').value;
      const turnoSelect = document.getElementById('modal-select-turno');
      const turnoId = turnoSelect.value;
      const turnoText = turnoSelect.selectedOptions[0].text;
      const oficial = document.getElementById('modal-select-oficial').value;
      const jefe = document.getElementById('modal-select-jefe').value;
      const planta = document.getElementById('modal-select-planta').value;

      document.getElementById('topbar-latam-date').textContent = fecha;
      document.getElementById('q-fecha').value = fecha;
      const shiftTagSidebar = document.getElementById('sidebar-shift-tag');
      if (shiftTagSidebar) shiftTagSidebar.textContent = turnoText;
      const shiftTagTopbar = document.getElementById('topbar-shift-tag');
      if (shiftTagTopbar) shiftTagTopbar.textContent = turnoText;
      document.getElementById('topbar-jefe-turno').textContent = jefe;
      document.getElementById('sidebar-user-name').textContent = oficial;
      document.getElementById('sidebar-plant-status').textContent = planta;

      // Sincronizar vía AJAX si el backend existe
      const partes = fecha.split('/');
      const fechaSql = partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : fecha;

      fetch(`procesos_reg_rad.php?accion=crear_planilla_nueva&id_user=1&id_oficial=1&id_turno=${turnoId}&fecha_planilla=${fechaSql}&id_jefe_turno=1&estados_planta=${encodeURIComponent(planta)}`)
        .then(r => r.text())
        .then(res => {
          if (res && res !== 'mal') {
            activePlanillaId = parseInt(res);
            window.location.href = 'demo_planilla.php';
          }
        })
        .catch(e => console.log('Standalone mode active'));

      closeShiftModal();
      showToast('✔️ Planilla de guardia configurada correctamente');
    }

    // ================= RELOJ EN VIVO =================
    function updateClock() {
      const now = new Date();
      document.getElementById('live-clock').textContent = now.toTimeString().split(' ')[0] + ' hs';
    }
    setInterval(updateClock, 1000);
    updateClock();

    document.getElementById('btn-now').addEventListener('click', () => {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      document.getElementById('q-hora').value = `${h}:${m}`;
      showToast('⚡ Hora sincronizada al momento actual');
    });

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.display = 'block';
      setTimeout(() => { t.style.display = 'none'; }, 3500);
    }

    // ================= RECALCULAR KPIS (PICOS RADIOLÓGICOS: DOSIS, TRITIO, GASES) =================
    function recalculateKPIs() {
      const rows = document.querySelectorAll('#table-body tr');
      const count = rows.length;
      document.getElementById('menu-badge-rutina').textContent = count;

      if (count === 0) {
        document.getElementById('kpi-max-dosis').textContent = '0.0';
        document.getElementById('kpi-max-tritio').textContent = '0.00';
        document.getElementById('kpi-max-gases').textContent = '0.00';
        document.getElementById('kpi-max-recinto').textContent = 'Sin mediciones';
        return;
      }

      let maxDosis = 0;
      let recintoDosis = '-';
      let maxTritio = 0;
      let recintoTritio = '-';
      let maxGases = 0;
      let recintoGases = '-';

      rows.forEach(r => {
        const d = parseFloat(r.getAttribute('data-dosis'));
        const t = parseFloat(r.getAttribute('data-tritio'));
        const g = parseFloat(r.getAttribute('data-gases'));
        const rec = r.getAttribute('data-recinto') || '-';
        const recShort = rec.split(' - ')[0];

        if (!isNaN(d) && d >= maxDosis) {
          maxDosis = d;
          recintoDosis = recShort;
        }
        if (!isNaN(t) && t >= maxTritio) {
          maxTritio = t;
          recintoTritio = recShort;
        }
        if (!isNaN(g) && g >= maxGases) {
          maxGases = g;
          recintoGases = recShort;
        }
      });

      document.getElementById('kpi-max-dosis').textContent = maxDosis.toFixed(1);
      document.getElementById('kpi-max-tritio').textContent = maxTritio.toFixed(2);
      document.getElementById('kpi-max-gases').textContent = maxGases.toFixed(2);

      if (recintoDosis === recintoTritio && recintoDosis !== '-') {
        document.getElementById('kpi-max-recinto').textContent = `📍 ${recintoDosis}`;
      } else if (recintoDosis !== '-' && recintoTritio !== '-') {
        document.getElementById('kpi-max-recinto').textContent = `📍 ${recintoDosis} • ${recintoTritio}`;
      } else {
        document.getElementById('kpi-max-recinto').textContent = `📍 ${recintoDosis !== '-' ? recintoDosis : 'Nominal'}`;
      }
    }

    // ================= CARGA DE MEDICIONES DE RUTINA & DETECCIÓN DE DISCREPANCIAS =================
    let pendingEntry = null;

    /**
     * Obtiene mediciones previas del recinto desde la tabla viva o desde el histórico
     */
    function getPreviousMeasurementsForRecinto(recintoName, recintoId) {
      const norm = (recintoName || '').toLowerCase().trim();
      const rows = Array.from(document.querySelectorAll('#table-body tr')).filter(tr => {
        const r = (tr.getAttribute('data-recinto') || '').toLowerCase().trim();
        return r === norm || r.startsWith(norm) || norm.startsWith(r);
      });

      if (rows.length > 0) {
        return rows.map(tr => ({
          dosis: parseFloat(tr.getAttribute('data-dosis') || 0),
          tritio: parseFloat(tr.getAttribute('data-tritio') || 0),
          gases: parseFloat(tr.getAttribute('data-gases') || 0)
        }));
      }

      // Buscar en mockRecintoHistory por ID o nombre
      if (typeof mockRecintoHistory !== 'undefined') {
        const byId = mockRecintoHistory[recintoId];
        if (byId && byId.length > 0) {
          return byId.map(h => ({
            dosis: parseFloat(h.dosis || 0),
            tritio: parseFloat(h.tritio || 0),
            gases: parseFloat(h.gases || 0)
          }));
        }
      }

      // Valores de línea de base estándar según tipo de recinto
      if (norm.includes('107') || norm.includes('108') || norm.includes('111')) {
        return [{ dosis: 0.0, tritio: 0.20, gases: 0.00 }];
      } else if (norm.includes('501')) {
        return [{ dosis: 0.0, tritio: 0.30, gases: 0.00 }];
      } else if (norm.includes('112')) {
        return [{ dosis: 0.0, tritio: 0.80, gases: 0.00 }];
      }

      return [{ dosis: 2.0, tritio: 0.25, gases: 0.00 }];
    }

    /**
     * Compara los valores ingresados contra el registro anterior del recinto para detectar saltos atípicos
     */
    function checkDiscrepancies(recinto, gases, tritio, dosis) {
      const gVal = parseFloat(gases);
      const tVal = parseFloat(tritio);
      const dVal = parseFloat(dosis);

      const recintoIdInput = document.getElementById('q-recinto-id');
      const prevList = getPreviousMeasurementsForRecinto(recinto, recintoIdInput ? recintoIdInput.value : '');
      if (!prevList || prevList.length === 0) return null;

      const prev = prevList[0];
      const warnings = [];

      // 1. Evaluar Tritio
      if (!isNaN(tVal)) {
        const pTritio = !isNaN(prev.tritio) ? prev.tritio : 0;
        if (pTritio > 0) {
          const pct = Math.round(((tVal - pTritio) / pTritio) * 100);
          if (pct >= 100 && (tVal - pTritio) >= 0.25) {
            warnings.push({
              param: 'Tritio',
              unit: 'DAC',
              val: tVal,
              prevVal: pTritio,
              diffPct: pct,
              type: 'spike'
            });
          }
        } else if (tVal >= 0.5) {
          warnings.push({
            param: 'Tritio',
            unit: 'DAC',
            val: tVal,
            prevVal: 0,
            diffPct: null,
            type: 'spike_from_zero'
          });
        }
      }

      // 2. Evaluar Tasa de Dosis
      if (!isNaN(dVal)) {
        const pDosis = !isNaN(prev.dosis) ? prev.dosis : 0;
        if (pDosis > 0) {
          const pct = Math.round(((dVal - pDosis) / pDosis) * 100);
          if (pct >= 100 && (dVal - pDosis) >= 5.0) {
            warnings.push({
              param: 'Tasa Dosis',
              unit: 'µSv/h',
              val: dVal,
              prevVal: pDosis,
              diffPct: pct,
              type: 'spike'
            });
          }
        } else if (dVal >= 10.0) {
          warnings.push({
            param: 'Tasa Dosis',
            unit: 'µSv/h',
            val: dVal,
            prevVal: 0,
            diffPct: null,
            type: 'spike_from_zero'
          });
        }
      }

      // 3. Evaluar Gases Nobles
      if (!isNaN(gVal)) {
        const pGases = !isNaN(prev.gases) ? prev.gases : 0;
        if (pGases > 0) {
          const pct = Math.round(((gVal - pGases) / pGases) * 100);
          if (pct >= 100 && (gVal - pGases) >= 0.2) {
            warnings.push({
              param: 'Gases Nobles',
              unit: 'DAC',
              val: gVal,
              prevVal: pGases,
              diffPct: pct,
              type: 'spike'
            });
          }
        } else if (gVal >= 0.3) {
          warnings.push({
            param: 'Gases Nobles',
            unit: 'DAC',
            val: gVal,
            prevVal: 0,
            diffPct: null,
            type: 'spike_from_zero'
          });
        }
      }

      return warnings.length > 0 ? warnings : null;
    }

    function commitRow(fecha, hora, recinto, gases, tritio, dosis, obs, warnings = null) {
      const dNum = parseFloat(dosis);
      const tNum = parseFloat(tritio);
      const gNum = parseFloat(gases);

      const tWarn = warnings ? warnings.find(w => w.param === 'Tritio') : null;
      const dWarn = warnings ? warnings.find(w => w.param === 'Tasa Dosis') : null;
      const gWarn = warnings ? warnings.find(w => w.param === 'Gases Nobles') : null;

      let dosisBadge = !isNaN(dNum) 
        ? (dNum > 100 
            ? `<span class="badge-red" title="${dWarn ? 'Alza de +' + dWarn.diffPct + '% vs previo' : 'Supera 100 µSv/h'}">${dNum.toFixed(1)} ⚠️</span>` 
            : ((dNum > 20 || dWarn) 
                ? `<span class="badge-amber" title="${dWarn ? 'Alza de +' + dWarn.diffPct + '% vs anterior' : 'Nivel elevado'}">${dNum.toFixed(1)}${dWarn ? ' ⚠️' : ''}</span>` 
                : dNum.toFixed(1))) 
        : '-';

      let tritioBadge = !isNaN(tNum) 
        ? ((tNum > 1.0 || tWarn) 
            ? `<span class="badge-amber" title="${tWarn ? 'Alza de +' + tWarn.diffPct + '% respecto a medición previa (' + tWarn.prevVal + ' DAC)' : 'Nivel > 1.0 DAC'}">${tNum.toFixed(2)}${tWarn ? ' ⚠️' : ''}</span>` 
            : tNum.toFixed(2)) 
        : '-';

      let gasesBadge = !isNaN(gNum) 
        ? (gWarn 
            ? `<span class="badge-amber" title="Alza de +${gWarn.diffPct}% vs anterior">${gNum.toFixed(2)} ⚠️</span>` 
            : gNum.toFixed(2)) 
        : '-';

      const tr = document.createElement('tr');
      tr.className = 'new-row';
      tr.setAttribute('data-fecha', fecha);
      tr.setAttribute('data-hora', hora);
      tr.setAttribute('data-dosis', isNaN(dNum) ? 0 : dNum);
      tr.setAttribute('data-tritio', isNaN(tNum) ? 0 : tNum);
      tr.setAttribute('data-gases', isNaN(gNum) ? 0 : gNum);
      tr.setAttribute('data-recinto', recinto);

      const oficial = document.getElementById('sidebar-user-name').textContent;

      const cleanObs = (!obs || obs.trim() === '' || obs.trim() === 'Sin observaciones') ? '--Sin observaciones--' : obs.trim();

      tr.innerHTML = `
        <td>${fecha}</td>
        <td class="num">${hora}</td>
        <td style="font-weight: 700; color: #ffffff;">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <span class="recinto-link" onclick="openRecintoHistoryModal('${recinto}')" title="Ver las últimas 3 mediciones de este recinto">
              ${recinto}
            </span>
            <button type="button" class="btn-recinto-hist-pill" onclick="openRecintoHistoryModal('${recinto}')" title="Ver últimas mediciones de este recinto">
              <span>⏱️ 3 Últimos</span>
            </button>
          </div>
        </td>
        <td class="num">${gasesBadge}</td>
        <td class="num">${tritioBadge}</td>
        <td class="num">${dosisBadge}</td>
        <td style="color: var(--text-muted);">${cleanObs}</td>
        <td>${oficial}</td>
        <td style="text-align: center;"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
      `;

      document.getElementById('table-body').prepend(tr);
      recalculateKPIs();
      showToast('✔️ Medición registrada en ' + recinto.split(' - ')[0]);

      // Encolar en LocalStorage y sincronizar con MySQL
      const partes = fecha.split('/');
      const fechaSql = partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : fecha;
      const recintoId = document.getElementById('q-recinto-id').value || 1;
      const rowTempId = 'row_' + Date.now();
      tr.id = rowTempId;

      const urlRutina = `procesos_reg_rad.php?accion=guardar_valores_rutina&id_user=1&fecha=${fechaSql}&hora=${hora}&ubicacion=${recintoId}&gases_nobles=${gNum || 0}&tritio=${tNum || 0}&tasa_dosis=${dNum || 0}&observaciones=${encodeURIComponent(cleanObs)}&relevado_por=1&id_planilla=${activePlanillaId || 1}`;

      SyncEngine.enqueue('rutina', urlRutina, 'GET', null, { rowId: rowTempId, recinto, fecha, hora });

      document.getElementById('q-gases').value = '';
      document.getElementById('q-tritio').value = '';
      document.getElementById('q-dosis').value = '';
      document.getElementById('q-obs').value = '';
      document.getElementById('q-recinto').focus();

      if (typeof updateRecintoHistory === 'function') {
        updateRecintoHistory(recintoId, recinto);
      }
    }

    function handleRegister() {
      const fecha = document.getElementById('q-fecha').value;
      const hora = document.getElementById('q-hora').value;
      const recinto = document.getElementById('q-recinto').value.trim();
      const gases = document.getElementById('q-gases').value.trim();
      const tritio = document.getElementById('q-tritio').value.trim();
      const dosis = document.getElementById('q-dosis').value.trim();
      const obs = document.getElementById('q-obs').value.trim();

      if (!recinto) {
        showToast('⚠️ Seleccione una ubicación / recinto');
        document.getElementById('q-recinto').focus();
        return;
      }

      // Validar que al menos uno de los 3 parámetros radiológicos haya sido ingresado
      if (gases === '' && tritio === '' && dosis === '') {
        showToast('⚠️ Debe introducir al menos un valor (Gases, Tritio o Tasa Dosis)');
        document.getElementById('q-gases').focus();
        return;
      }

      const dNum = parseFloat(dosis);
      const discWarnings = checkDiscrepancies(recinto, gases, tritio, dosis);

      // Caso 1: Umbral de emergencia por alta dosis (> 100 µSv/h)
      if (!isNaN(dNum) && dNum > 100) {
        pendingEntry = { fecha, hora, recinto, gases, tritio, dosis, obs, warnings: discWarnings };
        const hdr = document.getElementById('confirm-modal-header');
        if (hdr) hdr.style.color = 'var(--accent-red)';
        const tit = document.getElementById('confirm-modal-title');
        if (tit) tit.innerHTML = '☢️ ALERTA DE SEGURIDAD RADIOLÓGICA (> 100 µSv/h)';
        const btnConf = document.getElementById('btn-modal-confirm');
        if (btnConf) {
          btnConf.style.background = 'var(--accent-red)';
          btnConf.style.color = '#ffffff';
          btnConf.textContent = 'CONFIRMAR VALOR';
        }
        const btnCanc = document.getElementById('btn-modal-cancel');
        if (btnCanc) btnCanc.textContent = 'CANCELAR Y CORREGIR';

        document.getElementById('modal-desc').innerHTML = `
          La tasa de dosis ingresada para <strong>${recinto}</strong> es de <span style="color: var(--accent-red); font-weight:800;">${dNum} µSv/h</span>.
          <br><br>
          Este valor supera el umbral estándar (> 100 µSv/h). ¿Confirma que la medición es correcta o desea verificarla?
        `;
        document.getElementById('confirm-modal').style.display = 'flex';
        return;
      }

      // Caso 2: Alerta por Discrepancia / Alza Abrupta respecto a valores previos
      if (discWarnings && discWarnings.length > 0) {
        pendingEntry = { fecha, hora, recinto, gases, tritio, dosis, obs, warnings: discWarnings };
        const hdr = document.getElementById('confirm-modal-header');
        if (hdr) hdr.style.color = 'var(--accent-amber)';
        const tit = document.getElementById('confirm-modal-title');
        if (tit) tit.innerHTML = '⚠️ ATENCIÓN OFICIAL: VARIACIÓN RADIOLÓGICA SIGNIFICATIVA';
        const btnConf = document.getElementById('btn-modal-confirm');
        if (btnConf) {
          btnConf.style.background = 'var(--accent-amber)';
          btnConf.style.color = '#041613';
          btnConf.textContent = 'CONFIRMAR Y AGREGAR';
        }
        const btnCanc = document.getElementById('btn-modal-cancel');
        if (btnCanc) btnCanc.textContent = 'CANCELAR Y CORREGIR';

        document.getElementById('modal-desc').innerHTML = `
          <div style="font-size: 13px; line-height: 1.5; color: #ffffff;">
            Se detectó una <strong>variación atípica / alza importante</strong> respecto al historial del recinto <strong>${recinto}</strong>:
            <div style="margin: 14px 0; background: rgba(245, 158, 11, 0.12); border: 1px solid var(--accent-amber); border-left: 4px solid var(--accent-amber); border-radius: 6px; padding: 12px 14px;">
              ${discWarnings.map(w => `
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px;">
                  <span style="font-weight: 700; color: #ffffff;">📊 ${w.param}:</span>
                  <span style="font-family: var(--font-mono); font-size: 13.5px;">
                    <strong style="color: var(--accent-amber); font-size: 14.5px;">${w.val} ${w.unit}</strong> 
                    <span style="color: var(--text-dim); font-size: 11px;">(anterior: ${w.prevVal} ${w.unit})</span>
                  </span>
                  <span class="badge-amber" style="font-size: 11px; font-weight: 800; padding: 3px 8px;">
                    ${w.diffPct !== null ? '+' + w.diffPct + '%' : 'NUEVO PICO'}
                  </span>
                </div>
              `).join('')}
            </div>
            <p style="color: var(--text-muted); font-size: 12px; margin: 0;">
              Por favor, verifique si este valor corresponde a una condición operacional real de la planta o si se trata de un error de tipeo.
            </p>
          </div>
        `;
        document.getElementById('confirm-modal').style.display = 'flex';
        return;
      }

      commitRow(fecha, hora, recinto, gases, tritio, dosis, obs, null);
    }

    document.getElementById('btn-add').addEventListener('click', handleRegister);

    document.querySelectorAll('.entry-bar .field-control').forEach(input => {
      input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          handleRegister();
        }
      });
    });

    document.getElementById('btn-modal-cancel').onclick = () => {
      document.getElementById('confirm-modal').style.display = 'none';
      if (pendingEntry && pendingEntry.warnings && pendingEntry.warnings.length > 0) {
        const first = pendingEntry.warnings[0];
        if (first.param === 'Tritio') {
          const el = document.getElementById('q-tritio');
          if (el) { el.focus(); el.select(); }
        } else if (first.param === 'Gases Nobles') {
          const el = document.getElementById('q-gases');
          if (el) { el.focus(); el.select(); }
        } else {
          const el = document.getElementById('q-dosis');
          if (el) { el.focus(); el.select(); }
        }
      } else {
        const el = document.getElementById('q-dosis');
        if (el) { el.focus(); el.select(); }
      }
      pendingEntry = null;
    };

    document.getElementById('btn-modal-confirm').onclick = () => {
      document.getElementById('confirm-modal').style.display = 'none';
      if (pendingEntry) {
        commitRow(pendingEntry.fecha, pendingEntry.hora, pendingEntry.recinto, pendingEntry.gases, pendingEntry.tritio, pendingEntry.dosis, pendingEntry.obs, pendingEntry.warnings);
        pendingEntry = null;
      } else if (pendingKpiEntry) {
        applyKpiMetricsToDom(pendingKpiEntry.metrics, pendingKpiEntry.warnings);
        pendingKpiEntry = null;
      }
    };

    document.getElementById('btn-modal-cancel').onclick = () => {
      document.getElementById('confirm-modal').style.display = 'none';
      if (pendingEntry) {
        const el = document.getElementById('q-dosis');
        if (el) { el.focus(); el.select(); }
        pendingEntry = null;
      } else if (pendingKpiEntry) {
        if (pendingKpiEntry.warnings && pendingKpiEntry.warnings.length > 0) {
          switchKpiTab(pendingKpiEntry.warnings[0].tab);
          const inp = document.getElementById(pendingKpiEntry.warnings[0].inputId);
          if (inp) { inp.focus(); inp.select(); }
        }
        pendingKpiEntry = null;
      }
    };

    // Alerta en vivo en el panel HUD mientras el oficial tipea
    function evalRealtimeDiscrepancy() {
      const recinto = document.getElementById('q-recinto')?.value;
      const gases = document.getElementById('q-gases')?.value;
      const tritio = document.getElementById('q-tritio')?.value;
      const dosis = document.getElementById('q-dosis')?.value;
      const trendBadge = document.getElementById('rh-trend-badge');
      if (!trendBadge || !recinto) return;

      const warnings = checkDiscrepancies(recinto, gases, tritio, dosis);
      if (warnings && warnings.length > 0) {
        const w = warnings[0];
        trendBadge.innerHTML = `⚠️ Variación de ${w.param} (${w.diffPct !== null ? '+' + w.diffPct + '%' : 'Pico'}) vs anterior`;
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles habituales';
        trendBadge.style.color = 'var(--accent-green)';
      }
    }

    ['q-gases', 'q-tritio', 'q-dosis'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('input', evalRealtimeDiscrepancy);
    });

    let rowToDelete = null;

    function deleteRow(btn) {
      const tr = btn.closest('tr');
      if (!tr) return;
      rowToDelete = tr;

      const isRutina = tr.closest('#table-body') !== null;
      const isActa = tr.closest('#tbody-actas-guardia') !== null;

      let tipo = 'Medición de Rutina';
      let detalleHtml = '';

      if (isRutina) {
        const fecha = tr.getAttribute('data-fecha') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '');
        const hora = tr.getAttribute('data-hora') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '');
        const recinto = tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent.trim().split('\n')[0] : '');
        const gases = tr.getAttribute('data-gases') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '0.00');
        const tritio = tr.getAttribute('data-tritio') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '0.00');
        const dosis = tr.getAttribute('data-dosis') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '0.0');

        tipo = 'Medición de Relevamiento de Rutina';
        detalleHtml = `
          <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-red); border-radius: 6px; padding: 10px 14px; margin: 12px 0; font-family: var(--font-mono); font-size: 11.5px;">
            <div style="font-weight: 800; color: #ffffff; margin-bottom: 6px; font-size: 12.5px;">📍 ${recinto}</div>
            <div style="display: flex; gap: 12px; color: var(--text-muted); margin-bottom: 6px;">
              <span>📅 ${fecha}</span>
              <span>⏰ ${hora} hs</span>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; padding-top: 6px; border-top: 1px solid rgba(255,255,255,0.08);">
              <span style="color: #c084fc;"><strong>G. nobles:</strong> ${gases} DAC</span>
              <span style="color: var(--border-card);">|</span>
              <span style="color: var(--accent-cyan);"><strong>Tritio:</strong> ${tritio} DAC</span>
              <span style="color: var(--border-card);">|</span>
              <span style="color: var(--accent-amber);"><strong>Dosis:</strong> ${dosis} µSv/h</span>
            </div>
          </div>
        `;
      } else if (isActa) {
        tipo = 'Acta / Tarea de Guardia';
        const hora = tr.cells[0] ? tr.cells[0].textContent.trim() : '';
        const ptr = tr.cells[1] ? tr.cells[1].textContent.trim() : '';
        const recinto = tr.cells[2] ? tr.cells[2].textContent.trim() : '';
        const desc = tr.cells[5] ? tr.cells[5].textContent.trim() : '';
        detalleHtml = `
          <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-red); border-radius: 6px; padding: 10px 14px; margin: 12px 0; font-size: 11.5px;">
            <div style="font-weight: 800; color: #ffffff; margin-bottom: 4px;">📖 Acta en ${recinto} ${ptr !== '-' ? '(' + ptr + ')' : ''} - ${hora} hs</div>
            <div style="color: var(--text-muted); font-style: italic;">"${desc}"</div>
          </div>
        `;
      } else {
        tipo = 'Registro';
        detalleHtml = `
          <div style="background: rgba(0, 0, 0, 0.35); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-red); border-radius: 6px; padding: 10px 14px; margin: 12px 0; font-size: 11.5px; color: #ffffff;">
            ¿Confirma que desea eliminar este registro seleccionado de la tabla?
          </div>
        `;
      }

      const modal = document.getElementById('delete-confirm-modal');
      const body = document.getElementById('delete-modal-body');
      if (body) {
        body.innerHTML = `
          <div style="font-size: 12.5px; line-height: 1.5; color: #ffffff;">
            Está a punto de eliminar permanentemente el siguiente registro de <strong>${tipo}</strong>:
            ${detalleHtml}
            <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 6px; padding: 8px 12px; color: #fee2e2; font-size: 11px; display: flex; align-items: center; gap: 8px;">
              <span>⚠️</span>
              <span><strong>Acción irreversible:</strong> Este registro se removerá de la planilla activa y se recalcularán los indicadores del turno.</span>
            </div>
          </div>
        `;
      }

      if (modal) modal.style.display = 'flex';
      const cancelBtn = document.getElementById('btn-delete-cancel');
      if (cancelBtn) cancelBtn.focus();
    }

    function confirmDeleteRow() {
      if (rowToDelete) {
        const isRutina = rowToDelete.closest('#table-body') !== null;
        const isActa = rowToDelete.closest('#tbody-actas-guardia') !== null;
        const recinto = rowToDelete.getAttribute('data-recinto') || '';
        const recintoId = rowToDelete.getAttribute('data-recinto-id') || '';

        rowToDelete.remove();
        rowToDelete = null;

        if (isRutina) {
          recalculateKPIs();
          if (recinto && typeof updateRecintoHistory === 'function') {
            updateRecintoHistory(recintoId, recinto);
          }
        }
        if (isActa) {
          const tbody = document.getElementById('tbody-actas-guardia');
          if (tbody) {
            const count = tbody.querySelectorAll('tr').length;
            const badge = document.getElementById('menu-badge-actas');
            if (badge) badge.textContent = count;
            const countHdr = document.getElementById('actas-counter-badge');
            if (countHdr) countHdr.textContent = count + ' actas asentadas en este turno';
          }
        }

        showToast('🗑️ Registro eliminado correctamente');
      }
      closeDeleteModal();
    }

    function closeDeleteModal() {
      rowToDelete = null;
      const modal = document.getElementById('delete-confirm-modal');
      if (modal) modal.style.display = 'none';
    }

        // ================= MODAL INTERACTIVO DE ACTUALIZACIÓN DE KPIS =================
        function openKpiModal(targetTab) {
      if (targetTab === 'dosis') {
        const rutinaBtn = document.querySelector('.menu-btn[data-section="sec-rutina"]');
        if (rutinaBtn) rutinaBtn.click();
        const dosisInput = document.getElementById('q-dosis');
        if (dosisInput) {
          dosisInput.focus();
          showToast('📍 Dosis Pico: determinada por relevamientos de recintos');
        }
        return;
      }

      document.getElementById('kpi-metricas-modal').style.display = 'flex';

      if (targetTab === 'tritio') {
        switchKpiTab('tab-tritio');
      } else if (targetTab === 'c14') {
        switchKpiTab('tab-c14');
      } else if (targetTab === 'dedofrio') {
        switchKpiTab('tab-dedofrio');
      } else if (targetTab === 'ro') {
        switchKpiTab('tab-ro');
      } else if (targetTab === 'met') {
        switchKpiTab('tab-met');
      } else {
        switchKpiTab('tab-tritio');
      }
    }

    function closeKpiModal() {
      document.getElementById('kpi-metricas-modal').style.display = 'none';
    }

    function switchKpiTab(tabId) {
      document.querySelectorAll('.kpi-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
      });
      document.querySelectorAll('.kpi-tab-content').forEach(content => {
        content.style.display = (content.id === tabId) ? 'block' : 'none';
      });
    }

    let pendingKpiEntry = null;

    function checkKpiDiscrepancies(metrics) {
      const warnings = [];

      // 1. Dedo Frío 73120 (Previo: 12 ppm, Límite Operacional: 50 ppm)
      if (metrics.dedoFrio !== '') {
        const dfVal = parseFloat(metrics.dedoFrio);
        const prevDf = 12.0;
        const limitDf = 50.0;

        if (!isNaN(dfVal)) {
          if (dfVal > limitDf) {
            warnings.push({
              tab: 'tab-dedofrio',
              inputId: 'kpi-in-dedofrio-val',
              param: 'Dedo Frío 73120',
              unit: 'ppm',
              val: (metrics.isMenor ? '< ' : '') + dfVal,
              prevVal: '< ' + prevDf + ' ppm',
              diffPct: Math.round(((dfVal - prevDf) / prevDf) * 100),
              isOverLimit: true,
              limitVal: limitDf + ' ppm',
              msg: `Supera el Límite Operacional de Planta (${limitDf} ppm)`
            });
          } else if (dfVal >= prevDf * 2 && (dfVal - prevDf) >= 5) {
            const pct = Math.round(((dfVal - prevDf) / prevDf) * 100);
            warnings.push({
              tab: 'tab-dedofrio',
              inputId: 'kpi-in-dedofrio-val',
              param: 'Dedo Frío 73120',
              unit: 'ppm',
              val: (metrics.isMenor ? '< ' : '') + dfVal,
              prevVal: '< ' + prevDf + ' ppm',
              diffPct: pct,
              isOverLimit: false,
              msg: `Alza de +${pct}% respecto al valor previo`
            });
          }
        }
      }

      // 2. Ventilación Chimenea (Tritio)
      if (metrics.ventGral !== '') {
        const vgVal = parseFloat(metrics.ventGral);
        const prevVg = 1.45;
        if (!isNaN(vgVal) && vgVal >= prevVg * 2 && (vgVal - prevVg) >= 1.0) {
          const pct = Math.round(((vgVal - prevVg) / prevVg) * 100);
          warnings.push({
            tab: 'tab-tritio',
            inputId: 'kpi-in-vent-gral',
            param: 'Ventilación General',
            unit: 'GBq',
            val: vgVal + ' GBq',
            prevVal: prevVg + ' GBq',
            diffPct: pct,
            isOverLimit: false,
            msg: `Alza de +${pct}% respecto al valor habitual (${prevVg} GBq)`
          });
        }
      }

      if (metrics.ventReactor !== '') {
        const vrVal = parseFloat(metrics.ventReactor);
        const prevVr = 3.80;
        if (!isNaN(vrVal) && vrVal >= prevVr * 2 && (vrVal - prevVr) >= 2.0) {
          const pct = Math.round(((vrVal - prevVr) / prevVr) * 100);
          warnings.push({
            tab: 'tab-tritio',
            inputId: 'kpi-in-vent-reactor',
            param: 'Ventilación Reactor',
            unit: 'GBq',
            val: vrVal + ' GBq',
            prevVal: prevVr + ' GBq',
            diffPct: pct,
            isOverLimit: false,
            msg: `Alza de +${pct}% respecto al valor habitual (${prevVr} GBq)`
          });
        }
      }

      // 3. Carbono-14 (Previo: 12.4 MBq)
      if (metrics.c14 !== '') {
        const c14Val = parseFloat(metrics.c14);
        const prevC14 = 12.4;
        if (!isNaN(c14Val) && c14Val >= prevC14 * 2 && (c14Val - prevC14) >= 10.0) {
          const pct = Math.round(((c14Val - prevC14) / prevC14) * 100);
          warnings.push({
            tab: 'tab-c14',
            inputId: 'kpi-in-c14-val',
            param: 'Emisión Carbono-14',
            unit: 'MBq',
            val: c14Val + ' MBq',
            prevVal: prevC14 + ' MBq',
            diffPct: pct,
            isOverLimit: false,
            msg: `Alza de +${pct}% respecto a medición previa (${prevC14} MBq)`
          });
        }
      }

      // 4. % Restricción Operacional (Previo: 8.42%)
      if (metrics.roVal !== '') {
        const roNum = parseFloat(metrics.roVal);
        const prevRo = 8.42;
        if (!isNaN(roNum)) {
          if (roNum >= 15.0) {
            warnings.push({
              tab: 'tab-ro',
              inputId: 'kpi-in-ro-val',
              param: '% Restricción Operacional',
              unit: '% RO',
              val: roNum + '%',
              prevVal: prevRo + '%',
              diffPct: Math.round(((roNum - prevRo) / prevRo) * 100),
              isOverLimit: true,
              msg: `Supera el umbral de advertencia operacional (15% RO)`
            });
          } else if (roNum >= prevRo * 1.8 && (roNum - prevRo) >= 4.0) {
            const pct = Math.round(((roNum - prevRo) / prevRo) * 100);
            warnings.push({
              tab: 'tab-ro',
              inputId: 'kpi-in-ro-val',
              param: '% Restricción Operacional',
              unit: '% RO',
              val: roNum + '%',
              prevVal: prevRo + '%',
              diffPct: pct,
              isOverLimit: false,
              msg: `Alza de +${pct}% vs valor de referencia`
            });
          }
        }
      }

      // 5. Meteorología (Viento fuerte > 60 km/h)
      if (metrics.metVel !== '') {
        const velNum = parseFloat(metrics.metVel);
        if (!isNaN(velNum) && velNum >= 60.0) {
          warnings.push({
            tab: 'tab-met',
            inputId: 'kpi-in-met-vel',
            param: 'Velocidad del Viento',
            unit: 'km/h',
            val: velNum + ' km/h',
            prevVal: '14.5 km/h',
            diffPct: null,
            isOverLimit: true,
            msg: `Condición meteorológica extrema (> 60 km/h)`
          });
        }
      }

      return warnings.length > 0 ? warnings : null;
    }

    // Evaluación en tiempo real mientras el oficial tipea en cualquier campo del modal
    function evalKpiRealtimeAlerts() {
      const ventGral = (document.getElementById('kpi-in-vent-gral')?.value || '').trim();
      const ventReactor = (document.getElementById('kpi-in-vent-reactor')?.value || '').trim();
      const c14 = (document.getElementById('kpi-in-c14-val')?.value || '').trim();
      const dedoFrio = (document.getElementById('kpi-in-dedofrio-val')?.value || '').trim();
      const isMenor = document.getElementById('kpi-in-dedofrio-menor') ? document.getElementById('kpi-in-dedofrio-menor').checked : false;
      const roVal = (document.getElementById('kpi-in-ro-val')?.value || '').trim();
      const metVel = (document.getElementById('kpi-in-met-vel')?.value || '').trim();

      const metrics = { ventGral, ventReactor, c14, dedoFrio, isMenor, roVal, metVel };
      const warnings = checkKpiDiscrepancies(metrics);

      // Limpiar estilos y cajas de alerta
      ['tab-dedofrio', 'tab-tritio', 'tab-c14', 'tab-ro', 'tab-met'].forEach(tab => {
        const el = document.getElementById('alert-' + tab);
        if (el) el.style.display = 'none';
      });

      ['kpi-in-dedofrio-val', 'kpi-in-vent-gral', 'kpi-in-vent-reactor', 'kpi-in-c14-val', 'kpi-in-ro-val', 'kpi-in-met-vel'].forEach(id => {
        const inp = document.getElementById(id);
        if (inp) inp.classList.remove('input-alert-spike', 'input-alert-limit');
      });

      if (!warnings) return;

      warnings.forEach(w => {
        const alertBox = document.getElementById('alert-' + w.tab);
        const inp = document.getElementById(w.inputId);

        if (inp) {
          inp.classList.add(w.isOverLimit ? 'input-alert-limit' : 'input-alert-spike');
        }

        if (alertBox) {
          alertBox.style.display = 'flex';
          alertBox.className = 'kpi-field-alert ' + (w.isOverLimit ? 'danger' : 'warning');
          alertBox.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(245, 158, 11, 0.25); padding-bottom: 5px;">
              <span style="font-weight: 800; color: #ffffff; text-transform: uppercase; font-size: 10.5px; display: flex; align-items: center; gap: 6px;">
                <span>⚠️</span> <span>DISCREPANCIA: ${w.param.toUpperCase()}</span>
              </span>
              <span class="${w.isOverLimit ? 'badge-red' : 'badge-amber'}" style="font-size: 10px; font-weight: 800; padding: 2px 7px;">
                ${w.diffPct !== null ? '+' + w.diffPct + '% 📈' : 'LÍMITE OP'}
              </span>
            </div>
            <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 10px; padding: 4px 0;">
              <div style="background: rgba(0,0,0,0.3); padding: 5px 8px; border-radius: 4px; border: 1px solid var(--border-card);">
                <div style="color: var(--text-dim); font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Valor Ingresado</div>
                <div style="color: ${w.isOverLimit ? 'var(--accent-red)' : 'var(--accent-amber)'}; font-size: 13px; font-weight: 700;">${w.val}</div>
              </div>
              <div style="color: var(--text-dim); font-size: 14px; text-align: center;">➔</div>
              <div style="background: rgba(0,0,0,0.3); padding: 5px 8px; border-radius: 4px; border: 1px solid var(--border-card);">
                <div style="color: var(--text-dim); font-size: 9.5px; text-transform: uppercase; font-weight: 600;">Medición Previa (Histórico)</div>
                <div style="color: var(--accent-cyan); font-size: 13px; font-weight: 700;">${w.prevVal}</div>
              </div>
            </div>
            <div style="color: var(--text-muted); font-size: 10.5px; line-height: 1.35;">
              ${w.isOverLimit 
                ? '🚨 <strong>Atención:</strong> Supera el umbral operativo permitido. Verifique si corresponde a una condición real o corrija la carga.' 
                : `Variación de <strong>+${w.diffPct}%</strong> respecto al registro de la guardia anterior. Verifique que no sea un error de tipeo.`}
            </div>
          `;
        }
      });
    }

    ['kpi-in-dedofrio-val', 'kpi-in-dedofrio-menor', 'kpi-in-vent-gral', 'kpi-in-vent-reactor', 'kpi-in-c14-val', 'kpi-in-ro-val', 'kpi-in-met-vel'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', evalKpiRealtimeAlerts);
        el.addEventListener('change', evalKpiRealtimeAlerts);
      }
    });

    function saveKpiMetrics() {
      const ventGral = (document.getElementById('kpi-in-vent-gral').value || '').trim();
      const ventReactor = (document.getElementById('kpi-in-vent-reactor').value || '').trim();
      const c14 = (document.getElementById('kpi-in-c14-val').value || '').trim();
      const dedoFrio = (document.getElementById('kpi-in-dedofrio-val').value || '').trim();
      const isMenor = document.getElementById('kpi-in-dedofrio-menor') ? document.getElementById('kpi-in-dedofrio-menor').checked : false;
      const roVal = (document.getElementById('kpi-in-ro-val').value || '').trim();
      const roDesc = (document.getElementById('kpi-in-ro-desc').value || '').trim();
      const metDir = document.getElementById('kpi-in-met-dir') ? document.getElementById('kpi-in-met-dir').value : 'S';
      const metVel = document.getElementById('kpi-in-met-vel') ? (document.getElementById('kpi-in-met-vel').value || '').trim() : '';
      const metPasquill = document.getElementById('kpi-in-met-pasquill') ? document.getElementById('kpi-in-met-pasquill').value : 'Clase D (Neutral - Nocturno nublado)';

      const metrics = { ventGral, ventReactor, c14, dedoFrio, isMenor, roVal, roDesc, metDir, metVel, metPasquill };
      const warnings = checkKpiDiscrepancies(metrics);

      // Si hay advertencias de discrepancia o exceso de límite operacional, disparar modal de confirmación
      if (warnings && warnings.length > 0) {
        pendingKpiEntry = { metrics, warnings };
        const hasLimit = warnings.some(w => w.isOverLimit);

        const hdr = document.getElementById('confirm-modal-header');
        if (hdr) hdr.style.color = hasLimit ? 'var(--accent-red)' : 'var(--accent-amber)';

        const tit = document.getElementById('confirm-modal-title');
        if (tit) {
          tit.innerHTML = hasLimit 
            ? '☢️ ALERTA OPERACIONAL: VALOR FUERA DE RANGO NOMINAL' 
            : '⚠️ ATENCIÓN OFICIAL: VARIACIÓN SIGNIFICATIVA DETECTADA';
        }

        const btnConf = document.getElementById('btn-modal-confirm');
        if (btnConf) {
          btnConf.style.background = hasLimit ? 'var(--accent-red)' : 'var(--accent-amber)';
          btnConf.style.color = '#ffffff';
          btnConf.textContent = 'CONFIRMAR Y APLICAR';
        }

        const btnCanc = document.getElementById('btn-modal-cancel');
        if (btnCanc) btnCanc.textContent = 'CANCELAR Y CORREGIR';

        document.getElementById('modal-desc').innerHTML = `
          Se ha detectado una variación abrupta respecto a los registros previos de planta:
          <div style="background: rgba(14, 39, 34, 0.85); border-left: 3px solid ${hasLimit ? 'var(--accent-red)' : 'var(--accent-amber)'}; padding: 10px 14px; margin: 12px 0; border-radius: 4px;">
            ${warnings.map(w => `
              <div style="margin-bottom: 6px; font-family: var(--font-mono); font-size: 12px;">
                <strong style="color: #ffffff;">${w.param}:</strong>
                Ingresó <span style="font-weight: 800; color: ${w.isOverLimit ? 'var(--accent-red)' : 'var(--accent-amber)'};">${w.val} ${w.unit}</span>
                vs medición previa de <strong>${w.prevVal}</strong>
                ${w.diffPct !== null ? `<span style="color: var(--accent-amber); font-weight:700;">(+${w.diffPct}%)</span>` : ''}.
                ${w.isOverLimit ? `<div style="color: var(--accent-red); font-size: 11px; margin-top: 2px; font-weight: 700;">⚠️ ${w.msg}</div>` : ''}
              </div>
            `).join('')}
          </div>
          ¿Confirma que la medición es correcta o desea verificar y corregir el dato ingresado?
        `;

        document.getElementById('confirm-modal').style.display = 'flex';
        return;
      }

      applyKpiMetricsToDom(metrics, null);
    }

    function applyKpiMetricsToDom(metrics, warnings = null) {
      const { ventGral, ventReactor, c14, dedoFrio, isMenor, roVal, roDesc, metDir, metVel, metPasquill } = metrics;

      const dfWarn = warnings ? warnings.find(w => w.param === 'Dedo Frío 73120') : null;
      const vgWarn = warnings ? warnings.find(w => w.param === 'Ventilación General') : null;
      const vrWarn = warnings ? warnings.find(w => w.param === 'Ventilación Reactor') : null;
      const c14Warn = warnings ? warnings.find(w => w.param === 'Emisión Carbono-14') : null;
      const roWarn = warnings ? warnings.find(w => w.param === '% Restricción Operacional') : null;

      // 1. Ventilación Chimenea (General y Reactor)
      const elVentGral = document.getElementById('kpi-vent-gral');
      const elVentReactor = document.getElementById('kpi-vent-reactor');
      const elVentFooter = document.getElementById('kpi-vent-footer');
      const elVentGralUnit = document.getElementById('kpi-vent-gral-unit');
      const elVentReactorUnit = document.getElementById('kpi-vent-reactor-unit');
      const cardVent = document.getElementById('card-kpi-vent') || (elVentGral ? elVentGral.closest('.kpi-card') : null);

      if (!ventGral && !ventReactor) {
        if (elVentGral) elVentGral.innerHTML = '<span class="kpi-pending-pill">⚠️ Pendiente</span>';
        if (elVentReactor) elVentReactor.innerHTML = '<span class="kpi-pending-pill">⚠️ Pendiente</span>';
        if (elVentGralUnit) elVentGralUnit.textContent = '';
        if (elVentReactorUnit) elVentReactorUnit.textContent = '';
        if (elVentFooter) elVentFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Sin registrar en guardia</span>';
        if (cardVent) cardVent.classList.add('is-pending');
      } else {
        if (elVentGral) {
          elVentGral.innerHTML = vgWarn 
            ? `<span class="badge-amber" title="${vgWarn.msg}">${ventGral} ⚠️</span>` 
            : (ventGral || '-');
        }
        if (elVentReactor) {
          elVentReactor.innerHTML = vrWarn 
            ? `<span class="badge-amber" title="${vrWarn.msg}">${ventReactor} ⚠️</span>` 
            : (ventReactor || '-');
        }
        if (elVentGralUnit) elVentGralUnit.textContent = 'GBq';
        if (elVentReactorUnit) elVentReactorUnit.textContent = 'GBq';
        if (elVentFooter) elVentFooter.textContent = 'Caudal y extracción activa';
        if (cardVent) cardVent.classList.remove('is-pending');
      }

      // 2. Condición Meteorológica
      const elMetVal = document.getElementById('kpi-met-val');
      const elMetUnit = document.getElementById('kpi-met-unit');
      const elMetFooter = document.getElementById('kpi-met-footer');
      const cardMet = document.getElementById('card-kpi-met') || (elMetVal ? elMetVal.closest('.kpi-card') : null);

      if (!metVel) {
        if (elMetVal) elMetVal.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elMetUnit) elMetUnit.textContent = '';
        if (elMetFooter) elMetFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Sin datos meteorológicos</span>';
        if (cardMet) cardMet.classList.add('is-pending');
      } else {
        const dirMap = { 'S': 'Sur', 'SE': 'Sudeste', 'E': 'Este', 'NE': 'Noreste', 'N': 'Norte', 'SO': 'Sudoeste', 'O': 'Oeste', 'NO': 'Noroeste' };
        const dirName = dirMap[metDir] || metDir;
        if (elMetVal) elMetVal.textContent = `${dirName} • ${metVel}`;
        if (elMetUnit) elMetUnit.textContent = 'km/h';
        if (elMetFooter) elMetFooter.textContent = metPasquill.split(' - ')[0];
        if (cardMet) cardMet.classList.remove('is-pending');
      }

      // 3. Emisión Carbono-14
      const elC14 = document.getElementById('kpi-c14');
      const elC14Footer = document.getElementById('kpi-c14-footer');
      const cardC14 = elC14 ? elC14.closest('.kpi-card') : null;
      if (!c14) {
        if (elC14) elC14.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elC14Footer) elC14Footer.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Sin muestreo registrado</span>';
        if (cardC14) cardC14.classList.add('is-pending');
      } else {
        if (elC14) {
          elC14.innerHTML = c14Warn 
            ? `<span class="badge-amber" title="${c14Warn.msg}">${c14} ⚠️</span>` 
            : c14;
        }
        if (elC14Footer) elC14Footer.textContent = 'Muestreo acumulado turno';
        if (cardC14) cardC14.classList.remove('is-pending');
      }

      // 4. Dedo Frío 73120
      const elDedoFrio = document.getElementById('kpi-dedo-frio');
      const elDedoFrioFooter = document.getElementById('kpi-dedo-frio-footer');
      const cardDF = elDedoFrio ? elDedoFrio.closest('.kpi-card') : null;
      if (!dedoFrio) {
        if (elDedoFrio) elDedoFrio.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elDedoFrioFooter) elDedoFrioFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Pendiente de carga</span>';
        if (cardDF) cardDF.classList.add('is-pending');
      } else {
        const fullDf = (isMenor ? '< ' : '') + dedoFrio;
        if (elDedoFrio) {
          if (dfWarn) {
            elDedoFrio.innerHTML = `<span class="${dfWarn.isOverLimit ? 'badge-red' : 'badge-amber'}" title="${dfWarn.msg}">${fullDf} ⚠️</span>`;
          } else {
            elDedoFrio.textContent = fullDf;
          }
        }
        if (elDedoFrioFooter) elDedoFrioFooter.textContent = dfWarn && dfWarn.isOverLimit ? '⚠️ Supera Límite Operacional' : 'Histórico 5 guardias nominal';
        if (cardDF) cardDF.classList.remove('is-pending');
      }

      // 5. Restricción Operacional
      const elRoVal = document.getElementById('kpi-ro-val');
      const elRoFooter = document.getElementById('kpi-ro-footer');
      const cardRo = elRoVal ? elRoVal.closest('.kpi-card') : null;
      if (!roVal) {
        if (elRoVal) elRoVal.innerHTML = '<span class="kpi-pending-pill">⚠️ PENDIENTE</span>';
        if (elRoFooter) elRoFooter.innerHTML = '<span style="color: var(--accent-amber);">⚠️ Pendiente de cálculo</span>';
        if (cardRo) cardRo.classList.add('is-pending');
      } else {
        if (elRoVal) {
          elRoVal.innerHTML = roWarn 
            ? `<span class="${roWarn.isOverLimit ? 'badge-red' : 'badge-amber'}" title="${roWarn.msg}">${roVal} ⚠️</span>` 
            : roVal;
        }
        if (elRoFooter) elRoFooter.textContent = roDesc || 'Chimenea en rango nominal';
        if (cardRo) cardRo.classList.remove('is-pending');
      }

      // Sincronizar inputs secundarios si existen
      if (document.getElementById('input-vent-gral')) document.getElementById('input-vent-gral').value = ventGral;
      if (document.getElementById('input-vent-reactor')) document.getElementById('input-vent-reactor').value = ventReactor;
      if (document.getElementById('input-c14')) document.getElementById('input-c14').value = c14;
      if (document.getElementById('input-dedo-frio')) document.getElementById('input-dedo-frio').value = dedoFrio;
      if (document.getElementById('input-met-dir')) document.getElementById('input-met-dir').value = metDir;
      if (document.getElementById('input-met-vel')) document.getElementById('input-met-vel').value = metVel;
      if (document.getElementById('input-met-pasquill')) document.getElementById('input-met-pasquill').value = metPasquill;

      // Enviar a MySQL vía SyncEngine si está disponible
      if (typeof SyncEngine !== 'undefined') {
        const now = new Date();
        const horaActualStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
        const fechaLatamEl = document.getElementById('topbar-latam-date');
        const fechaLatam = fechaLatamEl ? fechaLatamEl.textContent : '';
        const partes = fechaLatam.split('/');
        const fechaSql = partes.length === 3 ? `${partes[2]}-${partes[1]}-${partes[0]}` : (document.getElementById('q-fecha') ? document.getElementById('q-fecha').value : '');

        const horaDesde = document.getElementById('kpi-in-tritio-desde') ? document.getElementById('kpi-in-tritio-desde').value : '23:00';
        const horaHasta = document.getElementById('kpi-in-tritio-hasta') ? document.getElementById('kpi-in-tritio-hasta').value : '07:00';

        if (ventGral || ventReactor) {
          SyncEngine.enqueue('tritio', `procesos_reg_rad.php?accion=guardar_valores_emision_tritio&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&desde=${horaDesde}&hasta=${horaHasta}&vent_gral_unidad_actividad=5&vent_reactor_unidad_actividad=5&vent_gral=${ventGral}&vent_reactor=${ventReactor}`);
        }
        if (c14) {
          SyncEngine.enqueue('carbono14', `procesos_reg_rad.php?accion=guardar_valores_carbono14&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&hora=${horaActualStr}&actividad_unidad_ingresada=4&actividad=${c14}`);
        }
        if (dedoFrio) {
          SyncEngine.enqueue('dedofrio', `procesos_reg_rad.php?accion=guardar_valores_dedo_frio&id_planilla=${activePlanillaId || 1}&id_usuario=1&fecha=${fechaSql}&hora=${horaActualStr}&ppm=${dedoFrio}&menor_que=${isMenor ? 1 : 0}`);
        }
      }

      closeKpiModal();
      showToast('✔️ Métricas de chimenea y planta actualizadas');
    }

    function clearCurrentKpiTabMetric() {
      const activeBtn = document.querySelector('.kpi-tab-btn.active');
      const activeTab = activeBtn ? activeBtn.getAttribute('data-tab') : 'tab-tritio';

      if (activeTab === 'tab-tritio') {
        document.getElementById('kpi-in-vent-gral').value = '';
        document.getElementById('kpi-in-vent-reactor').value = '';
        if (document.getElementById('input-vent-gral')) document.getElementById('input-vent-gral').value = '';
        if (document.getElementById('input-vent-reactor')) document.getElementById('input-vent-reactor').value = '';
        showToast('🗑️ Medición de ventilación eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-c14') {
        document.getElementById('kpi-in-c14-val').value = '';
        if (document.getElementById('input-c14')) document.getElementById('input-c14').value = '';
        showToast('🗑️ Medición de Carbono-14 eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-dedofrio') {
        document.getElementById('kpi-in-dedofrio-val').value = '';
        if (document.getElementById('input-dedo-frio')) document.getElementById('input-dedo-frio').value = '';
        showToast('🗑️ Medición de Dedo Frío eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-ro') {
        document.getElementById('kpi-in-ro-val').value = '';
        showToast('🗑️ Medición de % RO eliminada (marcada como pendiente)');
      } else if (activeTab === 'tab-met') {
        if (document.getElementById('kpi-in-met-vel')) document.getElementById('kpi-in-met-vel').value = '';
        if (document.getElementById('input-met-vel')) document.getElementById('input-met-vel').value = '';
        showToast('🗑️ Datos meteorológicos eliminados (marcados como pendiente)');
      }
      saveKpiMetrics();
    }

    // ================= EFLUENTES GASEOSOS & KPIS =================
    function updateChimneyKPIs() {
      const ventGral = (document.getElementById('input-vent-gral').value || '').trim();
      const ventReactor = (document.getElementById('input-vent-reactor').value || '').trim();
      const c14 = (document.getElementById('input-c14').value || '').trim();
      const dedoFrio = (document.getElementById('input-dedo-frio').value || '').trim();

      if (document.getElementById('kpi-in-vent-gral')) document.getElementById('kpi-in-vent-gral').value = ventGral;
      if (document.getElementById('kpi-in-vent-reactor')) document.getElementById('kpi-in-vent-reactor').value = ventReactor;
      if (document.getElementById('kpi-in-c14-val')) document.getElementById('kpi-in-c14-val').value = c14;
      if (document.getElementById('kpi-in-dedofrio-val')) document.getElementById('kpi-in-dedofrio-val').value = dedoFrio;

      saveKpiMetrics();
    }

    function clearChimneyKPIs() {
      if (document.getElementById('input-vent-gral')) document.getElementById('input-vent-gral').value = '';
      if (document.getElementById('input-vent-reactor')) document.getElementById('input-vent-reactor').value = '';
      if (document.getElementById('input-c14')) document.getElementById('input-c14').value = '';
      if (document.getElementById('input-dedo-frio')) document.getElementById('input-dedo-frio').value = '';

      if (document.getElementById('kpi-in-vent-gral')) document.getElementById('kpi-in-vent-gral').value = '';
      if (document.getElementById('kpi-in-vent-reactor')) document.getElementById('kpi-in-vent-reactor').value = '';
      if (document.getElementById('kpi-in-c14-val')) document.getElementById('kpi-in-c14-val').value = '';
      if (document.getElementById('kpi-in-dedofrio-val')) document.getElementById('kpi-in-dedofrio-val').value = '';

      saveKpiMetrics();
      showToast('🗑️ Mediciones de chimenea eliminadas (pendientes)');
    }

    function saveInlineMeteorology() {
      const dir = document.getElementById('input-met-dir') ? document.getElementById('input-met-dir').value : 'S';
      const vel = document.getElementById('input-met-vel') ? document.getElementById('input-met-vel').value.trim() : '';
      const pasquill = document.getElementById('input-met-pasquill') ? document.getElementById('input-met-pasquill').value : 'Clase D (Neutral - Nocturno nublado)';

      if (document.getElementById('kpi-in-met-dir')) document.getElementById('kpi-in-met-dir').value = dir;
      if (document.getElementById('kpi-in-met-vel')) document.getElementById('kpi-in-met-vel').value = vel;
      if (document.getElementById('kpi-in-met-pasquill')) document.getElementById('kpi-in-met-pasquill').value = pasquill;

      saveKpiMetrics();
    }

    function clearInlineMeteorology() {
      if (document.getElementById('input-met-vel')) document.getElementById('input-met-vel').value = '';
      if (document.getElementById('kpi-in-met-vel')) document.getElementById('kpi-in-met-vel').value = '';
      saveKpiMetrics();
      showToast('🗑️ Condiciones meteorológicas eliminadas (pendientes)');
    }
    // ================= TAREAS DE CAMPO & PTR =================
    function registerDailyTask() {
      const ptr = document.getElementById('task-ptr').value.trim();
      const recinto = document.getElementById('task-recinto').value;
      const oficialSelect = document.getElementById('task-oficial');
      const oficialNombre = (oficialSelect ? oficialSelect.value : document.getElementById('sidebar-user-name').textContent).toUpperCase();
      const ayudanteSelect = document.getElementById('task-ayudante');
      const ayudanteNombre = ayudanteSelect.options[ayudanteSelect.selectedIndex].text.toUpperCase();
      const dosis = document.getElementById('task-dosis').value || '0.0';
      const derrame = document.getElementById('task-derrame').value;
      const desc = (document.getElementById('task-desc').value || 'Sin descripción').toUpperCase();

      if (!ptr) {
        showToast('⚠️ Ingrese el número de PTR');
        document.getElementById('task-ptr').focus();
        return;
      }

      const tbody = document.getElementById('tasks-table-body');
      const tr = document.createElement('tr');
      tr.className = 'new-row';
      tr.innerHTML = `
        <td class="num" style="font-weight: 700; color: var(--accent-cyan);">${ptr.toUpperCase()}</td>
        <td>${recinto}</td>
        <td style="font-weight: 700; color: #ffffff;">${oficialNombre}</td>
        <td>${ayudanteNombre}</td>
        <td class="num">${dosis}</td>
        <td>${derrame === '1' ? '<span class="badge-amber">SÍ (D2O)</span>' : '<span style="color:var(--text-muted)">NO</span>'}</td>
        <td>${desc}</td>
        <td style="text-align: center;"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
      `;
      tbody.prepend(tr);

      const taskCount = tbody.querySelectorAll('tr').length;
      document.getElementById('menu-badge-tareas').textContent = taskCount;

      // Encolar tarea en LocalStorage y sincronizar con MySQL
      SyncEngine.enqueue('tarea', 'procesos_reg_rad_post.php?accion=insertar_tarea_diaria', 'POST', {
        id_planilla: (typeof activePlanillaId !== 'undefined' ? activePlanillaId : 1),
        id_usuario: 1,
        texto: desc,
        recinto: recinto,
        ptr: ptr,
        dosis: dosis,
        derrame: derrame,
        id_oficial_asociado: (oficialSelect ? oficialSelect.value : ''),
        id_ayudante: (ayudanteSelect ? ayudanteSelect.value : '')
      });

      showToast('✔️ Tarea PTR asentada correctamente');
      document.getElementById('task-ptr').value = '';
      document.getElementById('task-desc').value = '';
      document.getElementById('task-dosis').value = '';
    }

    // ================= LIBRO DE ACTAS =================
    function saveActas() {
      const text = document.getElementById('actas-text').value;
      // Guardar en MySQL
      SyncEngine.enqueue('actas', 'procesos_reg_rad_post.php?accion=actualizar_libro_actas', 'POST', {
        id_planilla: activePlanillaId || 1,
        id_usuario: 1,
        texto: text
      });

      showToast('✔️ Novedades guardadas en el Libro de Actas');
    }

    // ================= CIERRE DE TURNO =================
    function closeShiftFormal() {
      showToast('🔒 Formalizando y cerrando planilla...', 2000);
      fetch(`procesos_reg_rad.php?accion=cerrar_planilla&id_planilla=${activePlanillaId || 1}&id_usuario=1`)
        .catch(e => console.log('Offline close'));

      setTimeout(() => {
        showToast('🔒 Turno formalizado y cerrado. Planilla archivada con éxito.', 4000);
      }, 1000);
    }

    // ================= KEEP-ALIVE HEARTBEAT (ANTI-DESCONEXIÓN) =================
    function sendHeartbeat() {
      fetch('keep_alive.php')
        .then(response => response.json())
        .then(data => {
          const dot = document.getElementById('session-status-dot');
          const text = document.getElementById('session-status-text');
          if (dot && text) {
            dot.style.background = 'var(--accent-green)';
            text.textContent = 'Sistema en línea';
          }
        })
        .catch(err => {
          const dot = document.getElementById('session-status-dot');
          const text = document.getElementById('session-status-text');
          if (dot && text) {
            dot.style.background = 'var(--accent-amber)';
            text.textContent = 'Modo Local / Intranet';
          }
        });
    }
    // Ping cada 4 minutos para evitar caducidad a los 24 minutos
    setInterval(sendHeartbeat, 240000);
    setTimeout(sendHeartbeat, 3000);

    recalculateKPIs();
  
    // ================= CIERRE DE MODALES AL CLICKEAR AFUERA (BACKDROP) Y CON TECLA ESCAPE =================
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', (e) => {
        if (overlay.id === 'modal-traspaso-guardia') {
          // Clic exterior en el gatekeeper desbloquea para pruebas
          if (e.target === overlay) closeHandoverModal(true);
          return;
        }
        if (e.target === overlay) {
          overlay.style.display = 'none';
          if (overlay.id === 'confirm-modal') {
            pendingEntry = null;
          }
        }
      });
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
          if (overlay.id === 'modal-traspaso-guardia') {
            closeHandoverModal(true);
            return;
          }
          if (overlay.style.display === 'flex' || overlay.style.display === 'block') {
            overlay.style.display = 'none';
            if (overlay.id === 'confirm-modal') {
              pendingEntry = null;
            }
          }
        });
      }
    });

  
    // ================= BUSCADORES INTELIGENTES SELECT2 VANILLA (RECINTOS, OFICIALES, AYUDANTES, PTRs) =================
    const rawRecintos = <?php echo json_encode($recintos_array); ?>;
    const recintosList = rawRecintos.map(item => {
      if (typeof item === 'string') return { id: item, name: item, subtext: '' };
      return { id: String(item.id), name: item.recinto, subtext: '#' + item.id };
    });

    const rawOficiales = <?php echo json_encode($oficiales_array); ?>;
    const oficialesList = rawOficiales.map(item => ({
      id: item.nombre,
      name: item.nombre,
      subtext: 'OFICIAL'
    }));

    const rawAyudantes = <?php echo json_encode($ayudantes_array); ?>;
    const ayudantesList = rawAyudantes.map(item => ({
      id: String(item.id),
      name: item.nombre,
      subtext: 'AYUDANTE'
    }));

    // Listado Oficial de PTRs Activos CNE (con fallback offline completo)
    let listaPTRs = <?php echo json_encode(array_map(function($p) {
        return array(
            'id' => (string)$p['id'],
            'name' => 'PTR ' . $p['id'] . ' - ' . $p['titulo'],
            'titulo' => $p['titulo'],
            'subtext' => '#' . $p['id']
        );
    }, $ptrs_array_inicial)); ?>;

    // Sincronización en segundo plano con el Web Service Oficial de PTRs CNE (sin bloquear)
    if (window.fetch) {
      fetch('https://syrapp.cne.central.nasa/ptr/simplestWebService.php?action=listadoPTRs_activos')
        .then(res => res.json())
        .then(data => {
          if (Array.isArray(data) && data.length > 0) {
            listaPTRs = data.map(item => ({
              id: String(item.id),
              name: 'PTR ' + item.id + ' - ' + (item.titulo_tarea_elca || ('Tarea PTR ' + item.id)),
              titulo: item.titulo_tarea_elca || ('PTR ' + item.id),
              subtext: '#' + item.id
            }));
            console.log('✅ PTRs activos sincronizados exitosamente desde CNE Web Service:', listaPTRs.length);
          }
        })
        .catch(err => {
          console.log('ℹ️ Operando con catálogo de PTRs offline precargado');
        });
    }

    /**
     * Controlador genérico y robusto para selectores con buscador en vivo, navegación por teclado y crucecita X
     */
    function setupSearchableDropdown(cfg) {
      const input = document.getElementById(cfg.inputId);
      const hidden = cfg.hiddenId ? document.getElementById(cfg.hiddenId) : null;
      const dropdown = document.getElementById(cfg.dropdownId);
      const clearBtn = cfg.clearBtnId ? document.getElementById(cfg.clearBtnId) : null;
      if (!input || !dropdown) return null;

      let highlightedIndex = -1;
      let filteredItems = [];

      function updateClearBtn() {
        if (clearBtn) {
          clearBtn.style.display = input.value ? 'block' : 'none';
        }
      }

      function render(queryText = '') {
        const rawItems = typeof cfg.getItems === 'function' ? cfg.getItems() : (cfg.items || []);
        const query = (queryText || '').trim().toLowerCase();

        if (!query) {
          filteredItems = rawItems.slice(0, 60);
        } else {
          filteredItems = rawItems.filter(item => {
            const text = (item.name || item.id || '').toLowerCase();
            const sub = (item.subtext || '').toLowerCase();
            return text.includes(query) || sub.includes(query);
          }).slice(0, 60);
        }

        dropdown.innerHTML = '';
        highlightedIndex = -1;

        if (filteredItems.length === 0) {
          dropdown.innerHTML = '<div class="searchable-empty">No se encontraron resultados para "' + (queryText || '') + '"</div>';
        } else {
          filteredItems.forEach((item, idx) => {
            const div = document.createElement('div');
            div.className = 'searchable-item';
            div.setAttribute('data-index', idx);

            let labelHtml = item.name;
            if (query) {
              const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
              labelHtml = item.name.replace(regex, '<mark>$1</mark>');
            }

            const subHtml = item.subtext ? `<span style="font-size:10px; color:var(--text-dim); font-family:var(--font-mono); margin-left:8px;">${item.subtext}</span>` : '';
            div.innerHTML = `<span>${labelHtml}</span>${subHtml}`;

            div.addEventListener('mousedown', (e) => {
              e.preventDefault();
              selectItem(item);
            });

            dropdown.appendChild(div);
          });
        }

        dropdown.classList.add('open');
        updateClearBtn();
      }

      function selectItem(item) {
        if (cfg.formatValueOnSelect) {
          input.value = cfg.formatValueOnSelect(item);
        } else {
          input.value = item.name;
        }
        if (hidden) {
          hidden.value = item.id;
        }
        dropdown.classList.remove('open');
        updateClearBtn();

        if (typeof cfg.onSelect === 'function') {
          cfg.onSelect(item);
        }
        if (cfg.nextFocusId) {
          const next = document.getElementById(cfg.nextFocusId);
          if (next) next.focus();
        }
      }

      function clear() {
        input.value = '';
        if (hidden) hidden.value = '';
        dropdown.classList.remove('open');
        updateClearBtn();
        if (typeof cfg.onClear === 'function') {
          cfg.onClear();
        }
        input.focus();
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          clear();
        });
      }

      input.addEventListener('focus', () => {
        render(input.value);
      });

      input.addEventListener('input', () => {
        render(input.value);
        if (hidden) hidden.value = input.value;
        updateClearBtn();
      });

      input.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.searchable-item');
        if (!dropdown.classList.contains('open')) {
          if (e.key === 'ArrowDown' || e.key === 'Enter') {
            render(input.value);
            e.preventDefault();
            return;
          }
        }

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (items.length > 0) {
            highlightedIndex = (highlightedIndex + 1) % items.length;
            items.forEach((it, i) => it.classList.toggle('highlighted', i === highlightedIndex));
            if (items[highlightedIndex]) items[highlightedIndex].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (items.length > 0) {
            highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
            items.forEach((it, i) => it.classList.toggle('highlighted', i === highlightedIndex));
            if (items[highlightedIndex]) items[highlightedIndex].scrollIntoView({ block: 'nearest' });
          }
        } else if (e.key === 'Enter') {
          if (highlightedIndex >= 0 && filteredItems[highlightedIndex]) {
            e.preventDefault();
            selectItem(filteredItems[highlightedIndex]);
          } else if (filteredItems.length > 0 && dropdown.classList.contains('open')) {
            e.preventDefault();
            selectItem(filteredItems[0]);
          }
        } else if (e.key === 'Escape') {
          dropdown.classList.remove('open');
        }
      });

      document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target) && (!clearBtn || !clearBtn.contains(e.target))) {
          dropdown.classList.remove('open');
        }
      });

      return { clear, render, selectItem };
    }

    // 1. Buscador de Recintos en Rutina
    const routineRecintoSelect = setupSearchableDropdown({
      inputId: 'q-recinto',
      hiddenId: 'q-recinto-id',
      dropdownId: 'recinto-dropdown',
      clearBtnId: 'btn-clear-recinto',
      getItems: () => recintosList,
      onSelect: (item) => {
        if (typeof updateRecintoHistory === 'function') {
          updateRecintoHistory(item.id, item.name);
        }
      },
      onClear: () => {
        if (typeof clearRecintoSelection === 'function') {
          clearRecintoSelection();
        }
      },
      nextFocusId: 'q-gases'
    });

    // 2. Buscador de Oficial Responsable en Actas
    const actaOficialSelect = setupSearchableDropdown({
      inputId: 'acta-oficial',
      hiddenId: 'acta-oficial-id',
      dropdownId: 'acta-oficial-dropdown',
      clearBtnId: 'btn-clear-acta-oficial',
      getItems: () => oficialesList,
      nextFocusId: 'acta-ayudante'
    });

    // 3. Buscador de Personal / Ayudante en Actas
    const actaAyudanteSelect = setupSearchableDropdown({
      inputId: 'acta-ayudante',
      hiddenId: 'acta-ayudante-id',
      dropdownId: 'acta-ayudante-dropdown',
      clearBtnId: 'btn-clear-acta-ayudante',
      getItems: () => ayudantesList,
      nextFocusId: 'acta-recinto'
    });

    // 4. Buscador de Recinto Asociado en Actas
    const actaRecintoSelect = setupSearchableDropdown({
      inputId: 'acta-recinto',
      hiddenId: 'acta-recinto-id',
      dropdownId: 'acta-recinto-dropdown',
      clearBtnId: 'btn-clear-acta-recinto',
      getItems: () => recintosList,
      nextFocusId: 'acta-ptr'
    });

    // 5. Selector y Buscador de PTRs Activos en Actas
    const actaPtrSelect = setupSearchableDropdown({
      inputId: 'acta-ptr',
      hiddenId: 'acta-ptr-id',
      dropdownId: 'acta-ptr-dropdown',
      clearBtnId: 'btn-clear-acta-ptr',
      getItems: () => listaPTRs,
      formatValueOnSelect: (item) => 'PTR ' + item.id + ' - ' + (item.titulo || item.name.replace(/^PTR \d+ - /, '')),
      onSelect: (item) => {
        const ptrIdHidden = document.getElementById('acta-ptr-id');
        if (ptrIdHidden) ptrIdHidden.value = item.id;
        // Si el texto de la observación está vacío, sugerir la descripción de la tarea PTR
        const textoArea = document.getElementById('acta-texto');
        if (textoArea && !textoArea.value.trim()) {
          textoArea.value = (item.titulo || item.name) + ': ';
        }
      },
      nextFocusId: 'acta-dosis'
    });

    // 6. Buscador de Recinto en Contaminación Superficial (Frotis)
    const frotisRecintoSelect = setupSearchableDropdown({
      inputId: 'frotis-recinto',
      hiddenId: 'frotis-recinto-id',
      dropdownId: 'frotis-recinto-dropdown',
      clearBtnId: 'btn-clear-frotis-recinto',
      getItems: () => recintosList
    });

    // Exponer helpers globales para cruces X y compatibilidad
    window.clearActaOficial = () => actaOficialSelect && actaOficialSelect.clear();
    window.clearActaAyudante = () => actaAyudanteSelect && actaAyudanteSelect.clear();
    window.clearActaRecinto = () => actaRecintoSelect && actaRecintoSelect.clear();
    window.clearActaPtr = () => actaPtrSelect && actaPtrSelect.clear();
    window.clearFrotisRecinto = () => frotisRecintoSelect && frotisRecintoSelect.clear();

  
    // ================= IMPLEMENTACIÓN VANILLA DATEPICKER LATAM (DD/MM/AAAA) =================
    const dpPopup = document.getElementById('latam-datepicker-popup');
    const dpMonthDisplay = document.getElementById('dp-month-display');
    const dpDaysContainer = document.getElementById('dp-days-container');
    let dpCurrentDate = new Date();
    let dpTargetInput = null;

    const monthNamesLatam = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    function parseLatamDate(str) {
      if (!str) return new Date();
      const parts = str.split('/');
      if (parts.length === 3) {
        const d = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parseInt(parts[2], 10);
        if (!isNaN(d) && !isNaN(m) && !isNaN(y)) return new Date(y, m, d);
      }
      return new Date();
    }

    function formatLatamDate(dateObj) {
      const d = String(dateObj.getDate()).padStart(2, '0');
      const m = String(dateObj.getMonth() + 1).padStart(2, '0');
      const y = dateObj.getFullYear();
      return `${d}/${m}/${y}`;
    }

    function renderDatepickerGrid() {
      const year = dpCurrentDate.getFullYear();
      const month = dpCurrentDate.getMonth();

      dpMonthDisplay.textContent = `${monthNamesLatam[month]} ${year}`;
      dpDaysContainer.innerHTML = '';

      const firstDayOfMonth = new Date(year, month, 1);
      const lastDayOfMonth = new Date(year, month + 1, 0);

      let startingDay = firstDayOfMonth.getDay() - 1;
      if (startingDay === -1) startingDay = 6;

      const totalDays = lastDayOfMonth.getDate();
      const prevMonthLastDay = new Date(year, month, 0).getDate();

      for (let i = startingDay - 1; i >= 0; i--) {
        const cell = document.createElement('div');
        cell.className = 'dp-day-cell other-month';
        cell.textContent = prevMonthLastDay - i;
        dpDaysContainer.appendChild(cell);
      }

      const today = new Date();
      const selectedVal = dpTargetInput ? dpTargetInput.value : '';

      for (let day = 1; day <= totalDays; day++) {
        const cell = document.createElement('div');
        cell.className = 'dp-day-cell';
        cell.textContent = day;

        const cellDate = new Date(year, month, day);
        const cellStr = formatLatamDate(cellDate);

        if (cellStr === formatLatamDate(today)) {
          cell.classList.add('today');
        }
        if (cellStr === selectedVal) {
          cell.classList.add('selected');
        }

        cell.addEventListener('click', (e) => {
          e.stopPropagation();
          if (dpTargetInput) {
            dpTargetInput.value = cellStr;
            dpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
            dpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
          closeDatepicker();
        });

        dpDaysContainer.appendChild(cell);
      }
    }

    function openDatepicker(inputEl) {
      closeTimepicker();
      dpTargetInput = inputEl;
      dpCurrentDate = parseLatamDate(inputEl.value);

      const rect = inputEl.getBoundingClientRect();
      const popupWidth = 260;
      let left = rect.left;
      if (left + popupWidth > window.innerWidth) {
        left = window.innerWidth - popupWidth - 10;
      }
      dpPopup.style.top = (rect.bottom + 4) + 'px';
      dpPopup.style.left = Math.max(10, left) + 'px';
      dpPopup.style.display = 'block';

      renderDatepickerGrid();
    }

    function closeDatepicker() {
      if (dpPopup) dpPopup.style.display = 'none';
      dpTargetInput = null;
    }

    if (document.getElementById('dp-btn-prev')) {
      document.getElementById('dp-btn-prev').addEventListener('click', (e) => {
        e.stopPropagation();
        dpCurrentDate.setMonth(dpCurrentDate.getMonth() - 1);
        renderDatepickerGrid();
      });
    }

    if (document.getElementById('dp-btn-next')) {
      document.getElementById('dp-btn-next').addEventListener('click', (e) => {
        e.stopPropagation();
        dpCurrentDate.setMonth(dpCurrentDate.getMonth() + 1);
        renderDatepickerGrid();
      });
    }

    if (document.getElementById('dp-btn-yesterday')) {
      document.getElementById('dp-btn-yesterday').addEventListener('click', (e) => {
        e.stopPropagation();
        const y = new Date();
        y.setDate(y.getDate() - 1);
        if (dpTargetInput) {
          dpTargetInput.value = formatLatamDate(y);
          dpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
          dpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeDatepicker();
      });
    }

    if (document.getElementById('dp-btn-today')) {
      document.getElementById('dp-btn-today').addEventListener('click', (e) => {
        e.stopPropagation();
        const t = new Date();
        if (dpTargetInput) {
          dpTargetInput.value = formatLatamDate(t);
          dpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
          dpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
        closeDatepicker();
      });
    }

    if (document.getElementById('dp-btn-close')) {
      document.getElementById('dp-btn-close').addEventListener('click', (e) => {
        e.stopPropagation();
        closeDatepicker();
      });
    }

    // ================= IMPLEMENTACIÓN VANILLA TIMEPICKER LATAM (HH:MM) =================
    const tpPopup = document.getElementById('latam-timepicker-popup');
    const tpHoursList = document.getElementById('tp-hours-list');
    const tpMinutesList = document.getElementById('tp-minutes-list');
    let tpTargetInput = null;

    if (tpHoursList && tpHoursList.children.length === 0) {
      for (let h = 0; h < 24; h++) {
        const div = document.createElement('div');
        div.className = 'tp-item';
        div.textContent = String(h).padStart(2, '0');
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          selectTimePart('h', div.textContent);
        });
        tpHoursList.appendChild(div);
      }
    }

    if (tpMinutesList && tpMinutesList.children.length === 0) {
      for (let m = 0; m < 60; m += 5) {
        const div = document.createElement('div');
        div.className = 'tp-item';
        div.textContent = String(m).padStart(2, '0');
        div.addEventListener('click', (e) => {
          e.stopPropagation();
          selectTimePart('m', div.textContent);
        });
        tpMinutesList.appendChild(div);
      }
    }

    function selectTimePart(type, val) {
      if (!tpTargetInput) return;
      const current = (tpTargetInput.value || '00:00').split(':');
      let h = current[0] || '00';
      let m = current[1] || '00';

      if (type === 'h') h = val;
      if (type === 'm') m = val;

      tpTargetInput.value = `${h}:${m}`;
      tpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
      tpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function applyQuickTime(hhmm) {
      if (tpTargetInput) {
        tpTargetInput.value = hhmm;
        tpTargetInput.dispatchEvent(new Event('input', { bubbles: true }));
        tpTargetInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      closeTimepicker();
    }

    function applyNowTime() {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      applyQuickTime(`${h}:${m}`);
    }

    function openTimepicker(inputEl) {
      closeDatepicker();
      tpTargetInput = inputEl;
      const rect = inputEl.getBoundingClientRect();
      const popupWidth = 260;
      let left = rect.left;
      if (left + popupWidth > window.innerWidth) {
        left = window.innerWidth - popupWidth - 10;
      }
      tpPopup.style.top = (rect.bottom + 4) + 'px';
      tpPopup.style.left = Math.max(10, left) + 'px';
      tpPopup.style.display = 'block';
    }

    function closeTimepicker() {
      if (tpPopup) tpPopup.style.display = 'none';
      tpTargetInput = null;
    }

    if (document.getElementById('tp-close-btn')) {
      document.getElementById('tp-close-btn').addEventListener('click', (e) => {
        e.stopPropagation();
        closeTimepicker();
      });
    }

    // Delegación global de clics para abrir Datepicker y Timepicker
    document.addEventListener('click', (e) => {
      // 1. Clic en input de fecha
      const dpInput = e.target.closest('.latam-datepicker-input');
      if (dpInput) {
        e.stopPropagation();
        openDatepicker(dpInput);
        return;
      }

      // 2. Clic en input de hora
      const tpInput = e.target.closest('.latam-timepicker-input');
      if (tpInput) {
        e.stopPropagation();
        openTimepicker(tpInput);
        return;
      }

      // 3. Clic fuera de los popups -> cerrar
      if (dpPopup && dpPopup.style.display === 'block' && !dpPopup.contains(e.target)) {
        closeDatepicker();
      }
      if (tpPopup && tpPopup.style.display === 'block' && !tpPopup.contains(e.target)) {
        closeTimepicker();
      }
    });

  
    // ================= SELECTOR DE FONDO Y TEMAS EN VIVO =================
    function setAppTheme(themeName) {
      document.documentElement.setAttribute('data-theme', themeName);
      try {
        localStorage.setItem('regrad_active_theme', themeName);
      } catch(e) {}

      document.querySelectorAll('#theme-switcher-bar .theme-pill').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-theme') === themeName);
      });
      showToast('🎨 Fondo aplicado: ' + themeName.toUpperCase());
    }

    // Restaurar tema guardado al cargar
    try {
      const savedTheme = localStorage.getItem('regrad_active_theme') || 'petrol';
      if (savedTheme) {
        setAppTheme(savedTheme);
      }
    } catch(e) {}

  
    // ================= MOTOR DE COLA OFFLINE Y SINCRONIZACIÓN AUTOMÁTICA (OUTBOX) =================
    const IS_DEMO_MODE = true; // true = MODO PRUEBA/DEMO: Protege la base de datos real contra cualquier escritura

    const SyncEngine = {
      QUEUE_KEY: 'regrad_offline_queue_v1',
      isSyncing: false,

      // Encolar acción en LocalStorage
      enqueue(actionType, url, method = 'GET', postData = null, meta = {}) {
        let queue = this.getQueue();
        const item = {
          id: 'tx_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
          timestamp: new Date().toISOString(),
          actionType,
          url,
          method,
          postData,
          meta,
          retries: 0
        };
        queue.push(item);
        this.saveQueue(queue);
        this.updateSyncUI();

        // Disparar intento de sincronización en segundo plano
        this.flush();
        return item.id;
      },

      getQueue() {
        try {
          const q = localStorage.getItem(this.QUEUE_KEY);
          return q ? JSON.parse(q) : [];
        } catch(e) {
          return [];
        }
      },

      saveQueue(queue) {
        try {
          localStorage.setItem(this.QUEUE_KEY, JSON.stringify(queue));
        } catch(e) {}
      },

      // Intentar vaciar la cola enviando peticiones pendientes al servidor
      async flush(manual = false) {
        if (this.isSyncing) return;
        let queue = this.getQueue();
        if (queue.length === 0) {
          this.updateSyncUI();
          if (manual) showToast('☁️ No hay datos pendientes de sincronización');
          return;
        }

        this.isSyncing = true;
        this.updateSyncUI(true);

        const remaining = [];
        let successCount = 0;

        for (let i = 0; i < queue.length; i++) {
          const item = queue[i];
          try {
            if (IS_DEMO_MODE) {
              // MODO DEMO PROTEGIDO: Simula éxito en LocalStorage sin tocar MySQL
              console.log(`[DEMO PROTEGIDA] Acción simulada [${item.actionType}] en LocalStorage - Base de Datos MySQL INTACTA`);
              successCount++;
              if (item.meta && item.meta.rowId) {
                const rowEl = document.getElementById(item.meta.rowId);
                if (rowEl) {
                  const b = rowEl.querySelector('.sync-badge');
                  if (b) {
                    b.className = 'sync-badge synced';
                    b.innerHTML = '● Guardado (Demo)';
                    b.title = 'Guardado simulado en LocalStorage (Base de datos real protegida)';
                  }
                }
              }
              continue;
            }

            let response;
            if (item.method === 'POST') {
              let formData = new FormData();
              if (item.postData) {
                for (let k in item.postData) {
                  formData.append(k, item.postData[k]);
                }
              }
              response = await fetch(item.url, { method: 'POST', body: formData });
            } else {
              response = await fetch(item.url);
            }

            if (response && response.ok) {
              const resText = await response.text();
              console.log(`✔️ Sincronizado OK [${item.actionType}]:`, resText);
              successCount++;

              // Actualizar badge visual en la fila correspondiente si existe
              if (item.meta && item.meta.rowId) {
                const rowEl = document.getElementById(item.meta.rowId);
                if (rowEl) {
                  const b = rowEl.querySelector('.sync-badge');
                  if (b) {
                    b.className = 'sync-badge synced';
                    b.innerHTML = '● Guardado';
                    b.title = 'Guardado confirmado en MySQL';
                  }
                }
              }
            } else {
              throw new Error('Servidor devolvió status: ' + (response ? response.status : 'desconocido'));
            }
          } catch(err) {
            console.warn(`⏳ Error enviando [${item.actionType}], reintentará automáticamente:`, err.message);
            item.retries = (item.retries || 0) + 1;
            remaining.push(item);
          }
        }

        this.saveQueue(remaining);
        this.isSyncing = false;
        this.updateSyncUI();

        if (successCount > 0 && remaining.length === 0) {
          showToast(`☁️ ${successCount} registro(s) sincronizado(s) con éxito con MySQL`);
        } else if (manual && remaining.length > 0) {
          showToast(`⏳ El servidor aún no responde. ${remaining.length} registro(s) seguro(s) en LocalStorage`);
        }
      },

      updateSyncUI(inProgress = false) {
        const queue = this.getQueue();
        const pill = document.getElementById('sync-status-indicator');
        if (!pill) return;

        if (inProgress) {
          pill.className = 'sync-pill syncing';
          pill.innerHTML = `🔄 Sincronizando (${queue.length})...`;
        } else if (queue.length > 0) {
          pill.className = 'sync-pill pending';
          pill.innerHTML = `⏳ ${queue.length} pendiente${queue.length > 1 ? 's' : ''} [Reintentar]`;
          pill.title = 'Datos guardados localmente. Haga clic para intentar enviar ahora al servidor';
        } else {
          pill.className = 'sync-pill synced';
          pill.innerHTML = IS_DEMO_MODE ? `🛡️ Modo Demo (BD Protegida)` : `☁️ Servidor sincronizado`;
          pill.title = IS_DEMO_MODE ? 'Modo simulación activo: no se escribe nada en MySQL' : 'Todos los datos están guardados en MySQL';
        }
      }
    };

    // Reintentos automáticos periódicos cada 25 segundos
    setInterval(() => {
      SyncEngine.flush();
    }, 25000);

    // Reintento inmediato cuando se detecta conexión a internet / intranet
    window.addEventListener('online', () => {
      showToast('🌐 Conexión detectada. Sincronizando cola de datos...');
      SyncEngine.flush();
    });

    // Inicializar indicador visual al cargar
    setTimeout(() => {
      SyncEngine.updateSyncUI();
      SyncEngine.flush();
    }, 2000);


    // ================= HISTÓRICO DE LOS ÚLTIMOS 3 VALORES DEL RECINTO =================
        // Borrar selección de recinto con la crucecita X
    function clearRecintoSelection() {
      const input = document.getElementById('q-recinto');
      const idInput = document.getElementById('q-recinto-id');
      const clearBtn = document.getElementById('btn-clear-recinto');
      const histPanel = document.getElementById('recinto-history-panel');
      const dropdown = document.getElementById('recinto-dropdown');

      if (input) {
        input.value = '';
        input.focus();
      }
      if (idInput) idInput.value = '';
      if (clearBtn) clearBtn.style.display = 'none';
      if (histPanel) histPanel.style.display = 'none';
      if (dropdown) dropdown.classList.remove('open');
    }

    const mockRecintoHistory = {
      '1': [
        { fecha: '08/11/2026', hora: '23:30', dosis: '2.5', tritio: '0.20', gases: '0.00' },
        { fecha: '08/11/2026', hora: '15:40', dosis: '2.0', tritio: '0.15', gases: '0.00' },
        { fecha: '08/11/2026', hora: '08:10', dosis: '1.8', tritio: '0.20', gases: '0.00' }
      ],
      '24': [
        { fecha: '08/11/2026', hora: '23:30', dosis: '24.0', tritio: '0.45', gases: '0.00' },
        { fecha: '08/11/2026', hora: '15:40', dosis: '18.5', tritio: '0.40', gases: '0.00' },
        { fecha: '08/11/2026', hora: '08:15', dosis: '12.0', tritio: '0.25', gases: '0.00' }
      ],
      '5': [
        { fecha: '08/11/2026', hora: '23:35', dosis: '18.5', tritio: '1.80', gases: '0.40' },
        { fecha: '08/11/2026', hora: '15:50', dosis: '15.0', tritio: '1.50', gases: '0.30' },
        { fecha: '08/11/2026', hora: '08:20', dosis: '14.0', tritio: '1.20', gases: '0.20' }
      ],
      '12': [
        { fecha: '08/11/2026', hora: '23:45', dosis: '45.0', tritio: '3.40', gases: '0.85' },
        { fecha: '08/11/2026', hora: '16:00', dosis: '42.0', tritio: '3.10', gases: '0.80' },
        { fecha: '08/11/2026', hora: '08:30', dosis: '40.0', tritio: '2.80', gases: '0.70' }
      ]
    };

    function updateRecintoHistory(recintoId, recintoName) {
      const panel = document.getElementById('recinto-history-panel');
      const label = document.getElementById('rh-recinto-label');
      const chipsList = document.getElementById('rh-chips-list');
      const trendBadge = document.getElementById('rh-trend-badge');
      if (!panel || !chipsList) return;

      if (!recintoName || recintoName.trim() === '') {
        panel.style.display = 'none';
        return;
      }

      label.textContent = recintoName.split(' - ')[0];

      // 1. Extraer mediciones reales ya cargadas en la tabla viva para este recinto
      const norm = recintoName.toLowerCase().trim();
      const tableRows = Array.from(document.querySelectorAll('#table-body tr')).filter(tr => {
        const r = (tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent : '')).toLowerCase().trim();
        return r && (r === norm || r.startsWith(norm) || norm.startsWith(r));
      });

      const history = [];
      tableRows.forEach(tr => {
        if (history.length >= 3) return;
        const f = tr.getAttribute('data-fecha') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '08/11/2026');
        const h = tr.getAttribute('data-hora') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '00:00');
        const d = tr.getAttribute('data-dosis') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '0.0');
        const t = tr.getAttribute('data-tritio') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '0.0');
        const g = tr.getAttribute('data-gases') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '0.0');
        const fClean = (!f || f.toLowerCase().includes('turno')) ? '08/11/2026' : f;
        history.push({
          fecha: fClean,
          hora: h,
          dosis: parseFloat(d).toFixed(1),
          tritio: parseFloat(t).toFixed(2),
          gases: parseFloat(g).toFixed(2)
        });
      });

      // 2. Si hay menos de 3 registros en la tabla viva, complementar con historial de base (estrictamente Fecha y Hora, NUNCA Turno)
      const fallbackList = (typeof mockRecintoHistory !== 'undefined' && mockRecintoHistory[recintoId]) 
        ? mockRecintoHistory[recintoId] 
        : [
            { fecha: '08/11/2026', hora: '23:30', dosis: '4.5', tritio: '0.40', gases: '0.00' },
            { fecha: '08/11/2026', hora: '15:40', dosis: '4.0', tritio: '0.35', gases: '0.00' },
            { fecha: '08/11/2026', hora: '08:10', dosis: '3.8', tritio: '0.30', gases: '0.00' }
          ];

      fallbackList.forEach(fb => {
        if (history.length < 3) {
          const fbClean = (!fb.fecha || fb.fecha.toLowerCase().includes('turno')) ? '08/11/2026' : fb.fecha;
          history.push({
            fecha: fbClean,
            hora: fb.hora,
            dosis: fb.dosis,
            tritio: fb.tritio,
            gases: fb.gases
          });
        }
      });

      // 3. Renderizar chips mostrando estrictamente Fecha y Hora (SIN Turno) y en orden: G. nobles, Tritio y Dosis
      chipsList.innerHTML = history.map((item, idx) => `
        <div style="background: rgba(22, 54, 48, 0.7); border: 1px solid var(--border-card); border-radius: 4px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 11px;">
          <span style="font-weight: 800; color: var(--accent-cyan);">[${idx + 1}]</span>
          <span style="color: #ffffff; font-weight: 700;">📅 ${item.fecha} &bull; ${item.hora} hs:</span>
          <span style="color: #c084fc; font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">G. nobles:</span> ${item.gases} DAC</span>
          <span style="color: var(--border-card);">|</span>
          <span style="color: var(--accent-cyan); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Tritio:</span> ${item.tritio} DAC</span>
          <span style="color: var(--border-card);">|</span>
          <span style="color: var(--accent-amber); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Dosis:</span> ${item.dosis} µSv/h</span>
        </div>
      `).join('');

      // Calcular tendencia rápida
      const d1 = parseFloat(history[0].dosis);
      const dLast = parseFloat(history[history.length - 1].dosis);
      if (dLast > 0 && d1 > dLast * 1.3) {
        trendBadge.innerHTML = '📈 Alza de Dosis (+ ' + Math.round(((d1 - dLast)/dLast)*100) + '%)';
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles estables';
        trendBadge.style.color = 'var(--accent-green)';
      }

      panel.style.display = 'block';
    }

    // Escuchar cambios en selector de recinto
    const qRecintoInput = document.getElementById('q-recinto');
    if (qRecintoInput) {
      qRecintoInput.addEventListener('change', () => {
        const idVal = document.getElementById('q-recinto-id').value;
        updateRecintoHistory(idVal, qRecintoInput.value);
      });
    }

    // ================= FUNCIONES DE CARGA Y GESTIÓN DE ACTAS =================
    function toggleAerosolesInput(cb) {
      const input = document.getElementById('acta-aerosoles-dac');
      if (input) {
        input.style.display = cb.checked ? 'inline-block' : 'none';
        if (cb.checked) input.focus();
        else input.value = '';
      }
    }

    function asentarNuevaActa() {
      const texto = document.getElementById('acta-texto').value.trim();
      const oficialHidden = document.getElementById('acta-oficial-id');
      const oficialInput = document.getElementById('acta-oficial');
      const oficial = ((oficialHidden && oficialHidden.value) ? oficialHidden.value : (oficialInput ? oficialInput.value : 'OFICIAL DE GUARDIA')).toUpperCase();

      const ayudanteHidden = document.getElementById('acta-ayudante-id');
      const ayudanteInput = document.getElementById('acta-ayudante');
      const ayudanteId = ayudanteHidden ? ayudanteHidden.value : '';
      const ayudanteNombre = (ayudanteInput && ayudanteInput.value.trim()) ? ayudanteInput.value.trim().toUpperCase() : '-';

      const recintoInput = document.getElementById('acta-recinto');
      const recinto = (recintoInput && recintoInput.value.trim()) ? recintoInput.value.trim() : 'General de Planta';

      const ptrHidden = document.getElementById('acta-ptr-id');
      const ptrInput = document.getElementById('acta-ptr');
      let rawPtr = (ptrHidden && ptrHidden.value) ? ptrHidden.value : (ptrInput ? ptrInput.value.trim() : '');
      let ptr = '';
      if (rawPtr) {
        const m = rawPtr.match(/\d+/);
        ptr = m ? m[0] : rawPtr;
      }

      const dosis = document.getElementById('acta-dosis').value.trim() || '0.0';
      const tritio = document.getElementById('acta-tritio').value.trim() || '0.0';
      const contBeta = document.getElementById('acta-cont-beta').value.trim() || '0.0';
      const contAlfa = document.getElementById('acta-cont-alfa').value.trim() || '0.0';
      const derrame = document.getElementById('acta-derrame').checked ? 1 : 0;
      const aerosoles = document.getElementById('acta-aerosoles').checked ? 1 : 0;
      const aerosolesDac = document.getElementById('acta-aerosoles-dac').value.trim() || '0.0';

      if (!texto) {
        showToast('⚠️ Ingrese la descripción o texto del Acta');
        document.getElementById('acta-texto').focus();
        return;
      }

      const now = new Date();
      const horaStr = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');

      // 1. Agregar a la tabla de actas en pantalla
      const tbody = document.getElementById('tbody-actas-guardia');
      const tr = document.createElement('tr');
      tr.className = 'new-row';
      tr.className = 'new-row acta-clickable-row';
      tr.setAttribute('data-hora', horaStr);
      tr.setAttribute('data-ptr', ptr || '-');
      tr.setAttribute('data-recinto', recinto);
      tr.setAttribute('data-oficial', oficial);
      tr.setAttribute('data-ayudante', ayudanteNombre);
      tr.setAttribute('data-texto', texto);
      tr.setAttribute('data-dosis', dosis);
      tr.setAttribute('data-tritio', tritio);
      tr.setAttribute('data-derrame', derrame);
      tr.setAttribute('data-aerosoles', aerosolesDac || (aerosoles ? 'SÍ' : '0.0'));
      tr.onclick = function(e) {
        if (!e.target.closest('.btn-del')) {
          abrirModalDetalleActaFromRow(this);
        }
      };

      tr.innerHTML = `
        <td class="mono">${horaStr}</td>
        <td class="mono" style="${ptr ? 'color: var(--accent-cyan); font-weight:700;' : 'color: var(--text-dim);'}">${ptr ? ptr : '-'}</td>
        <td style="font-weight:600;">${recinto.split(' - ')[0]}</td>
        <td style="font-weight:700;">${oficial}</td>
        <td>${ayudanteNombre}</td>
        <td class="text-left" style="max-width: 320px;">
          <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 250px; display: inline-block;">${texto}</span>
            <button type="button" class="btn-acta-open" title="Abrir y ver acta completa">🔍 Ver</button>
          </div>
        </td>
        <td class="mono" style="text-align: right; color: ${parseFloat(dosis) > 0 ? 'var(--accent-amber); font-weight:700;' : 'var(--text-dim);'}">${dosis}</td>
        <td>${derrame ? '<span class="badge-amber">SÍ (D2O)</span>' : '<span style="color:var(--text-dim)">-</span>'}</td>
        <td style="text-align: center;" onclick="event.stopPropagation();"><button class="btn-del" onclick="deleteRow(this)">✕</button></td>
      `;
      tbody.prepend(tr);

      // 2. Actualizar badge contador
      const count = tbody.querySelectorAll('tr').length;
      const badge = document.getElementById('menu-badge-actas');
      if (badge) badge.textContent = count;
      const countHeader = document.getElementById('actas-counter-badge');
      if (countHeader) countHeader.textContent = count + ' actas asentadas en este turno';

      // 3. Encolar en SyncEngine
      SyncEngine.enqueue('acta_tarea', 'procesos_reg_rad_post.php', 'POST', {
        accion: 'insertar_tarea_diaria',
        id_planilla: (typeof activePlanillaId !== 'undefined' ? activePlanillaId : 1),
        id_usuario: 1,
        texto: texto,
        recinto: recinto,
        ptr: ptr,
        dosis: dosis,
        tritio: tritio,
        cont_beta: contBeta,
        cont_alfa: contAlfa,
        derrame: derrame,
        aerosoles: aerosoles,
        aerosoles_dac: aerosolesDac,
        id_oficial_asociado: (oficialSelect ? oficialSelect.value : ''),
        id_ayudante: ayudanteId
      });

      showToast('✔️ Acta de guardia asentada correctamente');

      // Limpiar formulario con los nuevos buscadores
      document.getElementById('acta-texto').value = '';
      if (window.clearActaAyudante) window.clearActaAyudante();
      if (window.clearActaRecinto) window.clearActaRecinto();
      if (window.clearActaPtr) window.clearActaPtr();
      document.getElementById('acta-dosis').value = '';
      document.getElementById('acta-tritio').value = '';
      document.getElementById('acta-cont-beta').value = '';
      document.getElementById('acta-cont-alfa').value = '';
      document.getElementById('acta-derrame').checked = false;
      document.getElementById('acta-aerosoles').checked = false;
      document.getElementById('acta-aerosoles-dac').style.display = 'none';
      document.getElementById('acta-aerosoles-dac').value = '';
    }

      // ================= MODAL INTERACTIVO DE HISTORIAL POR RECINTO GUARDADO =================
    let activeSelectedRecintoForPrefill = '';

    function openRecintoHistoryModal(recintoParam) {
      const modal = document.getElementById('modal-recinto-historial');
      const labelNombre = document.getElementById('mrh-recinto-nombre');
      const container = document.getElementById('mrh-chips-container');
      const trendBadge = document.getElementById('mrh-trend-badge');
      const prefillBtn = document.getElementById('mrh-btn-prefill');
      if (!modal || !container) return;

      activeSelectedRecintoForPrefill = recintoParam;
      if (labelNombre) labelNombre.textContent = recintoParam;

      // 1. Obtener historial: extraer mediciones reales de la tabla viva si existen
      const norm = (recintoParam || '').toLowerCase().trim();
      const tableRows = Array.from(document.querySelectorAll('#table-body tr')).filter(tr => {
        const r = (tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent : '')).toLowerCase().trim();
        return r && (r === norm || r.startsWith(norm) || norm.startsWith(r));
      });

      const history = [];
      tableRows.forEach(tr => {
        if (history.length >= 3) return;
        const f = tr.getAttribute('data-fecha') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '08/11/2026');
        const h = tr.getAttribute('data-hora') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '00:00');
        const d = tr.getAttribute('data-dosis') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '0.0');
        const t = tr.getAttribute('data-tritio') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '0.0');
        const g = tr.getAttribute('data-gases') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '0.0');
        const fClean = (!f || f.toLowerCase().includes('turno')) ? '08/11/2026' : f;
        history.push({
          fecha: fClean,
          hora: h,
          dosis: parseFloat(d).toFixed(1),
          tritio: parseFloat(t).toFixed(2),
          gases: parseFloat(g).toFixed(2)
        });
      });

      const idMatch = (typeof recintoParam === 'string') ? recintoParam.match(/R-?(\d+)/i) : null;
      const recintoId = idMatch ? parseInt(idMatch[1], 10).toString() : '1';

      const fallbackList = (typeof mockRecintoHistory !== 'undefined' && mockRecintoHistory[recintoId]) 
        ? mockRecintoHistory[recintoId] 
        : [
            { fecha: '08/11/2026', hora: '23:30', dosis: '4.5', tritio: '0.40', gases: '0.00' },
            { fecha: '08/11/2026', hora: '15:40', dosis: '4.0', tritio: '0.35', gases: '0.00' },
            { fecha: '08/11/2026', hora: '08:10', dosis: '3.8', tritio: '0.30', gases: '0.00' }
          ];

      fallbackList.forEach(fb => {
        if (history.length < 3) {
          const fbClean = (!fb.fecha || fb.fecha.toLowerCase().includes('turno')) ? '08/11/2026' : fb.fecha;
          history.push({
            fecha: fbClean,
            hora: fb.hora,
            dosis: fb.dosis,
            tritio: fb.tritio,
            gases: fb.gases
          });
        }
      });

      // 2. Renderizar las 3 mediciones con SOLO Fecha y Hora en orden: G. nobles, Tritio y Dosis
      container.innerHTML = history.map((item, idx) => `
        <div style="background: rgba(9, 28, 24, 0.85); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-cyan); border-radius: 6px; padding: 9px 14px; display: flex; align-items: center; justify-content: space-between; font-family: var(--font-mono); font-size: 11.5px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-weight: 800; color: var(--accent-cyan); font-size: 12px;">[${idx + 1}]</span>
            <span style="color: #ffffff; font-weight: 700;">📅 ${item.fecha} &bull; ${item.hora} hs</span>
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="color: #c084fc; font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">G. nobles:</span> ${item.gases} DAC</span>
            <span style="color: var(--border-card);">|</span>
            <span style="color: var(--accent-cyan); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Tritio:</span> ${item.tritio} DAC</span>
            <span style="color: var(--border-card);">|</span>
            <span style="color: var(--accent-amber); font-weight: 700;"><span style="color: var(--text-dim); font-size: 10px; font-weight: 600;">Dosis:</span> ${item.dosis} µSv/h</span>
          </div>
        </div>
      `).join('');

      // 3. Evaluar tendencia
      const d1 = parseFloat(history[0].dosis);
      const d3 = parseFloat(history[2].dosis);
      if (d1 > d3 * 1.3) {
        trendBadge.innerHTML = '📈 Alza de Dosis (+ ' + Math.round(((d1 - d3)/d3)*100) + '%)';
        trendBadge.style.color = 'var(--accent-amber)';
      } else {
        trendBadge.innerHTML = '🟢 Niveles estables';
        trendBadge.style.color = 'var(--accent-green)';
      }

      if (prefillBtn && !document.getElementById('q-recinto')) {
        prefillBtn.style.display = 'none';
      }

      modal.style.display = 'flex';
    }

    function closeRecintoHistoryModal() {
      const modal = document.getElementById('modal-recinto-historial');
      if (modal) modal.style.display = 'none';
    }

    function prefillRecintoForm() {
      closeRecintoHistoryModal();
      const input = document.getElementById('q-recinto');
      if (input && activeSelectedRecintoForPrefill) {
        input.value = activeSelectedRecintoForPrefill;
        const clearBtn = document.getElementById('btn-clear-recinto');
        if (clearBtn) clearBtn.style.display = 'block';
        if (typeof updateRecintoHistory === 'function') {
          updateRecintoHistory('1', activeSelectedRecintoForPrefill);
        }
        const tabRutina = document.querySelector('.menu-btn[data-section="sec-rutina"]');
        if (tabRutina) tabRutina.click();
        const nextInput = document.getElementById('q-dosis') || document.getElementById('q-gases');
        if (nextInput) nextInput.focus();
      }
    }

      // ================= MODAL DE DETALLE Y LECTURA COMPLETA DE ACTA =================
    function abrirModalDetalleActaFromRow(tr) {
      if (!tr) return;
      const hora = tr.getAttribute('data-hora') || (tr.cells[0] ? tr.cells[0].textContent.trim() : '');
      const ptr = tr.getAttribute('data-ptr') || (tr.cells[1] ? tr.cells[1].textContent.trim() : '');
      const recinto = tr.getAttribute('data-recinto') || (tr.cells[2] ? tr.cells[2].textContent.trim() : '');
      const oficial = tr.getAttribute('data-oficial') || (tr.cells[3] ? tr.cells[3].textContent.trim() : '');
      const ayudante = tr.getAttribute('data-ayudante') || (tr.cells[4] ? tr.cells[4].textContent.trim() : '');
      const texto = tr.getAttribute('data-texto') || (tr.cells[5] ? tr.cells[5].textContent.trim() : '');
      const dosis = tr.getAttribute('data-dosis') || (tr.cells[6] ? tr.cells[6].textContent.trim() : '');
      const derrame = tr.getAttribute('data-derrame') || ((tr.cells[7] && tr.cells[7].textContent.indexOf('SÍ') !== -1) ? '1' : '0');
      const tritio = tr.getAttribute('data-tritio') || '0.0';
      const aerosoles = tr.getAttribute('data-aerosoles') || '-';

      abrirModalDetalleActa({ hora, ptr, recinto, oficial, ayudante, texto, dosis, derrame, tritio, aerosoles });
    }

    function abrirModalDetalleActa(d) {
      const modal = document.getElementById('modal-detalle-acta');
      if (!modal) return;

      document.getElementById('mda-hora-ptr').textContent = (d.ptr && d.ptr !== '-') ? ('PTR #' + d.ptr + ' • ' + d.hora + ' hs') : ('Acta de Guardia • ' + d.hora + ' hs');
      document.getElementById('mda-hora').textContent = d.hora + ' hs';
      document.getElementById('mda-ptr').textContent = (d.ptr && d.ptr !== '-') ? d.ptr : 'Sin PTR';
      document.getElementById('mda-recinto').textContent = d.recinto || 'General de Planta';
      document.getElementById('mda-oficial').textContent = d.oficial || '-';
      document.getElementById('mda-ayudante').textContent = (d.ayudante && d.ayudante !== '-') ? d.ayudante : 'Sin ayudante';
      
      const derrameEl = document.getElementById('mda-derrame');
      if (d.derrame === '1' || d.derrame === 1 || String(d.derrame).indexOf('SÍ') !== -1) {
        derrameEl.innerHTML = '<span style="color: var(--accent-amber);">⚠️ SÍ (D2O)</span>';
      } else {
        derrameEl.innerHTML = '<span style="color: var(--accent-green);">🟢 NO</span>';
      }

      document.getElementById('mda-dosis').textContent = 'Dosis: ' + (d.dosis || '0.0') + ' µSv/h';
      document.getElementById('mda-tritio').textContent = 'Tritio: ' + (d.tritio || '0.0') + ' DAC';
      document.getElementById('mda-aerosoles').textContent = 'Aerosoles: ' + (d.aerosoles || '0.0') + ' DAC';
      document.getElementById('mda-texto-completo').textContent = d.texto || 'Sin observaciones';

      modal.style.display = 'flex';
    }

    function closeActaModal() {
      const modal = document.getElementById('modal-detalle-acta');
      if (modal) modal.style.display = 'none';
    }

    function copiarTextoActa() {
      const txt = document.getElementById('mda-texto-completo') ? document.getElementById('mda-texto-completo').textContent : '';
      if (navigator.clipboard) {
        navigator.clipboard.writeText(txt).then(() => {
          if (typeof showToast === 'function') showToast('📋 Descripción copiada al portapapeles');
        });
      } else {
        if (typeof showToast === 'function') showToast('📋 Texto disponible en pantalla');
      }
    }

    // ====================================================
    // MOTOR DE TOOLTIPS CYBERPUNK (REEMPLAZO TOOLTIP NATIVO)
    // ====================================================
    (function initCyberTooltips() {
      let tooltipEl = document.getElementById('cyber-tooltip');
      if (!tooltipEl) {
        tooltipEl = document.createElement('div');
        tooltipEl.id = 'cyber-tooltip';
        document.body.appendChild(tooltipEl);
      }

      let activeTarget = null;

      function showTooltip(el, text) {
        if (!text || !text.trim()) return;
        activeTarget = el;

        tooltipEl.className = '';
        const isAlert = text.includes('⚠️') || text.includes('Alza') || text.includes('Supera') || text.includes('Discrepancia') || el.classList.contains('badge-amber');
        const isDanger = text.includes('🚨') || text.includes('PELIGRO') || text.includes('CRÍTICO') || el.classList.contains('badge-red');

        if (isDanger) {
          tooltipEl.classList.add('danger');
        } else if (isAlert) {
          tooltipEl.classList.add('alert');
        }

        tooltipEl.textContent = text;
        tooltipEl.classList.add('visible');
        positionTooltip(el);
      }

      function positionTooltip(el) {
        if (!tooltipEl.classList.contains('visible')) return;
        const rect = el.getBoundingClientRect();
        const tipRect = tooltipEl.getBoundingClientRect();
        const margin = 8;

        let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
        if (left < 10) left = 10;
        if (left + tipRect.width > window.innerWidth - 10) {
          left = window.innerWidth - tipRect.width - 10;
        }

        let top = rect.top - tipRect.height - margin;
        if (top < 10) {
          top = rect.bottom + margin;
        }

        tooltipEl.style.left = Math.round(left) + 'px';
        tooltipEl.style.top = Math.round(top) + 'px';
      }

      function hideTooltip() {
        activeTarget = null;
        tooltipEl.classList.remove('visible');
      }

      // Interceptar mouseover en captura para suprimir de inmediato title nativo blanco
      document.addEventListener('mouseover', function(e) {
        const el = e.target.closest('[title], [data-tooltip]');
        if (!el) return;

        if (el.hasAttribute('title')) {
          const titleVal = el.getAttribute('title');
          if (titleVal && titleVal.trim()) {
            el.setAttribute('data-tooltip', titleVal);
          }
          el.removeAttribute('title');
        }

        const tipText = el.getAttribute('data-tooltip');
        if (tipText) {
          showTooltip(el, tipText);
        }
      }, true);

      document.addEventListener('mouseout', function(e) {
        if (!activeTarget) return;
        if (e.relatedTarget && activeTarget.contains(e.relatedTarget)) {
          return;
        }
        hideTooltip();
      }, true);

      window.addEventListener('scroll', function() {
        if (activeTarget) positionTooltip(activeTarget);
      }, { passive: true });
    })();
    // ====================================================
    // GESTIÓN DE RELEVO DE GUARDIA Y ADVERTENCIA DE PLANILLA ABIERTA
    // ====================================================
    const hayDiscrepanciaGuardia = <?php echo $hay_discrepancia_guardia ? 'true' : 'false'; ?>;
    const oficialIniciador = <?php echo json_encode($oficial_iniciador_nombre); ?>;
    const oficialLogueado = <?php echo json_encode($oficial_logueado); ?>;

    function openHandoverModal() {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'flex';
    }

    function closeHandoverModal(isDismiss) {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'none';
      if (isDismiss) {
        sessionStorage.setItem('relevo_advertencia_desestimada_' + activePlanillaId, '1');
      }
    }

    let sinPlanillaAbierta = <?php echo !empty($sin_planilla_abierta) ? 'true' : 'false'; ?>;

    function mostrarPasoAdvertenciaCierre() {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'flex';
      const p1 = document.getElementById('handover-paso-gatekeeper');
      const p2 = document.getElementById('handover-paso-advertencia');
      if (p1) p1.style.display = 'none';
      if (p2) p2.style.display = 'block';
    }

    function closeHandoverModal(unlockWorkspace) {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'none';
      if (unlockWorkspace) {
        desbloquearParaNuevoTurno();
      }
    }

    function volverPasoGatekeeper() {
      const p1 = document.getElementById('handover-paso-gatekeeper');
      const p2 = document.getElementById('handover-paso-advertencia');
      if (p1) p1.style.display = 'block';
      if (p2) p2.style.display = 'none';
      const btn = document.getElementById('btn-ejecutar-cierre-def');
      const btnCancel = document.getElementById('btn-cancelar-cierre-def');
      const loader = document.getElementById('cierre-loading-indicator');
      if (btn) btn.style.display = '';
      if (btnCancel) btnCancel.style.display = '';
      if (loader) loader.style.display = 'none';
    }

    function desbloquearParaNuevoTurno() {
      const modal = document.getElementById('modal-traspaso-guardia');
      if (modal) modal.style.display = 'none';
      const sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        sidebar.style.pointerEvents = 'auto';
        sidebar.style.opacity = '1';
      }
      const workspace = document.querySelector('.workspace');
      if (workspace) {
        workspace.style.filter = 'none';
        workspace.style.pointerEvents = 'auto';
        workspace.style.userSelect = 'auto';
        workspace.style.opacity = '1';
      }
      const banner = document.getElementById('banner-relevo-guardia');
      if (banner) banner.style.display = 'none';
      hayDiscrepanciaGuardia = false;
      showToast('✨ Consola desbloqueada para su sesión. Configure su turno.');
      setTimeout(() => {
        if (typeof openShiftModal === 'function') {
          openShiftModal();
        }
      }, 350);
    }

    function ejecutarCierreDefinitivoYCrearNueva() {
      const btn = document.getElementById('btn-ejecutar-cierre-def');
      const btnCancel = document.getElementById('btn-cancelar-cierre-def');
      const loader = document.getElementById('cierre-loading-indicator');
      if (btn) btn.style.display = 'none';
      if (btnCancel) btnCancel.style.display = 'none';
      if (loader) loader.style.display = 'block';

      const planId = <?php echo (int)($id_planilla_activa ? $id_planilla_activa : 1); ?>;
      const uId = <?php echo (int)($id_usuario_actual ? $id_usuario_actual : 1); ?>;

      // Timeout de seguridad: si después de 2.2 segundos no respondió el servidor, desbloquear directamente
      const fallbackTimer = setTimeout(() => {
        desbloquearParaNuevoTurno();
      }, 2200);

      try {
        fetch(`procesos_reg_rad.php?accion=cerrar_planilla&id_planilla=${planId}&id_usuario=${uId}`)
          .then(r => r.text())
          .then(res => {
            clearTimeout(fallbackTimer);
            showToast('🔒 Planilla anterior cerrada con éxito en el sistema.', 3000);
            setTimeout(() => {
              window.location.href = 'demo_planilla.php?nueva_guardia=1';
            }, 600);
          })
          .catch(err => {
            clearTimeout(fallbackTimer);
            console.warn('Cierre fallback:', err);
            desbloquearParaNuevoTurno();
          });
      } catch (e) {
        clearTimeout(fallbackTimer);
        console.error(e);
        desbloquearParaNuevoTurno();
      }
    }

    function verPlanillaSoloLectura() {
      window.location.href = `planilla_en_curso_visualizacion.php?id_planilla=${activePlanillaId || <?php echo $id_planilla_activa ? $id_planilla_activa : 1; ?>}`;
    }

    // Auto-bloqueo obligatorio al cargar si hay discrepancia de guardia
    document.addEventListener('DOMContentLoaded', () => {
      if (hayDiscrepanciaGuardia) {
        const modal = document.getElementById('modal-traspaso-guardia');
        if (modal) {
          modal.style.display = 'flex';
          const p1 = document.getElementById('handover-paso-gatekeeper');
          const p2 = document.getElementById('handover-paso-advertencia');
          if (p1) p1.style.display = 'block';
          if (p2) p2.style.display = 'none';
        }
      } else if (sinPlanillaAbierta) {
        setTimeout(() => {
          openShiftModal();
          showToast('✨ No hay planilla abierta activa. Configure su nuevo turno.');
        }, 400);
      }
    });

  </script>
    <!-- MODAL HUD: HISTORIAL DE LOS ÚLTIMOS 3 VALORES DEL RECINTO SELECCIONADO -->
  <div class="modal-overlay" id="modal-recinto-historial" style="display: none;" onclick="if(event.target===this) closeRecintoHistoryModal();">
    <div class="modal-box" style="max-width: 620px; border-left: 4px solid var(--accent-cyan); animation: cyber-hologram-assemble 0.28s ease-out;">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.5px;">
            📍 Historial Dosimétrico del Recinto
          </div>
          <div id="mrh-recinto-nombre" style="font-size: 15px; font-weight: 800; color: #ffffff; margin-top: 2px;">
            -
          </div>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeRecintoHistoryModal()" style="background: none; border: none; color: var(--text-dim); font-size: 20px; cursor: pointer; padding: 4px 8px; border-radius: 4px;" title="Cerrar modal (Esc)">&times;</button>
      </div>
      <div class="modal-body" style="padding: 16px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; background: rgba(16, 185, 129, 0.08); border: 1px solid var(--border-card); border-radius: 6px; padding: 7px 12px;">
          <span style="font-size: 11px; color: var(--text-muted); font-weight: 700;">
            📋 Últimas 3 mediciones registradas en este recinto
          </span>
          <span id="mrh-trend-badge" style="font-size: 11px; font-weight: 700; color: var(--accent-green); font-family: var(--font-mono);">
            🟢 Niveles habituales
          </span>
        </div>

        <!-- Contenedor con las 3 tarjetas de historial (Fecha y Hora solamente, NO oficial, NO turno) -->
        <div id="mrh-chips-container" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px;">
          <!-- JS inyecta las filas -->
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
          <span style="font-size: 10px; color: var(--text-dim); font-family: var(--font-mono);">
            * Registro de Radioprotección CNE &bull; Solo Fecha y Hora
          </span>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-ghost" onclick="closeRecintoHistoryModal()" style="padding: 5px 12px; font-size: 12px; border-radius: 6px; border: 1px solid var(--border-card); background: transparent; color: var(--text-muted); cursor: pointer;">
              Cerrar
            </button>
            <button type="button" id="mrh-btn-prefill" class="btn-primary" onclick="prefillRecintoForm()" style="padding: 5px 14px; font-size: 12px; font-weight: 700; border-radius: 6px; background: var(--accent-cyan); color: #02120e; border: none; cursor: pointer;">
              ⚡ Medir en este recinto
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL HUD: DETALLE Y LECTURA COMPLETA DE ACTA DE GUARDIA -->
  <div class="modal-overlay" id="modal-detalle-acta" style="display: none;" onclick="if(event.target===this) closeActaModal();">
    <div class="modal-box" style="max-width: 720px; border-left: 4px solid var(--accent-cyan); animation: cyber-hologram-assemble 0.28s ease-out;">
      <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 20px;">📖</span>
          <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; letter-spacing: 0.5px;">
              Libro de Actas &bull; Registro Completo
            </div>
            <div id="mda-hora-ptr" style="font-size: 15px; font-weight: 800; color: #ffffff; margin-top: 1px;">
              -
            </div>
          </div>
        </div>
        <button type="button" class="btn-modal-close" onclick="closeActaModal()" style="background: none; border: none; color: var(--text-dim); font-size: 20px; cursor: pointer; padding: 4px 8px; border-radius: 4px;" title="Cerrar modal (Esc)">&times;</button>
      </div>

      <div class="modal-body" style="padding: 16px 22px;">
        <!-- Ficha técnica en tarjetas compactas -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px;">
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Hora / Momento</div>
            <div id="mda-hora" style="font-size: 13px; font-weight: 800; color: #ffffff; font-family: var(--font-mono); margin-top: 2px;">-</div>
          </div>
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">N° PTR</div>
            <div id="mda-ptr" style="font-size: 13px; font-weight: 800; color: var(--accent-cyan); font-family: var(--font-mono); margin-top: 2px;">-</div>
          </div>
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Recinto</div>
            <div id="mda-recinto" style="font-size: 12px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">-</div>
          </div>
          <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid var(--border-card); border-radius: 6px; padding: 8px 10px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Derrame D2O</div>
            <div id="mda-derrame" style="font-size: 12px; font-weight: 800; margin-top: 2px;">-</div>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
          <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 8px 12px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Oficial Responsable</div>
            <div id="mda-oficial" style="font-size: 12.5px; font-weight: 700; color: #ffffff; margin-top: 2px;">-</div>
          </div>
          <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-subtle); border-radius: 6px; padding: 8px 12px;">
            <div style="font-size: 9.5px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Personal Asistente / Ayudante</div>
            <div id="mda-ayudante" style="font-size: 12.5px; font-weight: 700; color: var(--text-muted); margin-top: 2px;">-</div>
          </div>
        </div>

        <!-- Parámetros Radiológicos adicionales -->
        <div style="display: flex; gap: 10px; margin-bottom: 14px; background: rgba(9, 28, 24, 0.7); border: 1px solid var(--border-card); border-radius: 6px; padding: 7px 12px; font-family: var(--font-mono); font-size: 11px;">
          <span style="color: var(--text-muted);">Valores de radioprotección:</span>
          <span style="color: var(--accent-amber); font-weight: 700;" id="mda-dosis">Dosis: -</span>
          <span style="color: var(--text-dim);">|</span>
          <span style="color: var(--accent-cyan); font-weight: 700;" id="mda-tritio">Tritio: -</span>
          <span style="color: var(--text-dim);">|</span>
          <span style="color: #c084fc; font-weight: 700;" id="mda-aerosoles">Aerosoles: -</span>
        </div>

        <!-- Descripción Completa sin límite de renglón -->
        <div style="margin-bottom: 16px;">
          <div style="font-size: 11px; font-weight: 700; color: var(--accent-cyan); text-transform: uppercase; margin-bottom: 6px; display: flex; align-items: center; justify-content: space-between;">
            <span>📝 Descripción Completa / Novedad Asentada:</span>
            <span style="font-size: 10px; color: var(--text-dim); text-transform: none; font-weight: normal;">(Visualización completa del texto)</span>
          </div>
          <div id="mda-texto-completo" style="background: rgba(3, 15, 12, 0.95); border: 1px solid var(--border-card); border-left: 3px solid var(--accent-cyan); border-radius: 6px; padding: 14px 16px; font-size: 13px; line-height: 1.6; color: #ffffff; white-space: pre-wrap; word-break: break-word; max-height: 280px; overflow-y: auto; font-family: var(--font-sans);">
            -
          </div>
        </div>

        <!-- Acciones Footer -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 12px;">
          <button type="button" class="btn-ghost" onclick="copiarTextoActa()" id="btn-copiar-acta" style="padding: 6px 14px; font-size: 11.5px; border-radius: 6px; border: 1px solid var(--border-card); background: rgba(255,255,255,0.05); color: var(--text-main); cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
            <span>📋</span> Copiar Descripción
          </button>
          <button type="button" class="btn-primary" onclick="closeActaModal()" style="padding: 6px 18px; font-size: 12px; font-weight: 700; border-radius: 6px; background: var(--accent-cyan); color: #02120e; border: none; cursor: pointer;">
            Cerrar
          </button>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
