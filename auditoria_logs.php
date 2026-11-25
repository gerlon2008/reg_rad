<?php
/**
 * VISOR CENTRALIZADO DE AUDITORÍA Y TRAZABILIDAD (SyR - CNE)
 * Muestra todos los eventos registrados en `regrad_auditoria_log` con diff Antes -> Después y exportación CSV.
 * Compatible con PHP 5.5.7, Apache 2.4 y MySQL (mysql_*).
 */
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$miconex = null;
if (isset($_SERVER["DOCUMENT_ROOT"]) && file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php")) {
    @include_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
}
if (isset($_SERVER["DOCUMENT_ROOT"]) && file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php")) {
    @include_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Functions/funciones.php");
}
if ($miconex && isset($database_miconex)) {
    @mysql_select_db($database_miconex, $miconex);
    @mysql_query("SET NAMES 'utf8'", $miconex);
}

require_once(__DIR__ . '/auditoria_reg_rad.php');

$ctx = auditoria_obtener_contexto($miconex);
$puede_ver = ($ctx['es_u0'] || auditoria_usuario_tiene_permiso($miconex, 'ver_auditoria'));

if (!$puede_ver) {
    if (function_exists('http_response_code')) http_response_code(403);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Restringido</title>';
    echo '<style>body{background:#061412;color:#f87171;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head>';
    echo '<body><div style="text-align:center;padding:24px;border:1px solid #ef4444;border-radius:8px;background:rgba(239,68,68,0.1);max-width:460px;">';
    echo '<h2>⛔ Acceso Restringido</h2>';
    echo '<p style="color:#cbd5e1;font-size:13.5px;margin:10px 0 16px;">El visor de auditoría es exclusivo para el Usuario 0 y personal habilitado con permiso <strong>ver_auditoria</strong>.</p>';
    echo '<p><a href="demo_planilla.php" style="color:#38bdf8;text-decoration:none;font-weight:700;">&larr; Volver a la Consola Principal</a></p>';
    echo '</div></body></html>';
    exit;
}

$salud = auditoria_verificar_salud($miconex);

// Filtros GET
$f_planilla = isset($_GET['id_planilla']) ? trim($_GET['id_planilla']) : '';
$f_usuario  = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$f_modulo   = isset($_GET['modulo']) ? trim($_GET['modulo']) : '';
$f_accion   = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$f_desde    = isset($_GET['desde']) ? trim($_GET['desde']) : '';
$f_hasta    = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';
$f_post     = isset($_GET['solo_post_cierre']) && $_GET['solo_post_cierre'] === '1';
$f_limite   = isset($_GET['limite']) ? max(20, min(1000, intval($_GET['limite']))) : 200;

$where = array('1=1');
if ($f_planilla !== '' && ctype_digit($f_planilla)) {
    $where[] = "id_planilla = " . intval($f_planilla);
}
if ($f_usuario !== '') {
    $u_esc = auditoria_esc($miconex, $f_usuario);
    $where[] = "(usuario_id = '$u_esc' OR usuario_nombre LIKE '%$u_esc%')";
}
if ($f_modulo !== '') {
    $m_esc = auditoria_esc($miconex, $f_modulo);
    $where[] = "modulo = '$m_esc'";
}
if ($f_accion !== '') {
    $a_esc = auditoria_esc($miconex, $f_accion);
    $where[] = "accion LIKE '%$a_esc%'";
}
if ($f_desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_desde)) {
    $where[] = "fecha_hora >= '" . auditoria_esc($miconex, $f_desde) . " 00:00:00'";
}
if ($f_hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_hasta)) {
    $where[] = "fecha_hora <= '" . auditoria_esc($miconex, $f_hasta) . " 23:59:59'";
}
if ($f_post) {
    $where[] = "(planilla_cerrada = 1 OR accion = 'CORREGIR_FECHA_TURNO' OR modulo = 'PERMISOS')";
}

$where_sql = implode(' AND ', $where);
$logs = array();
$stats = array('total' => 0, 'post_cierre' => 0, 'eliminaciones' => 0, 'correcciones' => 0);

