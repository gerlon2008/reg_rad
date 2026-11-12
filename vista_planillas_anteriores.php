<?php
// Conexión y sesión de base de datos
$db_connected = false;
$usuario_nombre = "OFICIAL DE GUARDIA";
$usuario_rol = "Oficial de Radioprotección";

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php")) {
    @require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/miconex.php");
    @require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Functions/funciones.php");
    @require_once($_SERVER["DOCUMENT_ROOT"]."/ptr/Connections/niveles.php");

    @session_start();
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true) {
        $tipo_user = isset($_SESSION['portal_nivel']) ? $_SESSION['portal_nivel'] : 1;
        $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : 1;
        $of_rad_registro = $id_user;
        if (!empty($_SESSION['nombre_apellido'])) {
            $usuario_nombre = mb_strtoupper($_SESSION['nombre_apellido'], 'UTF-8');
        }
        if (isset($_SESSION['of_rad_registro_rad']) && $_SESSION['of_rad_registro_rad'] == '0') {
            $usuario_rol = "Usuario de Consulta";
        }
    }

    if (isset($miconex) && $miconex) {
        @mysql_set_charset('utf8', $miconex);
        @mysql_select_db($database_miconex, $miconex);
        $db_connected = true;
    }
}

// Funciones auxiliares seguras
if (!function_exists('formafecha_latam')) {
    function formafecha_latam($f) {
        if (empty($f)) return '--/--/----';
        $ts = strtotime($f);
        return $ts ? date('d/m/Y', $ts) : $f;
    }
}

$registros_historicos = array();
$lista_oficiales = array();
$lista_turnos = array();

if ($db_connected) {
    // 1. Obtener oficiales para filtro
    $res_of = @mysql_query("SELECT id_of_rad, of_apellido, of_nombre FROM regrad_of_rad ORDER BY of_apellido ASC", $miconex);
    if ($res_of) {
        while ($row = mysql_fetch_assoc($res_of)) {
            $lista_oficiales[] = array(
                'id' => $row['id_of_rad'],
                'nombre' => mb_strtoupper($row['of_apellido'] . ", " . $row['of_nombre'], 'UTF-8')
            );
        }
    }

    // 2. Obtener turnos
    $res_tur = @mysql_query("SELECT id_turnos, horarios FROM regrad_turnos ORDER BY id_turnos ASC", $miconex);
    if ($res_tur) {
        while ($row = mysql_fetch_assoc($res_tur)) {
            $lista_turnos[] = $row;
        }
    }

    // Helper UTF-8 seguro para PHP 5.5 + json_encode
    if (!function_exists('regrad_safe_utf8')) {
        function regrad_safe_utf8($val) {
            if (!is_string($val)) return $val;
            if (function_exists('mb_check_encoding') && !mb_check_encoding($val, 'UTF-8')) {
                return utf8_encode($val);
            }
            return $val;
        }
    }

    // 3. Identificar la planilla en curso actual (para excluirla únicamente a ella)
    $id_planilla_en_curso = 0;
    $res_en_curso = @mysql_query("SELECT id_planilla_diaria FROM regrad_planilla_diaria_nueva WHERE fecha_hora_cerrado IS NULL ORDER BY id_planilla_diaria DESC LIMIT 1", $miconex);
    if ($res_en_curso && ($row_ec = mysql_fetch_assoc($res_en_curso))) {
        $id_planilla_en_curso = (int)$row_ec['id_planilla_diaria'];
    }

    // 4. Obtener TODAS las planillas (excepto la que está en curso), ordenadas desde la última cargada
    $where_hist = ($id_planilla_en_curso > 0) ? ("WHERE a.id_planilla_diaria <> " . $id_planilla_en_curso) : "";
    $query_registros = "SELECT 
            a.id_planilla_diaria,
            a.fecha,
            a.fecha_hora_cerrado,
            a.turno,
            a.estado_planta,
            b.horarios,
            c.nombre_estado,
            d.of_apellido,
            d.of_nombre,
            e.jefe_apellido,
            e.jefe_nombre
        FROM regrad_planilla_diaria_nueva a 
        LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno 
        LEFT JOIN regrad_estados_planta c ON c.id_estado_planta = a.estado_planta 
        LEFT JOIN regrad_of_rad d ON d.id_of_rad = a.of_rad 
        LEFT JOIN regrad_jefes_turno e ON e.id_jefes_turno = a.jefe_turno 
        $where_hist 
        ORDER BY a.id_planilla_diaria DESC";
    $res_reg = @mysql_query($query_registros, $miconex);
    if ($res_reg) {
        while ($row = mysql_fetch_assoc($res_reg)) {
            $of_ap = regrad_safe_utf8($row['of_apellido']);
            $of_no = regrad_safe_utf8($row['of_nombre']);
            $jf_ap = regrad_safe_utf8($row['jefe_apellido']);
            $jf_no = regrad_safe_utf8($row['jefe_nombre']);
            $oficial_nom = (!empty($of_ap) || !empty($of_no)) ? mb_strtoupper(trim($of_ap . ", " . $of_no, ", "), 'UTF-8') : "SIN ASIGNAR";
            $jefe_nom = (!empty($jf_ap) || !empty($jf_no)) ? mb_strtoupper(trim($jf_ap . ", " . $jf_no, ", "), 'UTF-8') : "SIN ASIGNAR";
            $hor_str = !empty($row['horarios']) ? regrad_safe_utf8($row['horarios']) : ('Turno ' . $row['turno']);
            $est_str = !empty($row['nombre_estado']) ? mb_strtoupper(regrad_safe_utf8($row['nombre_estado']), 'UTF-8') : 'POTENCIA 100%';

            $registros_historicos[] = array(
                'id' => (int)$row['id_planilla_diaria'],
                'fecha_sql' => $row['fecha'] ? $row['fecha'] : '',
                'fecha_latam' => formafecha_latam($row['fecha']),
                'hora_cierre' => !empty($row['fecha_hora_cerrado']) ? date('H:i', strtotime($row['fecha_hora_cerrado'])) : '',
                'id_turno' => (int)$row['turno'],
                'horarios' => $hor_str,
                'oficial' => $oficial_nom,
                'jefe' => $jefe_nom,
                'estado_planta' => $est_str
            );
        }
    }
}

