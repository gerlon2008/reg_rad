<?php
/**
 * PORTAL DE INICIO — REGISTRO RADIOLÓGICO ONLINE (SyR)
 * Central Nuclear Embalse (NA-SA)
 * Estilo HMI sincronizado con demo_planilla.php y vista_planillas_anteriores.php
 * Compatible con PHP 5.5.7, Apache 2.4 y MySQL (mysql_*), 100% Offline.
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$db_connected = false;
$miconex = null;

if (isset($_SERVER["DOCUMENT_ROOT"]) && file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php");
    }
    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php")) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/niveles.php");
    }

    if (!(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] == true)) {
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Restringido - SyR</title>';
        echo '<style>body{background:#061412;color:#edfcf6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head>';
        echo '<body><div style="text-align:center;padding:28px 32px;border:1px solid #1f4d43;border-left:4px solid #ef4444;border-radius:10px;background:#0e2722;max-width:440px;">';
        echo '<h2 style="margin:0 0 10px;font-size:18px;color:#f87171;">⛔ Sesión No Iniciada</h2>';
        echo '<p style="color:#8ec7b9;font-size:13px;margin:0 0 18px;line-height:1.5;">Esta página es solo para usuarios registrados del Portal SyR.</p>';
        echo '<a href="/ptr/" style="display:inline-block;padding:9px 16px;border-radius:7px;background:#10b981;color:#04120f;font-weight:800;font-size:12px;text-decoration:none;">Volver al Ingreso</a>';
        echo '</div></body></html>';
        exit;
    }

    if (isset($miconex) && $miconex) {
        @mysql_set_charset('utf8', $miconex);
        if (isset($database_miconex)) {
            @mysql_select_db($database_miconex, $miconex);
        }
        $db_connected = true;
    }
}

$tipo_user = isset($_SESSION['portal_nivel']) ? $_SESSION['portal_nivel'] : 1;
$id_user = isset($_SESSION['id_user']) ? (string)$_SESSION['id_user'] : '';
$of_rad_registro = $id_user;
$rol_regrad = isset($_SESSION['of_rad_registro_rad']) ? (string)$_SESSION['of_rad_registro_rad'] : '0';

// Estricto: el bloque administrativo y de auditoría solo se muestra al Usuario ID 0
$es_u0 = ($id_user === '0');

$nombre_usuario_raw = !empty($_SESSION['nombre_apellido']) ? trim((string)$_SESSION['nombre_apellido']) : 'USUARIO CONECTADO';
$nombre_usuario = function_exists('mb_convert_case')
    ? mb_convert_case($nombre_usuario_raw, MB_CASE_TITLE, 'UTF-8')
    : ucwords(strtolower($nombre_usuario_raw));

// Iniciales para avatar
$partes_nom = preg_split('/\s+/', trim($nombre_usuario_raw));
$avatar_iniciales = '';
if (!empty($partes_nom[0])) {
    $avatar_iniciales .= function_exists('mb_substr') ? mb_substr($partes_nom[0], 0, 1, 'UTF-8') : substr($partes_nom[0], 0, 1);
}
if (!empty($partes_nom[1])) {
    $avatar_iniciales .= function_exists('mb_substr') ? mb_substr($partes_nom[1], 0, 1, 'UTF-8') : substr($partes_nom[1], 0, 1);
}
if ($avatar_iniciales === '') {
    $avatar_iniciales = 'RP';
}
$avatar_iniciales = function_exists('mb_strtoupper') ? mb_strtoupper($avatar_iniciales, 'UTF-8') : strtoupper($avatar_iniciales);

// Etiqueta de rol según sesión
if ($rol_regrad === '1') {
    $rol_etiqueta = 'Oficial de Radioprotección';
} elseif ($rol_regrad === '2') {
    $rol_etiqueta = 'Consulta Ampliada';
} else {
    $rol_etiqueta = 'Usuario Normal';
}
if ($es_u0) {
    $rol_etiqueta .= ' • U0';
}

// Helpers UTF-8 y fecha seguros para PHP 5.5.7
if (!function_exists('inicio_safe_utf8')) {
    function inicio_safe_utf8($val) {
        if (!is_string($val)) return (string)$val;
        if (function_exists('mb_check_encoding') && !mb_check_encoding($val, 'UTF-8')) {
            return utf8_encode($val);
        }
        return $val;
    }
}
if (!function_exists('inicio_fecha_latam')) {
    function inicio_fecha_latam($f) {
        if (empty($f)) return '--/--/----';
        $ts = strtotime($f);
        return $ts ? date('d/m/Y', $ts) : $f;
    }
}
if (!function_exists('inicio_h')) {
    function inicio_h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Consultas operativas en vivo (planilla abierta, última cerrada, conteo del mes)
$planilla_abierta = null;
$ultima_cerrada = null;
$total_planillas_mes = 0;
$meses_es = array(
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
);
$mes_actual_label = $meses_es[(int)date('n')] . ' ' . date('Y');

// Datos de auditoría (SOLO se consultan y muestran si $es_u0)
$auditoria_ok = false;
$auditoria_eventos_hoy = 0;

if ($db_connected) {
    // 1. Planilla actualmente abierta
    $q_open = "SELECT a.id_planilla_diaria, a.fecha, a.turno, a.estado_planta,
                      b.horarios, c.nombre_estado, d.of_apellido, d.of_nombre,
                      e.jefe_apellido, e.jefe_nombre
               FROM regrad_planilla_diaria_nueva a
               LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno
               LEFT JOIN regrad_estados_planta c ON c.id_estado_planta = a.estado_planta
               LEFT JOIN regrad_of_rad d ON d.id_of_rad = a.of_rad
               LEFT JOIN regrad_jefes_turno e ON e.id_jefes_turno = a.jefe_turno
               WHERE a.fecha_hora_cerrado IS NULL
               ORDER BY a.id_planilla_diaria DESC
               LIMIT 1";
    $r_open = @mysql_query($q_open, $miconex);
    if ($r_open && mysql_num_rows($r_open) > 0) {
        $rw = mysql_fetch_assoc($r_open);
        $of_ap = inicio_safe_utf8($rw['of_apellido']);
        $of_no = inicio_safe_utf8($rw['of_nombre']);
        $jf_ap = inicio_safe_utf8($rw['jefe_apellido']);
        $jf_no = inicio_safe_utf8($rw['jefe_nombre']);
        $planilla_abierta = array(
            'id' => (int)$rw['id_planilla_diaria'],
            'fecha' => inicio_fecha_latam($rw['fecha']),
            'horarios' => !empty($rw['horarios']) ? inicio_safe_utf8($rw['horarios']) : ('Turno ' . $rw['turno']),
            'estado' => !empty($rw['nombre_estado']) ? inicio_safe_utf8($rw['nombre_estado']) : 'EN OPERACIÓN',
            'oficial' => (!empty($of_ap) || !empty($of_no)) ? trim($of_ap . ', ' . $of_no, ', ') : 'SIN ASIGNAR',
            'jefe' => (!empty($jf_ap) || !empty($jf_no)) ? trim($jf_ap . ', ' . $jf_no, ', ') : ''
        );
    }

    // 2. Última planilla cerrada
    $q_last = "SELECT a.id_planilla_diaria, a.fecha, a.turno, a.fecha_hora_cerrado, b.horarios
               FROM regrad_planilla_diaria_nueva a
               LEFT JOIN regrad_turnos b ON b.id_turnos = a.turno
               WHERE a.fecha_hora_cerrado IS NOT NULL
               ORDER BY a.id_planilla_diaria DESC
               LIMIT 1";
    $r_last = @mysql_query($q_last, $miconex);
    if ($r_last && mysql_num_rows($r_last) > 0) {
        $rw_l = mysql_fetch_assoc($r_last);
        $ultima_cerrada = array(
            'id' => (int)$rw_l['id_planilla_diaria'],
            'fecha' => inicio_fecha_latam($rw_l['fecha']),
            'horarios' => !empty($rw_l['horarios']) ? inicio_safe_utf8($rw_l['horarios']) : ('Turno ' . $rw_l['turno'])
        );
    }

    // 3. Total de planillas del mes en curso
    $ini_mes = date('Y-m-01');
    $fin_mes = date('Y-m-t');
    $r_mes = @mysql_query("SELECT COUNT(*) AS cnt FROM regrad_planilla_diaria_nueva WHERE fecha >= '$ini_mes' AND fecha <= '$fin_mes'", $miconex);
    if ($r_mes && ($rw_m = mysql_fetch_assoc($r_mes))) {
        $total_planillas_mes = (int)$rw_m['cnt'];
    }

    // 4. Estado de Auditoría (estrictamente solo para Usuario 0)
    if ($es_u0 && file_exists(__DIR__ . '/auditoria_reg_rad.php')) {
        require_once(__DIR__ . '/auditoria_reg_rad.php');
        $salud = auditoria_verificar_salud($miconex);
        $auditoria_ok = !empty($salud['ok']);
        if ($auditoria_ok) {
            $hoy_sql = date('Y-m-d');
            $r_ev = @mysql_query("SELECT COUNT(*) AS cnt FROM `regrad_auditoria_log` WHERE fecha_hora >= '$hoy_sql 00:00:00'", $miconex);
            if ($r_ev && ($rw_e = mysql_fetch_assoc($r_ev))) {
                $auditoria_eventos_hoy = (int)$rw_e['cnt'];
            }
        }
    }
}

$puede_cargar = ($rol_regrad === '1');
$puede_ver_curso = ($rol_regrad === '1' || $rol_regrad === '2');
$puede_ver_actas = ($rol_regrad === '1' || $rol_regrad === '2');
$cant_modulos_op = 1 + ($puede_cargar ? 1 : 0) + ($puede_ver_curso ? 1 : 0);

$url_planilla_curso = 'planilla_en_curso_visualizacion.php';
if ($planilla_abierta !== null) {
    $url_planilla_curso .= '?id_planilla=' . (int)$planilla_abierta['id'];
} elseif ($ultima_cerrada !== null) {
    $url_planilla_curso .= '?id_planilla=' . (int)$ultima_cerrada['id'];
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro Radiológico Online - SyR (Central Nuclear Embalse)</title>
  <script>
    (function() {
      try {
        var saved = localStorage.getItem('regrad_active_theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
      } catch (e) {}
    })();
  </script>
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
      --accent-cyan: #10b981;
      --accent-green: #34d399;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
      --accent-sky: #38bdf8;
      --accent-violet: #a78bfa;
      --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
      --font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    [data-theme="navy"] {
      --bg-page: #07111e; --bg-sidebar: #0b1a2e; --bg-card: #10223b;
      --bg-card-subtle: #172f50; --bg-input: #091626; --border-card: #20416b;
      --border-subtle: #142a46; --text-main: #f0f6fc; --text-muted: #93b0d4;
      --text-dim: #5b7b9e; --accent-blue: #3b82f6; --accent-cyan: #38bdf8;
      --accent-green: #22c55e; --accent-amber: #f59e0b; --accent-red: #ef4444;
    }
    [data-theme="plum"] {
      --bg-page: #110b17; --bg-sidebar: #181021; --bg-card: #21162d;
      --bg-card-subtle: #2d1f3d; --bg-input: #150e1e; --border-card: #433157;
      --border-subtle: #271c33; --text-main: #f5f3ff; --text-muted: #c4b5fd;
      --text-dim: #8572a8; --accent-blue: #8b5cf6; --accent-cyan: #38bdf8;
      --accent-green: #34d399; --accent-amber: #fbbf24; --accent-red: #ef4444;
    }
    [data-theme="titanio"] {
      --bg-page: #0f1319; --bg-sidebar: #151a23; --bg-card: #1b212c;
      --bg-card-subtle: #242c3a; --bg-input: #12161f; --border-card: #2e3849;
      --border-subtle: #202733; --text-main: #f1f5f9; --text-muted: #94a3b8;
      --text-dim: #64748b; --accent-blue: #4f86f7; --accent-cyan: #38bdf8;
      --accent-green: #22c55e; --accent-amber: #f59e0b; --accent-red: #ef4444;
    }
    [data-theme="ambar"] {
      --bg-page: #0a0b0d; --bg-sidebar: #111216; --bg-card: #16181f;
      --bg-card-subtle: #22252e; --bg-input: #0e1014; --border-card: #343744;
      --border-subtle: #20222a; --text-main: #f8fafc; --text-muted: #a1a1aa;
      --text-dim: #71717a; --accent-blue: #d97706; --accent-cyan: #f59e0b;
      --accent-green: #10b981; --accent-amber: #f59e0b; --accent-red: #ef4444;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg-page);
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 13px;
      min-height: 100vh;
      line-height: 1.45;
    }

    .wrap {
      max-width: 1180px;
      margin: 0 auto;
      padding: 20px 20px 40px;
    }

    /* Topbar HMI */
    .topbar {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      padding: 14px 18px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      margin-bottom: 18px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 13px;
    }
    .brand-logo-wrap {
      height: 44px;
      min-width: 48px;
      padding: 4px 8px;
      border-radius: 9px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid var(--border-card);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .brand-logo-wrap img {
      max-height: 34px;
      max-width: 96px;
      display: block;
    }
    .brand-emblem-fallback {
      display: none;
      align-items: center;
      justify-content: center;
      font-weight: 900;
      font-size: 12px;
      letter-spacing: 0.6px;
      color: var(--accent-green);
      font-family: var(--font-mono);
    }
    .brand h1 {
      font-size: 16.5px;
      font-weight: 800;
      letter-spacing: 0.2px;
      color: var(--text-main);
    }
    .brand-sub {
      font-size: 11.5px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .topbar-right {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 10px;
    }

    /* Selector de Tema compacto */
    .theme-dropdown { position: relative; }
    .theme-trigger {
      padding: 7px 11px;
      border-radius: 7px;
      border: 1px solid var(--border-card);
      background: var(--bg-input);
      color: var(--text-main);
      font-size: 11.5px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      transition: border-color 0.15s;
    }
    .theme-trigger:hover { border-color: var(--accent-cyan); }
    .theme-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: var(--accent-cyan);
      display: inline-block;
    }
    .theme-menu {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 155px;
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 5px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.55);
      display: none;
      z-index: 200;
    }
    .theme-dropdown.open .theme-menu { display: block; }
    .theme-item {
      width: 100%;
      text-align: left;
      padding: 7px 10px;
      border: none;
      border-radius: 6px;
      background: transparent;
      color: var(--text-muted);
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .theme-item:hover, .theme-item.active {
      background: var(--bg-card-subtle);
      color: var(--text-main);
    }

    .user-chip {
      display: flex;
      align-items: center;
      gap: 9px;
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      padding: 6px 12px;
    }
    .user-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: rgba(16, 185, 129, 0.18);
      border: 1px solid rgba(16, 185, 129, 0.45);
      color: var(--accent-green);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 11px;
      font-family: var(--font-mono);
    }
    .user-name { font-weight: 800; font-size: 12px; color: var(--text-main); }
    .user-role { font-size: 10.5px; color: var(--accent-cyan); font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }

    .btn-ghost {
      padding: 8px 12px;
      border-radius: 7px;
      border: 1px solid var(--border-card);
      background: var(--bg-card-subtle);
      color: var(--text-main);
      font-weight: 700;
      font-size: 11.5px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      transition: all 0.15s;
    }
    .btn-ghost:hover { border-color: var(--accent-cyan); background: var(--bg-card); }

    /* Live Shift Hero Banner */
    .shift-hero {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-left: 4px solid var(--accent-green);
      border-radius: 10px;
      padding: 16px 20px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 16px;
    }
    .shift-hero.closed {
      border-left-color: var(--accent-amber);
      background: linear-gradient(90deg, rgba(245, 158, 11, 0.09), var(--bg-card) 55%);
    }
    .shift-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 10.5px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      padding: 3px 9px;
      border-radius: 999px;
      background: rgba(16, 185, 129, 0.14);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: var(--accent-green);
      margin-bottom: 6px;
    }
    .shift-hero.closed .shift-status-pill {
      background: rgba(245, 158, 11, 0.15);
      border-color: rgba(245, 158, 11, 0.45);
      color: var(--accent-amber);
    }
    .pulse-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: currentColor;
      box-shadow: 0 0 8px currentColor;
    }
    .shift-title {
      font-size: 15.5px;
      font-weight: 800;
      color: var(--text-main);
    }
    .shift-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 6px;
      font-size: 11.5px;
      color: var(--text-muted);
      font-family: var(--font-mono);
    }
    .shift-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }
    .btn-primary {
      padding: 10px 16px;
      border-radius: 8px;
      border: none;
      background: var(--accent-cyan);
      color: #04120f;
      font-weight: 900;
      font-size: 12px;
      letter-spacing: 0.3px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      text-decoration: none;
      transition: transform 0.12s, filter 0.12s;
    }
    .btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
    .btn-primary.amber { background: var(--accent-amber); color: #161105; }

    /* KPI Strip */
    .kpi-strip {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 10px;
      margin-bottom: 22px;
    }
    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: 8px;
      padding: 12px 15px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .kpi-label {
      font-size: 10.5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-dim);
      font-weight: 700;
    }
    .kpi-val {
      font-size: 15px;
      font-weight: 800;
      color: var(--text-main);
      font-family: var(--font-mono);
      margin-top: 2px;
    }
    .kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

    /* Section Headings */
    .sec-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 22px 0 11px;
      padding-bottom: 6px;
      border-bottom: 1px solid var(--border-subtle);
    }
    .sec-title {
      font-size: 11.5px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.8px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .sec-count {
      font-size: 10.5px;
      font-family: var(--font-mono);
      color: var(--text-dim);
    }

    /* Module Grid */
    .mod-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
      gap: 12px;
    }
    .mod-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 10px;
      padding: 15px 16px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 12px;
      text-decoration: none;
      color: inherit;
      transition: all 0.16s ease;
      position: relative;
      overflow: hidden;
    }
    .mod-card::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: var(--card-accent, var(--accent-cyan));
      opacity: 0.8;
    }
    .mod-card:hover {
      background: var(--bg-card-subtle);
      border-color: var(--card-accent, var(--accent-cyan));
      transform: translateY(-2px);
    }
    .mod-top {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .mod-ico {
      width: 38px;
      height: 38px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border-subtle);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }
    .mod-name {
      font-size: 13.5px;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 3px;
    }
    .mod-desc {
      font-size: 11.5px;
      color: var(--text-muted);
      line-height: 1.4;
    }
    .mod-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 10.5px;
      padding-top: 8px;
      border-top: 1px solid var(--border-subtle);
      color: var(--text-dim);
      font-family: var(--font-mono);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .badge-green  { background: rgba(16, 185, 129, 0.14); color: var(--accent-green); border: 1px solid rgba(16, 185, 129, 0.35); }
    .badge-sky    { background: rgba(56, 189, 248, 0.14); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.35); }
    .badge-amber  { background: rgba(245, 158, 11, 0.14); color: var(--accent-amber); border: 1px solid rgba(245, 158, 11, 0.35); }
    .badge-violet { background: rgba(167, 139, 250, 0.14); color: #c4b5fd; border: 1px solid rgba(167, 139, 250, 0.35); }

    .footer-note {
      margin-top: 28px;
      padding-top: 12px;
      border-top: 1px solid var(--border-subtle);
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 8px;
      font-size: 11px;
      color: var(--text-dim);
      font-family: var(--font-mono);
    }

    @media (max-width: 680px) {
      .wrap { padding: 12px 12px 28px; }
      .topbar { padding: 12px 14px; }
      .shift-hero { padding: 14px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <!-- TOPBAR HMI -->
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo-wrap">
          <img src="../images/logo_nasa.png" alt="NA-SA" onerror="this.style.display='none'; document.getElementById('logo-nasa-fallback').style.display='flex';">
          <div id="logo-nasa-fallback" class="brand-emblem-fallback">NA-SA</div>
        </div>
        <div>
          <h1>Registro Radiológico Online &mdash; SyR</h1>
          <div class="brand-sub">Central Nuclear Embalse &bull; Centro de Control y Accesos Operativos</div>
        </div>
      </div>

      <div class="topbar-right">
        <div class="theme-dropdown" id="theme-dropdown">
          <button type="button" class="theme-trigger" onclick="toggleThemeMenu(event)" title="Cambiar paleta de color">
            <span class="theme-dot" id="theme-dot-cur"></span>
            <span id="theme-label-cur">Verde</span>
            <span style="opacity:0.65;">▾</span>
          </button>
          <div class="theme-menu" id="theme-menu">
            <button type="button" class="theme-item" data-theme="petrol" data-label="Verde" data-color="#10b981" onclick="applyTheme('petrol')"><span class="theme-dot" style="background:#10b981;"></span> Verde Petróleo</button>
            <button type="button" class="theme-item" data-theme="navy" data-label="Azul" data-color="#38bdf8" onclick="applyTheme('navy')"><span class="theme-dot" style="background:#38bdf8;"></span> Azul Abisal</button>
            <button type="button" class="theme-item" data-theme="plum" data-label="Ciruela" data-color="#a78bfa" onclick="applyTheme('plum')"><span class="theme-dot" style="background:#a78bfa;"></span> Ciruela Obsidiana</button>
            <button type="button" class="theme-item" data-theme="titanio" data-label="Titanio" data-color="#38bdf8" onclick="applyTheme('titanio')"><span class="theme-dot" style="background:#64748b;"></span> Titanio Pizarra</button>
            <button type="button" class="theme-item" data-theme="ambar" data-label="Ámbar" data-color="#f59e0b" onclick="applyTheme('ambar')"><span class="theme-dot" style="background:#f59e0b;"></span> Ámbar SCADA</button>
          </div>
        </div>

        <div class="user-chip">
          <div class="user-avatar"><?php echo inicio_h($avatar_iniciales); ?></div>
          <div>
            <div class="user-name"><?php echo inicio_h($nombre_usuario); ?></div>
            <div class="user-role"><?php echo inicio_h($rol_etiqueta); ?></div>
          </div>
        </div>

        <button type="button" class="btn-ghost" onclick="window.history.back();">← Volver</button>
      </div>
    </header>

    <?php if ($db_connected) { ?>
    <!-- ESTADO DE GUARDIA EN VIVO -->
    <?php if ($planilla_abierta !== null) { ?>
    <section class="shift-hero">
      <div>
        <div class="shift-status-pill">
          <span class="pulse-dot"></span>
          <span>Guardia Activa en Curso</span>
        </div>
        <div class="shift-title">
          Planilla Diaria #<?php echo (int)$planilla_abierta['id']; ?> &mdash; <?php echo inicio_h($planilla_abierta['fecha']); ?> &bull; <?php echo inicio_h($planilla_abierta['horarios']); ?>
        </div>
        <div class="shift-meta">
          <span>🏭 Estado: <?php echo inicio_h($planilla_abierta['estado']); ?></span>
          <span>👤 Oficial: <?php echo inicio_h($planilla_abierta['oficial']); ?></span>
          <?php if ($planilla_abierta['jefe'] !== '') { ?>
          <span>📋 Jefe de Turno: <?php echo inicio_h($planilla_abierta['jefe']); ?></span>
          <?php } ?>
        </div>
      </div>
      <div class="shift-actions">
        <?php if ($puede_cargar) { ?>
        <a href="demo_planilla.php" class="btn-primary">⚡ CONTINUAR CARGA DIARIA</a>
        <?php } ?>
        <?php if ($puede_ver_curso) { ?>
        <a href="<?php echo inicio_h($url_planilla_curso); ?>" class="btn-ghost">👁️ Ver en Curso</a>
        <?php } ?>
      </div>
    </section>
    <?php } else { ?>
    <section class="shift-hero closed">
      <div>
        <div class="shift-status-pill">
          <span class="pulse-dot"></span>
          <span>Sin Planilla Abierta</span>
        </div>
        <div class="shift-title">No hay ninguna guardia radiológica activa en este momento</div>
        <div class="shift-meta">
          <?php if ($ultima_cerrada !== null) { ?>
          <span>🕒 Último cierre: <?php echo inicio_h($ultima_cerrada['fecha']); ?> (<?php echo inicio_h($ultima_cerrada['horarios']); ?> &bull; #<?php echo (int)$ultima_cerrada['id']; ?>)</span>
          <?php } ?>
          <?php if ($puede_cargar) { ?>
          <span>🔒 Al ingresar a Carga Diaria se solicitará iniciar el turno</span>
          <?php } ?>
        </div>
      </div>
      <div class="shift-actions">
        <?php if ($puede_cargar) { ?>
        <a href="demo_planilla.php" class="btn-primary amber">➕ INICIAR NUEVA GUARDIA</a>
        <?php } ?>
        <a href="vista_planillas_anteriores.php" class="btn-ghost">🗂️ Planillas Anteriores</a>
      </div>
    </section>
    <?php } ?>

    <!-- KPI STRIP -->
    <section class="kpi-strip">
      <div class="kpi-card">
        <div>
          <div class="kpi-label">Última Planilla Cerrada</div>
          <div class="kpi-val"><?php echo $ultima_cerrada !== null ? inicio_h($ultima_cerrada['fecha']) : '--/--/----'; ?></div>
          <div class="kpi-sub">
            <?php echo $ultima_cerrada !== null ? (inicio_h($ultima_cerrada['horarios']) . ' • #' . (int)$ultima_cerrada['id']) : 'Sin registros cerrados'; ?>
          </div>
        </div>
        <span style="font-size: 20px;">🗂️</span>
      </div>

      <div class="kpi-card">
        <div>
          <div class="kpi-label">Planillas del Mes</div>
          <div class="kpi-val"><?php echo (int)$total_planillas_mes; ?> guardias</div>
          <div class="kpi-sub"><?php echo inicio_h($mes_actual_label); ?></div>
        </div>
        <span style="font-size: 20px;">📅</span>
      </div>

      <?php if ($es_u0) { ?>
      <div class="kpi-card">
        <div>
          <div class="kpi-label">Motor de Auditoría</div>
          <div class="kpi-val" style="color: <?php echo $auditoria_ok ? 'var(--accent-green)' : 'var(--accent-red)'; ?>;">
            <?php echo $auditoria_ok ? 'OPERATIVO' : 'CON FALLA'; ?>
          </div>
          <div class="kpi-sub">
            <?php echo $auditoria_ok ? ('Fail-Closed activo • ' . (int)$auditoria_eventos_hoy . ' eventos hoy') : 'Revisar conexión y tablas'; ?>
          </div>
        </div>
        <span style="font-size: 20px;">🛡️</span>
      </div>
      <?php } ?>
    </section>
    <?php } ?>

    <!-- SECCIÓN 1: OPERACIÓN DE PLANILLA RADIOLÓGICA -->
    <div class="sec-head">
      <div class="sec-title">☢️ Operación de Planilla Radiológica</div>
      <div class="sec-count"><?php echo (int)$cant_modulos_op . ($cant_modulos_op === 1 ? ' módulo' : ' módulos'); ?></div>
    </div>
    <section class="mod-grid">
      <?php if ($puede_cargar) { ?>
      <a href="demo_planilla.php" class="mod-card" style="--card-accent: var(--accent-green);">
        <div class="mod-top">
          <div class="mod-ico">⚡</div>
          <div>
            <div class="mod-name">Carga Diaria</div>
            <div class="mod-desc">Consola HMI para carga en tiempo real de parámetros de guardia, tritio, efluentes gaseosos/líquidos, dosimetría y cierre formal.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>Consola Principal</span>
          <span class="badge badge-green">Operativo</span>
        </div>
      </a>
      <?php } ?>

      <a href="vista_planillas_anteriores.php" class="mod-card" style="--card-accent: var(--accent-sky);">
        <div class="mod-top">
          <div class="mod-ico">🗂️</div>
          <div>
            <div class="mod-name">Vista Planillas Anteriores</div>
            <div class="mod-desc">Consulta histórica de guardias cerradas, filtros por fecha/oficial/turno, impresión PDF y exportación de informes.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>Archivo Histórico</span>
          <span class="badge badge-sky"><?php echo $es_u0 ? 'Consulta + Gestión U0' : 'Consulta'; ?></span>
        </div>
      </a>

      <?php if ($puede_ver_curso) { ?>
      <a href="<?php echo inicio_h($url_planilla_curso); ?>" class="mod-card" style="--card-accent: var(--accent-cyan);">
        <div class="mod-top">
          <div class="mod-ico">📊</div>
          <div>
            <div class="mod-name">Ver Informe en Curso</div>
            <div class="mod-desc">Visualización consolidada en modo solo lectura de la guardia abierta actualmente, sin riesgo de modificación accidental.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>Turno Actual</span>
          <span class="badge badge-sky">Solo Lectura</span>
        </div>
      </a>
      <?php } ?>
    </section>

    <?php if ($puede_ver_actas) { ?>
    <!-- SECCIÓN 2: ACTAS Y REGISTROS COMPLEMENTARIOS -->
    <div class="sec-head">
      <div class="sec-title">📒 Actas y Registros Complementarios de SyR</div>
      <div class="sec-count">3 módulos</div>
    </div>
    <section class="mod-grid">
      <a href="libro_actas.php" class="mod-card" style="--card-accent: var(--accent-violet);">
        <div class="mod-top">
          <div class="mod-ico">📒</div>
          <div>
            <div class="mod-name">Vista Libro Actas &mdash; Últimos 15 registros</div>
            <div class="mod-desc">Acceso rápido a las últimas 15 actas registradas en el libro de novedades de Radioprotección.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>libro_actas.php</span>
          <span class="badge badge-violet">Actas</span>
        </div>
      </a>

      <a href="minizonas.php" class="mod-card" style="--card-accent: var(--accent-violet);">
        <div class="mod-top">
          <div class="mod-ico">📍</div>
          <div>
            <div class="mod-name">Minizonas &mdash; Actas</div>
            <div class="mod-desc">Gestión y consulta de actas de habilitación, zonificación radiológica y control de minizonas.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>minizonas.php</span>
          <span class="badge badge-violet">Zonificación</span>
        </div>
      </a>

      <a href="acta_registros_ptr.php" class="mod-card" style="--card-accent: var(--accent-violet);">
        <div class="mod-top">
          <div class="mod-ico">☢️</div>
          <div>
            <div class="mod-name">Registros Auxiliares Radioprotección</div>
            <div class="mod-desc">Registros complementarios y actas vinculadas a Permisos de Trabajo Radiológico (PTR).</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>acta_registros_ptr.php</span>
          <span class="badge badge-violet">Auxiliares</span>
        </div>
      </a>
    </section>
    <?php } ?>

    <?php if ($es_u0) { ?>
    <!-- SECCIÓN 3: ADMINISTRACIÓN Y AUDITORÍA (EXCLUSIVO USUARIO ID 0) -->
    <div class="sec-head">
      <div class="sec-title">🛡️ Auditoría, Seguridad y Administración</div>
      <div class="sec-count">5 módulos</div>
    </div>
    <section class="mod-grid">
      <a href="auditoria_logs.php" class="mod-card" style="--card-accent: var(--accent-amber);">
        <div class="mod-top">
          <div class="mod-ico">🛡️</div>
          <div>
            <div class="mod-name">Log de Auditoría</div>
            <div class="mod-desc">Trazabilidad completa de aperturas, cargas, bajas, cierres, cambios de fecha y ediciones históricas (diff Antes/Después y CSV).</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>auditoria_logs.php</span>
          <span class="badge badge-amber">Exclusivo U0</span>
        </div>
      </a>

      <a href="configuracion_permisos.php" class="mod-card" style="--card-accent: var(--accent-amber);">
        <div class="mod-top">
          <div class="mod-ico">🔐</div>
          <div>
            <div class="mod-name">Permisos y Seguridad Operativa</div>
            <div class="mod-desc">Habilitación granular por usuario (editar cerradas, corregir fecha/turno, ver auditoría) y reglas de autodestrabe.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>configuracion_permisos.php</span>
          <span class="badge badge-amber">Exclusivo U0</span>
        </div>
      </a>

      <a href="configuracion_correo.php" class="mod-card" style="--card-accent: var(--accent-amber);">
        <div class="mod-top">
          <div class="mod-ico">📧</div>
          <div>
            <div class="mod-name">Configuración de Correo</div>
            <div class="mod-desc">Destinatarios automáticos de informes de cierre de guardia y configuración SMTP corporativa.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>configuracion_correo.php</span>
          <span class="badge badge-amber">Exclusivo U0</span>
        </div>
      </a>

      <a href="graficos_varios.php" class="mod-card" style="--card-accent: var(--text-dim);">
        <div class="mod-top">
          <div class="mod-ico">📈</div>
          <div>
            <div class="mod-name">Prueba Gráficos</div>
            <div class="mod-desc">Módulo de gráficos y tendencias comparativas de parámetros radiológicos.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>graficos_varios.php</span>
          <span class="badge badge-amber">Herramienta U0</span>
        </div>
      </a>

      <a href="admin_personal_paradas.php" class="mod-card" style="--card-accent: var(--text-dim);">
        <div class="mod-top">
          <div class="mod-ico">👷</div>
          <div>
            <div class="mod-name">Administración Personal</div>
            <div class="mod-desc">Gestión de nómina y asignaciones de personal para paradas programadas.</div>
          </div>
        </div>
        <div class="mod-foot">
          <span>admin_personal_paradas.php</span>
          <span class="badge badge-amber">Herramienta U0</span>
        </div>
      </a>
    </section>
    <?php } ?>

    <footer class="footer-note">
      <span>NA-SA &bull; Central Nuclear Embalse &bull; Seguridad Radiológica (SyR)</span>
      <span>Registro Radiológico Online</span>
    </footer>
  </div>

  <script>
    function syncThemeUI(themeName) {
      var items = document.querySelectorAll('#theme-menu .theme-item');
      var labelEl = document.getElementById('theme-label-cur');
      var dotEl = document.getElementById('theme-dot-cur');
      for (var i = 0; i < items.length; i++) {
        var it = items[i];
        var match = it.getAttribute('data-theme') === themeName;
        it.classList.toggle('active', match);
        if (match) {
          if (labelEl) labelEl.textContent = it.getAttribute('data-label') || 'Verde';
          if (dotEl) dotEl.style.background = it.getAttribute('data-color') || '#10b981';
        }
      }
    }

    function applyTheme(themeName) {
      document.documentElement.setAttribute('data-theme', themeName);
      try { localStorage.setItem('regrad_active_theme', themeName); } catch (e) {}
      syncThemeUI(themeName);
      var dd = document.getElementById('theme-dropdown');
      if (dd) dd.classList.remove('open');
    }

    function toggleThemeMenu(e) {
      if (e) e.stopPropagation();
      var dd = document.getElementById('theme-dropdown');
      if (dd) dd.classList.toggle('open');
    }

    document.addEventListener('click', function(e) {
      var dd = document.getElementById('theme-dropdown');
      if (dd && dd.classList.contains('open') && !dd.contains(e.target)) {
        dd.classList.remove('open');
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        var dd = document.getElementById('theme-dropdown');
        if (dd) dd.classList.remove('open');
      }
    });

    syncThemeUI(document.documentElement.getAttribute('data-theme') || 'petrol');
  </script>
</body>
</html>