if ($miconex && $salud['ok']) {
    $q_st = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN planilla_cerrada = 1 THEN 1 ELSE 0 END) AS post_cierre,
                SUM(CASE WHEN accion = 'ELIMINAR' THEN 1 ELSE 0 END) AS eliminaciones,
                SUM(CASE WHEN accion = 'CORREGIR_FECHA_TURNO' THEN 1 ELSE 0 END) AS correcciones
             FROM `regrad_auditoria_log` WHERE $where_sql";
    $r_st = @mysql_query($q_st, $miconex);
    if ($r_st && mysql_num_rows($r_st) > 0) {
        $rw_st = mysql_fetch_assoc($r_st);
        $stats['total'] = intval($rw_st['total']);
        $stats['post_cierre'] = intval($rw_st['post_cierre']);
        $stats['eliminaciones'] = intval($rw_st['eliminaciones']);
        $stats['correcciones'] = intval($rw_st['correcciones']);
    }

    $q_logs = "SELECT * FROM `regrad_auditoria_log` WHERE $where_sql ORDER BY id_log DESC LIMIT $f_limite";
    $r_logs = @mysql_query($q_logs, $miconex);
    if ($r_logs && mysql_num_rows($r_logs) > 0) {
        while ($row = mysql_fetch_assoc($r_logs)) {
            $logs[] = $row;
        }
    }
}

// Exportación CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="auditoria_reg_rad_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    // BOM UTF-8 para Excel
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('ID_LOG', 'FECHA_HORA', 'ID_PLANILLA', 'POST_CIERRE', 'USUARIO_ID', 'USUARIO_NOMBRE', 'IP', 'MODULO', 'ACCION', 'TABLA', 'ID_REGISTRO', 'MOTIVO', 'DATOS_ANTES', 'DATOS_DESPUES'), ';');
    foreach ($logs as $lg) {
        fputcsv($out, array(
            $lg['id_log'],
            $lg['fecha_hora'],
            $lg['id_planilla'],
            $lg['planilla_cerrada'] ? 'SI' : 'NO',
            $lg['usuario_id'],
            $lg['usuario_nombre'],
            $lg['ip_origen'],
            $lg['modulo'],
            $lg['accion'],
            $lg['tabla_afectada'],
            $lg['id_registro'],
            $lg['motivo'],
            $lg['datos_antes'],
            $lg['datos_despues']
        ), ';');
    }
    fclose($out);
    exit;
}

$modulos_catalogo = array('PLANILLA', 'RUTINA', 'GASES_NOBLES', 'TRITIO', 'C14', 'DEDO_FRIO', 'CONCENTRADOR_7923', 'PRECINTOS', 'METEOROLOGIA', 'ESPECTROMETRIA', 'LIQUIDOS', 'CONT_SUPERFICIAL', 'DRYERS', 'ACTAS', 'MINIZONAS', 'CORREO', 'PERMISOS');

function fmt_fecha_hora_lat($dt) {
    if (empty($dt)) return '-';
    $ts = strtotime($dt);
    return ($ts !== false) ? date('d/m/Y H:i:s', $ts) : $dt;
}