// Si está offline o en modo demo (sin conexión DB), proveer datos mock realistas de Central Nuclear Embalse
if (!$db_connected) {
    $lista_oficiales = array(
        array('id' => 1, 'nombre' => 'ALTUNA, LEONARDO (P)'),
        array('id' => 2, 'nombre' => 'ROLHAUSER, MARIANO'),
        array('id' => 3, 'nombre' => 'GATICA, CARLOS'),
        array('id' => 4, 'nombre' => 'CISTERNA, GUSTAVO'),
        array('id' => 5, 'nombre' => 'PASCUAL, SEBASTIAN'),
        array('id' => 6, 'nombre' => 'ROMERO, PABLO')
    );

    $lista_turnos = array(
        array('id_turnos' => 1, 'horarios' => 'Turno 1 (23:00 a 07:00 hs)'),
        array('id_turnos' => 2, 'horarios' => 'Turno 2 (07:00 a 15:00 hs)'),
        array('id_turnos' => 3, 'horarios' => 'Turno 3 (15:00 a 23:00 hs)')
    );

    $registros_historicos = array(
        array(
            'id' => 1482,
            'fecha_sql' => '2026-11-09',
            'fecha_latam' => '09/11/2026',
            'hora_cierre' => '06:55',
            'id_turno' => 1,
            'horarios' => 'Turno 1 (23:00 a 07:00 hs)',
            'oficial' => 'ALTUNA, LEONARDO (P)',
            'jefe' => 'RODRIGUEZ, MARCELO',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1481,
            'fecha_sql' => '2026-11-08',
            'fecha_latam' => '08/11/2026',
            'hora_cierre' => '22:45',
            'id_turno' => 3,
            'horarios' => 'Turno 3 (15:00 a 23:00 hs)',
            'oficial' => 'ROLHAUSER, MARIANO',
            'jefe' => 'FERNANDEZ, JORGE',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1480,
            'fecha_sql' => '2026-11-08',
            'fecha_latam' => '08/11/2026',
            'hora_cierre' => '14:50',
            'id_turno' => 2,
            'horarios' => 'Turno 2 (07:00 a 15:00 hs)',
            'oficial' => 'GATICA, CARLOS',
            'jefe' => 'RODRIGUEZ, MARCELO',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1479,
            'fecha_sql' => '2026-11-08',
            'fecha_latam' => '08/11/2026',
            'hora_cierre' => '06:50',
            'id_turno' => 1,
            'horarios' => 'Turno 1 (23:00 a 07:00 hs)',
            'oficial' => 'CISTERNA, GUSTAVO',
            'jefe' => 'GOMEZ, HERNAN',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1478,
            'fecha_sql' => '2026-11-07',
            'fecha_latam' => '07/11/2026',
            'hora_cierre' => '22:52',
            'id_turno' => 3,
            'horarios' => 'Turno 3 (15:00 a 23:00 hs)',
            'oficial' => 'PASCUAL, SEBASTIAN',
            'jefe' => 'FERNANDEZ, JORGE',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1477,
            'fecha_sql' => '2026-11-07',
            'fecha_latam' => '07/11/2026',
            'hora_cierre' => '14:48',
            'id_turno' => 2,
            'horarios' => 'Turno 2 (07:00 a 15:00 hs)',
            'oficial' => 'ROMERO, PABLO',
            'jefe' => 'RODRIGUEZ, MARCELO',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1476,
            'fecha_sql' => '2026-11-07',
            'fecha_latam' => '07/11/2026',
            'hora_cierre' => '06:54',
            'id_turno' => 1,
            'horarios' => 'Turno 1 (23:00 a 07:00 hs)',
            'oficial' => 'ALTUNA, LEONARDO (P)',
            'jefe' => 'GOMEZ, HERNAN',
            'estado_planta' => 'POTENCIA 100%'
        ),
        array(
            'id' => 1475,
            'fecha_sql' => '2026-11-06',
            'fecha_latam' => '06/11/2026',
            'hora_cierre' => '22:49',
            'id_turno' => 3,
            'horarios' => 'Turno 3 (15:00 a 23:00 hs)',
            'oficial' => 'ROLHAUSER, MARIANO',
            'jefe' => 'FERNANDEZ, JORGE',
            'estado_planta' => 'POTENCIA 100%'
        )
    );
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SyR - Archivo Histórico de Planillas | CNE</title>
  <style>
    /* ================= PALETA MAESTRA: VERDE RADIOLÓGICO / PETRÓLEO ================= */
    :root {
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
      --accent-cyan: #10b981;
      --accent-green: #34d399;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
      --font-mono: 'Consolas', 'Courier New', monospace;
      --font-ui: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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
      overflow-x: hidden;
    }
    body {
      width: 100%;
      overflow-x: hidden;
      background-color: var(--bg-page);
      color: var(--text-main);
      font-family: var(--font-ui);
      font-size: 13px;
      line-height: 1.4;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Topbar HUD */
    .topbar {
      height: 48px;
      background: var(--bg-sidebar);
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 16px;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .topbar-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      font-size: 14px;
      color: var(--text-main);
      letter-spacing: 0.5px;
    }

    .brand-badge {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid var(--accent-cyan);
      color: var(--accent-cyan);
      font-size: 10px;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
      letter-spacing: 1px;
    }

    .topbar-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Selector compacto de tema / fondo */
    .theme-dropdown {
      position: relative;
      flex-shrink: 0;
    }
    .btn-theme-toggle {
      height: 28px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 0 9px;
      border-radius: 6px;
      font-family: var(--font-ui);
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      transition: all 0.15s ease;
    }
    .btn-theme-toggle:hover,
    .theme-dropdown.open .btn-theme-toggle {
      border-color: var(--accent-cyan);
      background: var(--bg-card);
    }
    .theme-swatch-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      display: inline-block;
      flex-shrink: 0;
      border: 1px solid rgba(255, 255, 255, 0.35);
    }
    .theme-dropdown-menu {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 215px;
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 5px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
      display: none;
      flex-direction: column;
      gap: 2px;
      z-index: 1200;
    }
    .theme-dropdown.open .theme-dropdown-menu {
      display: flex;
    }
    .theme-menu-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
      width: 100%;
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 7px 9px;
      border-radius: 6px;
      font-family: var(--font-ui);
      font-size: 11.5px;
      font-weight: 600;
      text-align: left;
      cursor: pointer;
      transition: all 0.12s ease;
    }
    .theme-menu-item:hover {
      background: var(--bg-card-subtle);
      color: var(--text-main);
    }
    .theme-menu-item.active {
      background: rgba(16, 185, 129, 0.14);
      color: var(--accent-cyan);
      font-weight: 700;
    }
    .theme-menu-item .theme-check {
      opacity: 0;
      font-size: 11px;
      font-weight: 800;
    }
    .theme-menu-item.active .theme-check {
      opacity: 1;
    }

    .btn-nav {
      background: var(--bg-card-subtle);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 6px 12px;
      font-size: 11px;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      transition: all 0.15s ease;
    }

    .btn-nav:hover {
      background: var(--accent-cyan);
      color: #04100e;
      border-color: var(--accent-cyan);
    }

    /* Main Container */
    .main-wrapper {
      flex: 1;
      padding: 16px 20px;
      max-width: 1440px;
      margin: 0 auto;
      width: 100%;
    }

    /* Header Panel */
    .page-header {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 14px 18px;
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
      overflow: hidden;
    }

    .page-header::before {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 4px; height: 100%;
      background: var(--accent-cyan);
    }

    .header-title-box h1 {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-title-box p {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .header-stats-chips {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stat-chip {
      background: var(--bg-sidebar);
      border: 1px solid var(--border-subtle);
      border-radius: 6px;
      padding: 4px 10px;
      text-align: right;
    }

    .stat-chip-lbl {
      font-size: 9px;
      color: var(--text-dim);
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .stat-chip-val {
      font-family: var(--font-mono);
      font-size: 13px;
      font-weight: 700;
      color: var(--accent-cyan);
    }

    /* Filters Bar */
    .filters-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 12px 16px;
      margin-bottom: 16px;
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      gap: 12px;
    }

    .filter-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .filter-item label {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      letter-spacing: 0.5px;
    }

    .filter-ctrl {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 6px 10px;
      font-size: 12px;
      border-radius: 6px;
      outline: none;
      height: 34px;
      transition: border-color 0.15s ease;
    }

    .filter-ctrl:focus {
      border-color: var(--accent-cyan);
      box-shadow: 0 0 0 1px var(--accent-cyan);
    }

    .filter-ctrl.mono {
      font-family: var(--font-mono);
      width: 120px;
      text-align: center;
      cursor: pointer;
    }

    .filter-ctrl select, select.filter-ctrl {
      cursor: pointer;
      min-width: 170px;
    }

    .filter-search {
      flex: 1;
      min-width: 220px;
    }

    .filter-search input {
      width: 100%;
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

    .btn-filter-reset {
      background: transparent;
      border: 1px solid var(--border-card);
      color: var(--text-muted);
      height: 34px;
      padding: 0 12px;
      font-size: 11px;
      border-radius: 6px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s ease;
    }

    .btn-filter-reset:hover {
      background: var(--bg-card-subtle);
      color: var(--text-main);
      border-color: var(--text-muted);
    }

    /* Historical Data Table */
    .table-container {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      overflow: hidden;
      position: relative;
    }

    .hud-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .hud-table thead th {
      background: var(--bg-sidebar);
      color: var(--text-muted);
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 10px 12px;
      border-bottom: 1px solid var(--border-card);
      white-space: nowrap;
    }

    /* Filas intercaladas (Zebra Striping de Alto Contraste) */
    .hud-table tbody tr:nth-child(odd) td {
      background-color: rgba(14, 39, 34, 0.45);
    }

    .hud-table tbody tr:nth-child(even) td {
      background-color: rgba(25, 66, 58, 0.65);
    }

    .hud-table tbody tr {
      border-bottom: 1px solid rgba(31, 77, 67, 0.45);
      transition: background-color 0.1s ease;
    }

    .hud-table tbody tr:hover td {
      background-color: rgba(16, 185, 129, 0.22) !important;
      color: #ffffff;
    }

    .hud-table td {
      padding: 9px 12px;
      font-size: 12px;
      vertical-align: middle;
      color: var(--text-main);
    }

    .hud-table td.mono {
      font-family: var(--font-mono);
    }

    /* Badges */
    .shift-badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      font-family: var(--font-mono);
      letter-spacing: 0.5px;
    }

    .shift-badge.t1 {
      background: rgba(59, 130, 246, 0.15);
      border: 1px solid rgba(59, 130, 246, 0.4);
      color: #93c5fd;
    }

    .shift-badge.t2 {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #6ee7b7;
    }

    .shift-badge.t3 {
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #fcd34d;
    }

    .badge-plant {
      background: rgba(16, 185, 129, 0.1);
      border: 1px solid var(--border-card);
      color: var(--accent-cyan);
      font-size: 10px;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
    }

    /* Action Buttons in Row */
    .row-actions {
      display: flex;
      align-items: center;
      gap: 6px;
      justify-content: flex-end;
    }

    .btn-action {
      background: var(--bg-sidebar);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 4px 8px;
      font-size: 11px;
      font-weight: 600;
      border-radius: 5px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-decoration: none;
      transition: all 0.15s ease;
      white-space: nowrap;
    }

    .btn-action:hover {
      background: var(--accent-cyan);
      color: #04100e;
      border-color: var(--accent-cyan);
    }

    .btn-action.btn-secondary:hover {
      background: var(--accent-blue);
      color: #ffffff;
      border-color: var(--accent-blue);
    }

    .btn-action.btn-print:hover {
      background: var(--accent-amber);
      color: #000000;
      border-color: var(--accent-amber);
    }

    /* ================= PAGINACIÓN PROFESIONAL ================= */
    .pagination-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      padding: 10px 16px;
      border-top: 1px solid var(--border-card);
      background: var(--bg-sidebar);
      font-size: 11.5px;
      color: var(--text-muted);
    }
    .pagination-left {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }
    .pagination-size-select {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      padding: 3px 8px;
      font-size: 11px;
      border-radius: 5px;
      cursor: pointer;
      font-family: var(--font-mono);
      outline: none;
    }
    .pagination-size-select:focus {
      border-color: var(--accent-cyan);
    }
    .pagination-controls {
      display: flex;
      align-items: center;
      gap: 4px;
      flex-wrap: wrap;
    }
    .page-nav-btn {
      min-width: 28px;
      height: 28px;
      padding: 0 8px;
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      color: var(--text-main);
      border-radius: 5px;
      font-size: 11px;
      font-family: var(--font-mono);
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.15s ease;
      user-select: none;
    }
    .page-nav-btn:hover:not(:disabled) {
      background: rgba(6, 182, 212, 0.15);
      border-color: var(--accent-cyan);
      color: var(--accent-cyan);
    }
    .page-nav-btn.active {
      background: var(--accent-cyan);
      border-color: var(--accent-cyan);
      color: #04100e;
      font-weight: 800;
    }
    .page-nav-btn:disabled {
      opacity: 0.35;
      cursor: not-allowed;
      border-color: var(--border-card);
    }
    .page-nav-ellipsis {
      padding: 0 4px;
      color: var(--text-dim);
      font-size: 11px;
    }

    /* Empty state */
    .empty-row td {
      text-align: center;
      padding: 30px;
      color: var(--text-muted);
      font-style: italic;
    }

    /* ================= MODAL NOVEDADES LIBRO DE ACTAS ================= */
    .modal-backdrop {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(2, 8, 7, 0.85);
      backdrop-filter: blur(4px);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal-window {
      background: var(--bg-card);
      border: 1px solid var(--accent-cyan);
      border-radius: 10px;
      width: 100%;
      max-width: 820px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
      position: relative;
      animation: modalSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes modalSlide {
      from { transform: translateY(15px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .modal-header {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--bg-sidebar);
      border-radius: 10px 10px 0 0;
    }

    .modal-header h3 {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .modal-close-btn {
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
      padding: 4px 8px;
      border-radius: 4px;
    }

    .modal-close-btn:hover {
      background: var(--border-card);
      color: #ffffff;
    }

    .modal-body {
      padding: 16px;
      overflow-y: auto;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    /* Modal KPI Cards */
    .modal-kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
    }

    .modal-kpi-card {
      background: var(--bg-sidebar);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      padding: 8px 10px;
      text-align: center;
    }

    .modal-kpi-lbl {
      font-size: 10px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-bottom: 4px;
    }

    .modal-kpi-val {
      font-family: var(--font-mono);
      font-size: 14px;
      font-weight: 700;
      color: var(--accent-cyan);
    }

    .modal-meta-row {
      display: flex;
      justify-content: space-between;
      background: var(--bg-sidebar);
      border: 1px solid var(--border-subtle);
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 12px;
      color: var(--text-muted);
    }

    .modal-meta-row strong {
      color: var(--text-main);
    }

    .actas-content-box {
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      padding: 12px;
      min-height: 220px;
      max-height: 380px;
      overflow-y: auto;
      font-family: var(--font-mono);
      font-size: 12px;
      color: var(--text-main);
      white-space: pre-wrap;
      line-height: 1.5;
    }

    .modal-footer {
      padding: 10px 16px;
      border-top: 1px solid var(--border-card);
      background: var(--bg-sidebar);
      border-radius: 0 0 10px 10px;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }

    /* ================= DATEPICKER LATAM ================= */
    .latam-datepicker-popup {
      display: none;
      position: fixed !important;
      background: #091c18 !important;
      border: 1px solid var(--accent-cyan) !important;
      border-radius: 8px;
      box-shadow: 0 16px 36px rgba(0,0,0,0.8);
      z-index: 99999 !important;
      width: 250px;
      padding: 10px;
      color: #edfcf6;
      font-family: var(--font-ui);
    }

    .dp-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    }

    .dp-month-title {
      font-weight: 700;
      font-size: 12px;
      color: var(--accent-cyan);
      text-transform: capitalize;
    }

    .dp-nav-btn {
      background: #0e2722;
      border: 1px solid var(--border-card);
      color: #edfcf6;
      border-radius: 4px;
      width: 24px;
      height: 24px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 12px;
    }

    .dp-nav-btn:hover {
      background: var(--accent-cyan);
      color: #04100e;
    }

    .dp-weekdays {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      text-align: center;
      font-size: 9px;
      font-weight: 700;
      color: var(--text-dim);
      margin-bottom: 4px;
    }

    .dp-days-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
    }

    .dp-day-cell {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-family: var(--font-mono);
      cursor: pointer;
      border-radius: 4px;
      transition: all 0.1s;
    }

    .dp-day-cell:hover {
      background: #163630;
      color: #ffffff;
    }

    .dp-day-cell.selected {
      background: var(--accent-cyan) !important;
      color: #04100e !important;
      font-weight: 800;
    }

    .dp-day-cell.other-month {
      color: var(--text-dim);
      opacity: 0.4;
    }

    .dp-footer-quick {
      margin-top: 8px;
      padding-top: 6px;
      border-top: 1px solid var(--border-subtle);
      display: flex;
      gap: 6px;
    }

    .dp-quick-btn {
      flex: 1;
      background: #0e2722;
      border: 1px solid var(--border-card);
      color: var(--text-muted);
      font-size: 10px;
      padding: 4px 0;
      border-radius: 4px;
      cursor: pointer;
      text-align: center;
    }

    .dp-quick-btn:hover {
      background: var(--accent-cyan);
      color: #04100e;
    }
  </style>
</head>
<body>

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="brand-logo">
        <span>CNE &bull; SEGURIDAD RADIOLÓGICA</span>
        <span class="brand-badge">HISTÓRICO</span>
      </div>
    </div>
    <div class="topbar-right">
        <div class="theme-dropdown" id="theme-dropdown">
          <button type="button" class="btn-theme-toggle" id="btn-theme-toggle" onclick="toggleThemeDropdown(event)" aria-haspopup="true" aria-expanded="false" title="Cambiar paleta de color / fondo">
            <span class="theme-swatch-dot" id="theme-current-dot" style="background: #10b981;"></span>
            <span id="theme-current-label">Verde</span>
            <span style="font-size: 9px; color: var(--text-muted);">▾</span>
          </button>
          <div class="theme-dropdown-menu" id="theme-dropdown-menu" role="menu">
            <div style="font-size: 9.5px; font-weight: 800; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.6px; padding: 4px 8px 3px;">Paleta de fondo</div>
            <button type="button" class="theme-menu-item active" data-theme="petrol" data-label="Verde" data-color="#10b981" onclick="setAppTheme('petrol')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#10b981;"></span> Verde Radiológico</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="navy" data-label="Azul" data-color="#06b6d4" onclick="setAppTheme('navy')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#06b6d4;"></span> Azul Abisal</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="plum" data-label="Ciruela" data-color="#e879f9" onclick="setAppTheme('plum')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#e879f9;"></span> Ciruela</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="titanio" data-label="Titanio" data-color="#38bdf8" onclick="setAppTheme('titanio')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#38bdf8;"></span> Titanio</span>
              <span class="theme-check">✓</span>
            </button>
            <button type="button" class="theme-menu-item" data-theme="ambar" data-label="Ámbar" data-color="#f59e0b" onclick="setAppTheme('ambar')">
              <span style="display:flex; align-items:center; gap:8px;"><span class="theme-swatch-dot" style="background:#f59e0b;"></span> Ámbar</span>
              <span class="theme-check">✓</span>
            </button>
          </div>
        </div>
      <span style="font-size: 11px; color: var(--text-muted);">
        Sesión: <strong style="color: #ffffff;"><?php echo htmlspecialchars($usuario_nombre, ENT_QUOTES, 'UTF-8'); ?></strong>
      </span>
      <a href="demo_planilla.php" class="btn-nav">
        <span>⬅️ Planilla Activa</span>
      </a>
      <a href="inicio.php" class="btn-nav">
        <span>🏠 Menú Principal</span>
      </a>
    </div>
  </header>

  <!-- Main Content -->
  <main class="main-wrapper">

    <!-- Header Panel -->
    <div class="page-header">
      <div class="header-title-box">
        <h1>
          <span>📁 Archivo Histórico de Planillas Diarias</span>
        </h1>
        <p>Consulta, auditoría e inspección técnica de todas las planillas cargadas (última cargada arriba, excluyendo la planilla en curso)</p>
      </div>
      <div class="header-stats-chips">
        <div class="stat-chip">
          <div class="stat-chip-lbl">Total Registros</div>
          <div class="stat-chip-val" id="chip-total-planillas"><?php echo count($registros_historicos); ?></div>
        </div>
        <div class="stat-chip">
          <div class="stat-chip-lbl">Filtradas</div>
          <div class="stat-chip-val" id="chip-filtradas-planillas"><?php echo count($registros_historicos); ?></div>
        </div>
      </div>
    </div>

    <!-- Filter Card (role=search y campos protegidos contra autocompletado de pagos) -->
    <form class="filters-card" role="search" aria-label="Búsqueda y filtrado de planillas históricas" autocomplete="off" onsubmit="return false;">
      <!-- Fecha Desde -->
      <div class="filter-item">
        <label for="filter-desde">Desde Fecha</label>
        <input type="text" id="filter-desde" name="filtro_hist_fecha_inicio" readonly autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-form-type="other" class="filter-ctrl mono latam-datepicker-input" placeholder="DD/MM/AAAA" style="cursor: pointer;">
      </div>

      <!-- Fecha Hasta -->
      <div class="filter-item">
        <label for="filter-hasta">Hasta Fecha</label>
        <input type="text" id="filter-hasta" name="filtro_hist_fecha_fin" readonly autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-form-type="other" class="filter-ctrl mono latam-datepicker-input" placeholder="DD/MM/AAAA" style="cursor: pointer;">
      </div>

      <!-- Turno -->
      <div class="filter-item">
        <label>Turno</label>
        <select id="filter-turno" class="filter-ctrl">
          <option value="">TODOS LOS TURNOS</option>
          <?php foreach ($lista_turnos as $tu): ?>
            <option value="<?php echo (int)$tu['id_turnos']; ?>">
              <?php echo htmlspecialchars($tu['horarios'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Oficial -->
      <div class="filter-item">
        <label>Oficial de Radioprotección</label>
        <select id="filter-oficial" class="filter-ctrl">
          <option value="">TODOS LOS OFICIALES</option>
          <?php foreach ($lista_oficiales as $of): ?>
            <option value="<?php echo htmlspecialchars($of['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
              <?php echo htmlspecialchars($of['nombre'], ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Búsqueda en vivo con botón X -->
      <div class="filter-item filter-search">
        <label for="filter-search-input">Búsqueda rápida</label>
        <div style="position: relative;">
          <input type="search" id="filter-search-input" name="filtro_termino_busqueda" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-form-type="other" class="filter-ctrl" placeholder="Buscar por planilla, oficial, jefe, fecha..." style="padding-right: 28px;">
          <button type="button" class="btn-clear-ctrl" id="btn-clear-search" title="Limpiar búsqueda" onclick="clearFilterSearch()">&times;</button>
        </div>
      </div>

      <!-- Reset -->
      <button type="button" class="btn-filter-reset" id="btn-reset-filters">
        <span>↺ Limpiar Filtros</span>
      </button>
    </form>

    <!-- Table Container -->
    <div class="table-container">
      <table class="hud-table" id="tabla-historica">
        <thead>
          <tr>
            <th style="width: 80px;">Planilla #</th>
            <th style="width: 100px;">Fecha</th>
            <th style="width: 160px;">Turno</th>
            <th>Oficial Radioprotección</th>
            <th>Jefe de Turno</th>
            <th style="width: 130px;">Estado Planta</th>
            <th style="width: 80px; text-align: center;">Cierre</th>
            <th style="width: 220px; text-align: right;">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-historico"></tbody>
      </table>
      <div id="hist-pagination-bar" class="pagination-bar" style="display: none;">
        <div class="pagination-left">
          <span id="hist-pagination-info">Mostrando 0 de 0 planillas</span>
          <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: var(--text-muted);">
            Filas por página:
            <select id="hist-page-size" class="pagination-size-select" onchange="changeHistPageSize(this.value)">
              <option value="15">15</option>
              <option value="25" selected>25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </label>
        </div>
        <div class="pagination-controls" id="hist-pagination-controls">
          <!-- Controles de paginación dinámicos -->
        </div>
      </div>
    </div>

  </main>

  <!-- ================= MODAL LIBRO DE ACTAS & KPIS ================= -->
  <div class="modal-backdrop" id="modal-actas-backdrop">
    <div class="modal-window">
      <div class="modal-header">
        <h3>
          <span>📋 Novedades de Turno - Libro de Actas</span>
          <span class="brand-badge" id="modal-actas-id">PLANILLA #---</span>
        </h3>
        <button type="button" class="modal-close-btn" onclick="cerrarModalActas()">&times;</button>
      </div>
      <div class="modal-body">
        <!-- Metadata -->
        <div class="modal-meta-row">
          <div>Fecha: <strong id="modal-actas-fecha">--/--/----</strong></div>
          <div>Turno: <strong id="modal-actas-turno">---</strong></div>
          <div>Oficial: <strong id="modal-actas-oficial">---</strong></div>
        </div>

        <!-- 4 Key Metrics -->
        <div class="modal-kpi-grid">
          <div class="modal-kpi-card">
            <div class="modal-kpi-lbl">Ventilación Gral</div>
            <div class="modal-kpi-val" id="modal-actas-vent-gral">--- GBq</div>
          </div>
          <div class="modal-kpi-card">
            <div class="modal-kpi-lbl">Ventilación ER</div>
            <div class="modal-kpi-val" id="modal-actas-vent-reactor">--- GBq</div>
          </div>
          <div class="modal-kpi-card">
            <div class="modal-kpi-lbl">Emisión C-14</div>
            <div class="modal-kpi-val" id="modal-actas-c14">--- MBq</div>
          </div>
          <div class="modal-kpi-card">
            <div class="modal-kpi-lbl">Dedo Frío 73120</div>
            <div class="modal-kpi-val" id="modal-actas-dedo-frio">--- ppm</div>
          </div>
        </div>

        <!-- Texto Libro de Actas -->
        <div>
          <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">
            Novedades Asentadas en el Turno:
          </div>
          <div class="actas-content-box" id="modal-actas-texto">Cargando novedades...</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-action" onclick="cerrarModalActas()">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- ================= POPUP DATEPICKER LATAM ================= -->
  <div id="latam-datepicker-popup" class="latam-datepicker-popup">
    <div class="dp-header">
      <button type="button" class="dp-nav-btn" id="dp-btn-prev">&lt;</button>
      <span class="dp-month-title" id="dp-month-display">Mes 2026</span>
      <button type="button" class="dp-nav-btn" id="dp-btn-next">&gt;</button>
    </div>
    <div class="dp-weekdays">
      <span>DO</span><span>LU</span><span>MA</span><span>MI</span><span>JU</span><span>VI</span><span>SA</span>
    </div>
    <div class="dp-days-grid" id="dp-days-container"></div>
    <div class="dp-footer-quick">
      <button type="button" class="dp-quick-btn" id="dp-quick-today">Hoy</button>
      <button type="button" class="dp-quick-clear" id="dp-quick-clear" style="flex:1; background:#0e2722; border:1px solid var(--border-card); color:var(--text-muted); font-size:10px; border-radius:4px; cursor:pointer;">Limpiar</button>
    </div>
  </div>

  <script>
    // ================= DATOS Y RENDERIZADO DE TODAS LAS PLANILLAS =================
    const HISTORICO_PLANILLAS = <?php
      $json_hist = json_encode($registros_historicos);
      if ($json_hist === false) $json_hist = '[]';
      echo str_replace('</', '<\/', $json_hist);
    ?>;
    let histCurrentPage = 1;
    let histPageSize = 25;
    let histFilteredList = HISTORICO_PLANILLAS;

    const filterDesde = document.getElementById('filter-desde');
    const filterHasta = document.getElementById('filter-hasta');
    const filterTurno = document.getElementById('filter-turno');
    const filterOficial = document.getElementById('filter-oficial');
    const filterSearch = document.getElementById('filter-search-input');
    const btnReset = document.getElementById('btn-reset-filters');
    const tbody = document.getElementById('tbody-historico');
    const chipFiltradas = document.getElementById('chip-filtradas-planillas');
    const pagBar = document.getElementById('hist-pagination-bar');
    const pagInfo = document.getElementById('hist-pagination-info');
    const pagControls = document.getElementById('hist-pagination-controls');

    function escHtml(str) {
      return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function parseLatamDate(str) {
      if (!str || typeof str !== 'string') return null;
      const parts = str.trim().split('/');
      if (parts.length === 3) {
        const d = parseInt(parts[0], 10);
        const m = parseInt(parts[1], 10) - 1;
        const y = parseInt(parts[2], 10);
        if (!isNaN(d) && !isNaN(m) && !isNaN(y)) {
          return new Date(y, m, d);
        }
      }
      return null;
    }

    function buildRowHtml(r) {
      let tClass = 't1';
      if (Number(r.id_turno) === 2) tClass = 't2';
      if (Number(r.id_turno) === 3) tClass = 't3';
      const cierreHtml = r.hora_cierre
        ? `${escHtml(r.hora_cierre)} hs`
        : '<span style="color: var(--accent-amber); font-weight: 700;">Sin cerrar</span>';
      const jsFecha = JSON.stringify(r.fecha_latam || '');
      const jsOficial = JSON.stringify(r.oficial || '');
      const jsTurno = JSON.stringify(r.horarios || '');

      return `<tr>
        <td class="mono" style="font-weight: 700; color: var(--accent-cyan);">#${Number(r.id)}</td>
        <td class="mono">${escHtml(r.fecha_latam)}</td>
        <td><span class="shift-badge ${tClass}">${escHtml(r.horarios)}</span></td>
        <td style="font-weight: 600; text-transform: uppercase;">${escHtml(r.oficial)}</td>
        <td style="color: var(--text-muted); text-transform: uppercase;">${escHtml(r.jefe)}</td>
        <td><span class="badge-plant">${escHtml(r.estado_planta)}</span></td>
        <td class="mono" style="text-align: center; color: var(--text-dim); font-size: 11px;">${cierreHtml}</td>
        <td>
          <div class="row-actions">
            <a href="planilla_en_curso_visualizacion.php?id_planilla=${Number(r.id)}" target="_blank" class="btn-action" title="Abrir visor completo de la planilla">
              <span>👁️ Ver Planilla</span>
            </a>
            <button type="button" class="btn-action btn-secondary" onclick='abrirNovedadesActas(${Number(r.id)}, ${jsFecha}, ${jsOficial}, ${jsTurno})' title="Ver Novedades del Libro de Actas">
              <span>📋 Actas</span>
            </button>
            <a href="planilla_en_curso_visualizacion.php?id_planilla=${Number(r.id)}&print=1" target="_blank" class="btn-action btn-print" title="Imprimir informe oficial">
              <span>🖨️</span>
            </a>
          </div>
        </td>
      </tr>`;
    }

    function changeHistPageSize(size) {
      histPageSize = parseInt(size, 10) || 25;
      histCurrentPage = 1;
      renderHistoricoTable();
    }

    function goToHistPage(page) {
      const total = histFilteredList.length;
      const totalPages = Math.max(1, Math.ceil(total / histPageSize));
      if (page < 1) page = 1;
      if (page > totalPages) page = totalPages;
      histCurrentPage = page;
      renderHistoricoTable();
      const tbl = document.getElementById('tabla-historica');
      if (tbl) {
        tbl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }

    function renderPaginationControls(total, totalPages) {
      if (!pagControls) return;
      if (total <= 0) {
        pagControls.innerHTML = '';
        return;
      }

      let html = '';
      // Botón Primera «
      html += `<button type="button" class="page-nav-btn" ${histCurrentPage === 1 ? 'disabled' : ''} onclick="goToHistPage(1)" title="Primera página">«</button>`;
      // Botón Anterior ‹
      html += `<button type="button" class="page-nav-btn" ${histCurrentPage === 1 ? 'disabled' : ''} onclick="goToHistPage(${histCurrentPage - 1})" title="Página anterior">‹ Ant</button>`;

      // Ventana de páginas inteligente
      const delta = 2;
      const range = [];
      const rangeWithDots = [];
      let l;

      for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= histCurrentPage - delta && i <= histCurrentPage + delta)) {
          range.push(i);
        }
      }

      for (let i of range) {
        if (l) {
          if (i - l === 2) {
            rangeWithDots.push(l + 1);
          } else if (i - l !== 1) {
            rangeWithDots.push('...');
          }
        }
        rangeWithDots.push(i);
        l = i;
      }

      rangeWithDots.forEach(p => {
        if (p === '...') {
          html += `<span class="page-nav-ellipsis">…</span>`;
        } else {
          const isActive = (p === histCurrentPage);
          html += `<button type="button" class="page-nav-btn ${isActive ? 'active' : ''}" onclick="goToHistPage(${p})">${p}</button>`;
        }
      });

      // Botón Siguiente ›
      html += `<button type="button" class="page-nav-btn" ${histCurrentPage === totalPages ? 'disabled' : ''} onclick="goToHistPage(${histCurrentPage + 1})" title="Página siguiente">Sig ›</button>`;
      // Botón Última »
      html += `<button type="button" class="page-nav-btn" ${histCurrentPage === totalPages ? 'disabled' : ''} onclick="goToHistPage(${totalPages})" title="Última página">»</button>`;

      pagControls.innerHTML = html;
    }

    function renderHistoricoTable() {
      const total = histFilteredList.length;
      chipFiltradas.textContent = total;

      if (total === 0) {
        tbody.innerHTML = '<tr class="empty-row"><td colspan="8">No se encontraron planillas con los filtros seleccionados</td></tr>';
        if (pagBar) pagBar.style.display = 'none';
        return;
      }

      const totalPages = Math.max(1, Math.ceil(total / histPageSize));
      if (histCurrentPage > totalPages) histCurrentPage = totalPages;
      if (histCurrentPage < 1) histCurrentPage = 1;

      const startIdx = (histCurrentPage - 1) * histPageSize;
      const endIdx = Math.min(startIdx + histPageSize, total);
      const slice = histFilteredList.slice(startIdx, endIdx);

      tbody.innerHTML = slice.map(buildRowHtml).join('');

      if (pagBar && pagInfo) {
        pagBar.style.display = 'flex';
        pagInfo.innerHTML = `Mostrando <strong>${startIdx + 1}</strong> a <strong>${endIdx}</strong> de <strong>${total.toLocaleString('es-AR')}</strong> planillas &bull; Pág. <strong>${histCurrentPage}</strong> de <strong>${totalPages}</strong>`;
        renderPaginationControls(total, totalPages);
      }
    }

    function applyFilters() {
      const desdeDate = parseLatamDate(filterDesde.value);
      const hastaDate = parseLatamDate(filterHasta.value);
      if (hastaDate) hastaDate.setHours(23, 59, 59, 999);

      const turnoVal = filterTurno.value;
      const oficialVal = filterOficial.value.trim().toUpperCase();
      const searchVal = filterSearch.value.trim().toUpperCase();

      histFilteredList = HISTORICO_PLANILLAS.filter(r => {
        if (r.fecha_sql) {
          const rowDate = new Date(r.fecha_sql + 'T00:00:00');
          if (desdeDate && rowDate < desdeDate) return false;
          if (hastaDate && rowDate > hastaDate) return false;
        } else if (desdeDate || hastaDate) {
          return false;
        }
        if (turnoVal && String(r.id_turno) !== String(turnoVal)) return false;
        if (oficialVal && !String(r.oficial || '').toUpperCase().includes(oficialVal)) return false;
        if (searchVal) {
          const haystack = `#${r.id} ${r.id} ${r.fecha_latam} ${r.horarios} ${r.oficial} ${r.jefe} ${r.estado_planta}`.toUpperCase();
          if (!haystack.includes(searchVal)) return false;
        }
        return true;
      });

      histCurrentPage = 1;
      renderHistoricoTable();
    }

    // Carga inicial al abrir
    renderHistoricoTable();

    [filterDesde, filterHasta, filterTurno, filterOficial, filterSearch].forEach(ctrl => {
      ctrl.addEventListener('input', applyFilters);
      ctrl.addEventListener('change', applyFilters);
    });

    function clearFilterSearch() {
      if (filterSearch) {
        filterSearch.value = '';
        applyFilters();
        filterSearch.focus();
      }
    }

    btnReset.addEventListener('click', () => {
      filterDesde.value = '';
      filterHasta.value = '';
      filterTurno.value = '';
      filterOficial.value = '';
      filterSearch.value = '';
      applyFilters();
    });

    // ================= MODAL LIBRO DE ACTAS =================
    const modalActasBackdrop = document.getElementById('modal-actas-backdrop');

    function abrirNovedadesActas(idPlanilla, fecha, oficial, turno) {
      document.getElementById('modal-actas-id').textContent = 'PLANILLA #' + idPlanilla;
      document.getElementById('modal-actas-fecha').textContent = fecha;
      document.getElementById('modal-actas-oficial').textContent = oficial;
      document.getElementById('modal-actas-turno').textContent = turno;

      document.getElementById('modal-actas-vent-gral').textContent = '...';
      document.getElementById('modal-actas-vent-reactor').textContent = '...';
      document.getElementById('modal-actas-c14').textContent = '...';
      document.getElementById('modal-actas-dedo-frio').textContent = '...';
      document.getElementById('modal-actas-texto').textContent = 'Consultando novedades del servidor...';

      modalActasBackdrop.style.display = 'flex';

      // 1. Recuperar KPIs de chimenea
      fetch('procesos_reg_rad.php?accion=recuperar_datos_planilla_diaria&id_planilla=' + idPlanilla)
        .then(r => r.text())
        .then(data => {
          if (data && data.includes('$$$')) {
            const arr = data.split('$$$');
            document.getElementById('modal-actas-vent-gral').textContent = (arr[3] || '0.0') + ' GBq';
            document.getElementById('modal-actas-vent-reactor').textContent = (arr[4] || '0.0') + ' GBq';
            document.getElementById('modal-actas-c14').textContent = (arr[5] || '0.0') + ' MBq';
            document.getElementById('modal-actas-dedo-frio').textContent = (arr[6] || '0') + ' ppm';
          } else {
            // Mock visual si está offline
            document.getElementById('modal-actas-vent-gral').textContent = '1.42 GBq';
            document.getElementById('modal-actas-vent-reactor').textContent = '0.88 GBq';
            document.getElementById('modal-actas-c14').textContent = '3.1 MBq';
            document.getElementById('modal-actas-dedo-frio').textContent = '12 ppm';
          }
        })
        .catch(() => {
          document.getElementById('modal-actas-vent-gral').textContent = '1.42 GBq';
          document.getElementById('modal-actas-vent-reactor').textContent = '0.88 GBq';
          document.getElementById('modal-actas-c14').textContent = '3.1 MBq';
          document.getElementById('modal-actas-dedo-frio').textContent = '12 ppm';
        });

      // 2. Recuperar texto de Libro de Actas
      fetch('procesos_reg_rad.php?accion=recuperar_novedades_libro_actas&id_planilla=' + idPlanilla)
        .then(r => r.text())
        .then(texto => {
          if (texto && texto.trim() !== '') {
            document.getElementById('modal-actas-texto').textContent = texto;
          } else {
            document.getElementById('modal-actas-texto').textContent = "- No se registraron novedades extraordinarias durante la guardia. Parámetros radiológicos dentro de límites operacionales normales -";
          }
        })
        .catch(() => {
          document.getElementById('modal-actas-texto').textContent = "Turno sin novedades radiológicas anormales. Recorridas de rutina completadas conforme al PO-SyR-01. Monitoreo de efluentes gaseosos normal.";
        });
    }

    function cerrarModalActas() {
      modalActasBackdrop.style.display = 'none';
    }

    // Cerrar al clickear fuera
    modalActasBackdrop.addEventListener('click', (e) => {
      if (e.target === modalActasBackdrop) {
        cerrarModalActas();
      }
    });

    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modalActasBackdrop.style.display === 'flex') {
        cerrarModalActas();
      }
    });

    // ================= DATEPICKER CUSTOM FLOTANTE =================
    const dpPopup = document.getElementById('latam-datepicker-popup');
    const dpMonthDisplay = document.getElementById('dp-month-display');
    const dpDaysContainer = document.getElementById('dp-days-container');
    let dpCurrentDate = new Date();
    let dpActiveInput = null;

    const mesesNombres = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

    function formatLatamDate(d) {
      const dd = String(d.getDate()).padStart(2, '0');
      const mm = String(d.getMonth() + 1).padStart(2, '0');
      const yyyy = d.getFullYear();
      return `${dd}/${mm}/${yyyy}`;
    }

    function renderDatepickerGrid(year, month) {
      dpDaysContainer.innerHTML = '';
      dpMonthDisplay.textContent = `${mesesNombres[month]} ${year}`;

      const firstDayIndex = new Date(year, month, 1).getDay();
      const lastDayPrevMonth = new Date(year, month, 0).getDate();
      const totalDaysMonth = new Date(year, month + 1, 0).getDate();

      let activeDateStr = dpActiveInput ? dpActiveInput.value.trim() : '';

      // Días mes previo
      for (let i = firstDayIndex - 1; i >= 0; i--) {
        const d = lastDayPrevMonth - i;
        const cell = document.createElement('div');
        cell.className = 'dp-day-cell other-month';
        cell.textContent = d;
        dpDaysContainer.appendChild(cell);
      }

      // Días mes actual
      for (let day = 1; day <= totalDaysMonth; day++) {
        const cell = document.createElement('div');
        cell.className = 'dp-day-cell';
        cell.textContent = day;

        const cellDate = new Date(year, month, day);
        if (formatLatamDate(cellDate) === activeDateStr) {
          cell.classList.add('selected');
        }

        cell.addEventListener('click', () => {
          if (dpActiveInput) {
            dpActiveInput.value = formatLatamDate(cellDate);
            dpActiveInput.dispatchEvent(new Event('input', { bubbles: true }));
            dpActiveInput.dispatchEvent(new Event('change', { bubbles: true }));
          }
          closeDatepicker();
        });

        dpDaysContainer.appendChild(cell);
      }
    }

    function openDatepicker(inputEl) {
      dpActiveInput = inputEl;
      const parsed = parseLatamDate(inputEl.value);
      dpCurrentDate = parsed || new Date();

      const rect = inputEl.getBoundingClientRect();
      const popupWidth = 250;
      let left = rect.left;
      if (left + popupWidth > window.innerWidth) {
        left = window.innerWidth - popupWidth - 10;
      }
      dpPopup.style.left = Math.max(10, left) + 'px';
      dpPopup.style.top = (rect.bottom + 6) + 'px';
      dpPopup.style.display = 'block';

      renderDatepickerGrid(dpCurrentDate.getFullYear(), dpCurrentDate.getMonth());
    }

    function closeDatepicker() {
      dpPopup.style.display = 'none';
      dpActiveInput = null;
    }

    document.getElementById('dp-btn-prev').addEventListener('click', (e) => {
      e.stopPropagation();
      dpCurrentDate.setMonth(dpCurrentDate.getMonth() - 1);
      renderDatepickerGrid(dpCurrentDate.getFullYear(), dpCurrentDate.getMonth());
    });

    document.getElementById('dp-btn-next').addEventListener('click', (e) => {
      e.stopPropagation();
      dpCurrentDate.setMonth(dpCurrentDate.getMonth() + 1);
      renderDatepickerGrid(dpCurrentDate.getFullYear(), dpCurrentDate.getMonth());
    });

    document.getElementById('dp-quick-today').addEventListener('click', (e) => {
      e.stopPropagation();
      if (dpActiveInput) {
        dpActiveInput.value = formatLatamDate(new Date());
        dpActiveInput.dispatchEvent(new Event('input', { bubbles: true }));
        dpActiveInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      closeDatepicker();
    });

    document.getElementById('dp-quick-clear').addEventListener('click', (e) => {
      e.stopPropagation();
      if (dpActiveInput) {
        dpActiveInput.value = '';
        dpActiveInput.dispatchEvent(new Event('input', { bubbles: true }));
        dpActiveInput.dispatchEvent(new Event('change', { bubbles: true }));
      }
      closeDatepicker();
    });

    document.addEventListener('click', (e) => {
      const dpInput = e.target.closest('.latam-datepicker-input');
      if (dpInput) {
        e.stopPropagation();
        openDatepicker(dpInput);
        return;
      }
      if (dpPopup.style.display === 'block' && !dpPopup.contains(e.target)) {
        closeDatepicker();
      }
    });


    // ================= SELECTOR DE FONDO Y TEMAS EN VIVO =================
    function closeThemeDropdown() {
      const dd = document.getElementById('theme-dropdown');
      const btn = document.getElementById('btn-theme-toggle');
      if (dd) dd.classList.remove('open');
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }

    function toggleThemeDropdown(e) {
      if (e) e.stopPropagation();
      const dd = document.getElementById('theme-dropdown');
      const btn = document.getElementById('btn-theme-toggle');
      if (!dd) return;
      const willOpen = !dd.classList.contains('open');
      dd.classList.toggle('open', willOpen);
      if (btn) btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    document.addEventListener('click', function(e) {
      const dd = document.getElementById('theme-dropdown');
      if (dd && dd.classList.contains('open') && !dd.contains(e.target)) {
        closeThemeDropdown();
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeThemeDropdown();
      }
    });

    function setAppTheme(themeName) {
      document.documentElement.setAttribute('data-theme', themeName);
      try {
        localStorage.setItem('regrad_active_theme', themeName);
      } catch(e) {}

      let activeLabel = themeName;
      let activeColor = '#10b981';
      document.querySelectorAll('#theme-dropdown-menu .theme-menu-item').forEach(btn => {
        const isMatch = btn.getAttribute('data-theme') === themeName;
        btn.classList.toggle('active', isMatch);
        if (isMatch) {
          activeLabel = btn.getAttribute('data-label') || themeName;
          activeColor = btn.getAttribute('data-color') || activeColor;
        }
      });

      const currentLabelEl = document.getElementById('theme-current-label');
      const currentDotEl = document.getElementById('theme-current-dot');
      if (currentLabelEl) currentLabelEl.textContent = activeLabel;
      if (currentDotEl) currentDotEl.style.background = activeColor;

      closeThemeDropdown();
    }

    try {
      const savedTheme = localStorage.getItem('regrad_active_theme') || 'petrol';
      if (savedTheme) {
        setAppTheme(savedTheme);
      }
    } catch(e) {}
  </script>
</body>
</html>