function render_diff_html($antes_raw, $despues_raw) {
    $antes = !empty($antes_raw) ? @json_decode($antes_raw, true) : null;
    $despues = !empty($despues_raw) ? @json_decode($despues_raw, true) : null;

    if (!is_array($antes) && !is_array($despues)) {
        return '<div style="color:var(--text-dim);font-size:11.5px;">Sin detalle JSON adjunto.</div>';
    }

    $claves = array();
    if (is_array($antes)) {
        foreach ($antes as $k => $v) $claves[$k] = true;
    }
    if (is_array($despues)) {
        foreach ($despues as $k => $v) $claves[$k] = true;
    }
    // Omitir campo redundante log_modificaciones si es muy largo
    unset($claves['log_modificaciones']);

    $html = '<table class="diff-table"><thead><tr><th>Campo</th><th>Antes</th><th>Después</th></tr></thead><tbody>';
    foreach (array_keys($claves) as $k) {
        $va = (is_array($antes) && array_key_exists($k, $antes)) ? $antes[$k] : null;
        $vd = (is_array($despues) && array_key_exists($k, $despues)) ? $despues[$k] : null;

        $sa = is_array($va) ? json_encode($va) : ($va === null ? '—' : (string)$va);
        $sd = is_array($vd) ? json_encode($vd) : ($vd === null ? '—' : (string)$vd);

        $cambio = ($sa !== $sd);
        $cls = $cambio ? 'diff-changed' : '';
        $html .= '<tr class="' . $cls . '">';
        $html .= '<td class="mono" style="font-weight:700;color:var(--text-muted);">' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td class="mono" style="color:' . ($cambio ? '#fca5a5' : 'var(--text-dim)') . ';">' . htmlspecialchars($sa, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td class="mono" style="color:' . ($cambio ? '#6ee7b7' : 'var(--text-dim)') . ';">' . htmlspecialchars($sd, ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SyR &bull; Auditoría y Trazabilidad de Registros</title>
  <style>
    :root {
      --bg-page: #061412;
      --bg-card: #0e2722;
      --bg-subtle: #14332d;
      --bg-input: #081a16;
      --border-card: #1f4d43;
      --border-subtle: #13352e;
      --text-main: #edfcf6;
      --text-muted: #8ec7b9;
      --text-dim: #5c9186;
      --accent-cyan: #38bdf8;
      --accent-green: #10b981;
      --accent-amber: #f59e0b;
      --accent-red: #ef4444;
      --font-mono: 'Consolas', 'Monaco', monospace;
      --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg-page);
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 13px;
      padding: 20px;
      min-height: 100vh;
    }
    .page-wrap { max-width: 1360px; margin: 0 auto; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
      background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 8px;
      padding: 14px 18px; margin-bottom: 16px;
    }
    .topbar h1 { font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .topbar-sub { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
    .nav-links { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .btn-link {
      text-decoration: none; color: var(--text-main); background: var(--bg-subtle);
      border: 1px solid var(--border-card); padding: 7px 12px; border-radius: 6px;
      font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;
      cursor: pointer; transition: 0.15s;
    }
    .btn-link:hover { border-color: var(--accent-cyan); color: var(--accent-cyan); }
    .btn-link.primary { background: var(--accent-green); border-color: var(--accent-green); color: #04120e; }
    .btn-link.primary:hover { filter: brightness(1.08); color: #04120e; }
    .health-pill {
      display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px;
      border-radius: 999px; font-size: 11px; font-weight: 800;
    }
    .health-pill.ok { background: rgba(16,185,129,0.16); border: 1px solid rgba(16,185,129,0.45); color: #34d399; }
    .health-pill.err { background: rgba(239,68,68,0.16); border: 1px solid rgba(239,68,68,0.45); color: #fca5a5; }
    .stats-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 16px;
    }
    .stat-card {
      background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 8px;
      padding: 12px 16px;
    }
    .stat-lbl { font-size: 10.5px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; }
    .stat-val { font-size: 22px; font-weight: 800; font-family: var(--font-mono); margin-top: 4px; }
    .filters-box {
      background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 8px;
      padding: 14px 16px; margin-bottom: 16px;
    }
    .filters-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; align-items: end;
    }
    .field-lbl { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
    .field-ctrl {
      width: 100%; background: var(--bg-input); border: 1px solid var(--border-card);
      color: var(--text-main); padding: 7px 10px; border-radius: 5px; font-size: 12px;
    }
    .field-ctrl:focus { outline: none; border-color: var(--accent-cyan); }
    .table-card {
      background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 8px; overflow: hidden;
    }
    .log-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .log-table th {
      text-align: left; padding: 10px 12px; background: rgba(0,0,0,0.3);
      color: var(--text-muted); font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.4px;
      border-bottom: 1px solid var(--border-card);
    }
    .log-table td {
      padding: 9px 12px; border-bottom: 1px solid var(--border-subtle); vertical-align: top;
    }
    .log-table tr.row-post-cierre { background: rgba(245, 158, 11, 0.06); }
    .log-table tr:hover { background: rgba(56, 189, 248, 0.06); }
    .mono { font-family: var(--font-mono); }
    .badge {
      display: inline-block; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 800;
      letter-spacing: 0.3px; text-transform: uppercase;
    }
    .badge-create { background: rgba(16,185,129,0.18); color: #34d399; border: 1px solid rgba(16,185,129,0.4); }
    .badge-edit { background: rgba(56,189,248,0.16); color: #7dd3fc; border: 1px solid rgba(56,189,248,0.4); }
    .badge-del { background: rgba(239,68,68,0.18); color: #fca5a5; border: 1px solid rgba(239,68,68,0.45); }
    .badge-warn { background: rgba(245,158,11,0.2); color: #fde68a; border: 1px solid rgba(245,158,11,0.5); }
    .badge-mod { background: rgba(255,255,255,0.07); color: var(--text-main); border: 1px solid var(--border-card); }
    .btn-diff {
      background: transparent; border: 1px solid var(--border-card); color: var(--accent-cyan);
      padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; cursor: pointer;
    }
    .btn-diff:hover { background: rgba(56,189,248,0.12); }
    .diff-row { display: none; background: #071916; }
    .diff-row.open { display: table-row; }
    .diff-table { width: 100%; border-collapse: collapse; font-size: 11.5px; margin-top: 4px; }
    .diff-table th { background: rgba(0,0,0,0.4); padding: 5px 8px; font-size: 10px; }
    .diff-table td { padding: 5px 8px; border-bottom: 1px solid rgba(31,77,67,0.5); word-break: break-word; }
    .diff-table tr.diff-changed { background: rgba(245,158,11,0.08); }
  </style>
</head>
<body>
<div class="page-wrap">
  <div class="topbar">
    <div>
      <h1>
        <span>🧾</span> AUDITORÍA Y TRAZABILIDAD &bull; REGISTRO RADIOLÓGICO
        <span class="health-pill <?php echo $salud['ok'] ? 'ok' : 'err'; ?>">
          <?php echo $salud['ok'] ? '🟢 Log Operativo' : '🔴 Log con Error'; ?>
        </span>
      </h1>
      <div class="topbar-sub">
        Sesión: <strong><?php echo htmlspecialchars($ctx['usuario_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong> (ID: <?php echo htmlspecialchars($ctx['usuario_id'], ENT_QUOTES, 'UTF-8'); ?>)
        &bull; Registro inmutable (append-only) de altas, modificaciones, borrados, cierres y correcciones
      </div>
    </div>
    <div class="nav-links">
      <a class="btn-link" href="demo_planilla.php">📋 Consola Principal</a>
      <a class="btn-link" href="vista_planillas_anteriores.php">📁 Planillas Anteriores</a>
      <?php if ($ctx['es_u0']): ?>
        <a class="btn-link" href="configuracion_permisos.php" style="border-color: var(--accent-amber); color: var(--accent-amber);">🛡️ Permisos (U0)</a>
      <?php endif; ?>
      <a class="btn-link primary" href="?<?php echo htmlspecialchars(http_build_query(array_merge($_GET, array('export' => 'csv'))), ENT_QUOTES, 'UTF-8'); ?>">⬇️ Exportar CSV</a>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-lbl">Eventos Coincidentes</div>
      <div class="stat-val" style="color: var(--accent-cyan);"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-lbl">Modificaciones Post-Cierre</div>
      <div class="stat-val" style="color: var(--accent-amber);"><?php echo number_format($stats['post_cierre'], 0, ',', '.'); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-lbl">Eliminaciones Registradas</div>
      <div class="stat-val" style="color: var(--accent-red);"><?php echo number_format($stats['eliminaciones'], 0, ',', '.'); ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-lbl">Correcciones Fecha / Turno</div>
      <div class="stat-val" style="color: var(--accent-green);"><?php echo number_format($stats['correcciones'], 0, ',', '.'); ?></div>
    </div>
  </div>

  <form method="GET" class="filters-box">
    <div class="filters-grid">
      <div>
        <label class="field-lbl">N° Planilla</label>
        <input type="text" name="id_planilla" class="field-ctrl mono" value="<?php echo htmlspecialchars($f_planilla, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej: 10843">
      </div>
      <div>
        <label class="field-lbl">Usuario (ID o Nombre)</label>
        <input type="text" name="usuario" class="field-ctrl" value="<?php echo htmlspecialchars($f_usuario, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ej: 0 o LONCAR">
      </div>
      <div>
        <label class="field-lbl">Módulo</label>
        <select name="modulo" class="field-ctrl">
          <option value="">Todos</option>
          <?php foreach ($modulos_catalogo as $mc): ?>
            <option value="<?php echo $mc; ?>" <?php if ($f_modulo === $mc) echo 'selected'; ?>><?php echo $mc; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="field-lbl">Acción</label>
        <input type="text" name="accion" class="field-ctrl" value="<?php echo htmlspecialchars($f_accion, ENT_QUOTES, 'UTF-8'); ?>" placeholder="CREAR, ELIMINAR...">
      </div>
      <div>
        <label class="field-lbl">Desde</label>
        <input type="date" name="desde" class="field-ctrl mono" value="<?php echo htmlspecialchars($f_desde, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div>
        <label class="field-lbl">Hasta</label>
        <input type="date" name="hasta" class="field-ctrl mono" value="<?php echo htmlspecialchars($f_hasta, ENT_QUOTES, 'UTF-8'); ?>">
      </div>
      <div>
        <label class="field-lbl" style="display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--accent-amber);">
          <input type="checkbox" name="solo_post_cierre" value="1" <?php if ($f_post) echo 'checked'; ?>>
          Solo Post-Cierre / Críticos
        </label>
        <div style="display:flex;gap:6px;margin-top:4px;">
          <button type="submit" class="btn-link primary" style="flex:1;justify-content:center;">🔍 Filtrar</button>
          <a href="auditoria_logs.php" class="btn-link" style="justify-content:center;">Limpiar</a>
        </div>
      </div>
    </div>
  </form>

  <div class="table-card">
    <table class="log-table">
      <thead>
        <tr>
          <th style="width:70px;">#Log</th>
          <th style="width:140px;">Fecha / Hora</th>
          <th style="width:105px;">Planilla</th>
          <th style="width:190px;">Usuario / IP</th>
          <th style="width:145px;">Módulo / Acción</th>
          <th>Tabla / Motivo</th>
          <th style="width:110px;">Detalle</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="7" style="text-align:center;padding:30px;color:var(--text-dim);">
              No se encontraron eventos de auditoría con los filtros aplicados.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $lg):
            $acc = strtoupper($lg['accion']);
            $badge_cls = 'badge-edit';
            if (strpos($acc, 'CREAR') !== false || strpos($acc, 'AGREGAR') !== false) $badge_cls = 'badge-create';
            elseif (strpos($acc, 'ELIMINAR') !== false) $badge_cls = 'badge-del';
            elseif (strpos($acc, 'CORREGIR') !== false || strpos($acc, 'CERRAR') !== false) $badge_cls = 'badge-warn';
          ?>
            <tr class="<?php echo !empty($lg['planilla_cerrada']) ? 'row-post-cierre' : ''; ?>">
              <td class="mono" style="color:var(--text-dim);">#<?php echo intval($lg['id_log']); ?></td>
              <td class="mono"><?php echo htmlspecialchars(fmt_fecha_hora_lat($lg['fecha_hora']), ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="mono">
                <?php if (!empty($lg['id_planilla'])): ?>
                  <a href="planilla_en_curso_visualizacion.php?id_planilla=<?php echo intval($lg['id_planilla']); ?>" target="_blank" style="color:var(--accent-cyan);font-weight:700;text-decoration:none;">
                    #<?php echo intval($lg['id_planilla']); ?>
                  </a>
                  <?php if (!empty($lg['planilla_cerrada'])): ?>
                    <div style="margin-top:3px;"><span class="badge badge-warn">POST-CIERRE</span></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color:var(--text-dim);">Global</span>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:700;color:#fff;"><?php echo htmlspecialchars($lg['usuario_nombre'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="mono" style="font-size:10.5px;color:var(--text-dim);">
                  ID: <?php echo htmlspecialchars($lg['usuario_id'], ENT_QUOTES, 'UTF-8'); ?> &bull; <?php echo htmlspecialchars($lg['ip_origen'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </td>
              <td>
                <span class="badge badge-mod"><?php echo htmlspecialchars($lg['modulo'], ENT_QUOTES, 'UTF-8'); ?></span>
                <div style="margin-top:4px;"><span class="badge <?php echo $badge_cls; ?>"><?php echo htmlspecialchars($lg['accion'], ENT_QUOTES, 'UTF-8'); ?></span></div>
              </td>
              <td>
                <div class="mono" style="font-size:11px;color:var(--text-muted);">
                  <?php echo htmlspecialchars($lg['tabla_afectada'] ? $lg['tabla_afectada'] : '-', ENT_QUOTES, 'UTF-8'); ?>
                  <?php if (!empty($lg['id_registro'])): ?>
                    <span style="color:var(--text-dim);">(ID: <?php echo htmlspecialchars($lg['id_registro'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($lg['motivo'])): ?>
                  <div style="margin-top:4px;color:#fde68a;font-size:11.5px;">
                    <strong>Motivo:</strong> <?php echo htmlspecialchars($lg['motivo'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <button type="button" class="btn-diff" onclick="toggleDiff(<?php echo intval($lg['id_log']); ?>)">🔎 Ver Diff</button>
              </td>
            </tr>
            <tr id="diff-row-<?php echo intval($lg['id_log']); ?>" class="diff-row">
              <td colspan="7" style="padding:10px 16px;">
                <?php echo render_diff_html($lg['datos_antes'], $lg['datos_despues']); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function toggleDiff(idLog) {
    var row = document.getElementById('diff-row-' + idLog);
    if (row) {
      row.classList.toggle('open');
    }
  }
</script>
</body>
</html>
