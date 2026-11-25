<?php
/**
 * PANEL DE PERMISOS GRANULARES Y SEGURIDAD OPERATIVA (EXCLUSIVO USUARIO 0)
 * Registro Radiológico Online - Central Nuclear Embalse (NA-SA)
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
if (!$ctx['es_u0']) {
    if (function_exists('http_response_code')) http_response_code(403);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Restringido</title>';
    echo '<style>body{background:#061412;color:#f87171;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head>';
    echo '<body><div style="text-align:center;padding:24px;border:1px solid #ef4444;border-radius:8px;background:rgba(239,68,68,0.1);max-width:460px;">';
    echo '<h2>⛔ Acceso Restringido</h2>';
    echo '<p style="color:#cbd5e1;font-size:13.5px;margin:10px 0 16px;">La administración de permisos y seguridad operativa es exclusiva para el <strong>Usuario 0</strong>.</p>';
    echo '<p><a href="demo_planilla.php" style="color:#38bdf8;text-decoration:none;font-weight:700;">&larr; Volver a la Consola Principal</a></p>';
    echo '</div></body></html>';
    exit;
}

$salud = auditoria_verificar_salud($miconex);
$mensaje = '';
$tipo_msg = 'info';

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$salud['ok']) {
        $mensaje = '❌ Operación abortada (Fail-Closed): el log de auditoría no está operativo. No se pueden modificar permisos sin registro.';
        $tipo_msg = 'error';
    } elseif (isset($_POST['accion_permiso']) && $_POST['accion_permiso'] === 'guardar_usuario') {
        $uid = isset($_POST['usuario_id']) ? trim($_POST['usuario_id']) : '';
        $unom = isset($_POST['usuario_nombre']) ? trim($_POST['usuario_nombre']) : '';
        $p_editar = !empty($_POST['editar_planillas_cerradas']) ? 1 : 0;
        $p_fecha  = !empty($_POST['corregir_fecha_turno']) ? 1 : 0;
        $p_ver    = !empty($_POST['ver_auditoria']) ? 1 : 0;
        $activo   = !empty($_POST['activo']) ? 1 : 0;

        if ($uid === '' || $uid === '0') {
            $mensaje = '⚠️ Debe indicar un ID de usuario válido distinto de 0 (el Usuario 0 siempre tiene acceso total y auditado).';
            $tipo_msg = 'warning';
        } else {
            if ($unom === '' && function_exists('nombre_usuario_web')) {
                $unom = trim((string)@nombre_usuario_web($uid));
            }
            if ($unom === '') {
                $unom = 'USUARIO #' . $uid;
            }
            $unom_up = function_exists('mb_strtoupper') ? mb_strtoupper($unom, 'UTF-8') : strtoupper($unom);

            $antes = auditoria_snapshot_fila($miconex, 'regrad_permisos_usuarios', 'usuario_id', $uid);
            $uid_esc = auditoria_esc($miconex, $uid);
            $unom_esc = auditoria_esc($miconex, $unom_up);
            $ahora = date('Y-m-d H:i:s');
            $u0_esc = auditoria_esc($miconex, $ctx['usuario_id']);

            $q_up = "INSERT INTO `regrad_permisos_usuarios`
                (`usuario_id`, `usuario_nombre`, `editar_planillas_cerradas`, `corregir_fecha_turno`, `ver_auditoria`, `activo`, `actualizado_el`, `actualizado_por`)
                VALUES ('$uid_esc', '$unom_esc', $p_editar, $p_fecha, $p_ver, $activo, '$ahora', '$u0_esc')
                ON DUPLICATE KEY UPDATE
                `usuario_nombre` = '$unom_esc',
                `editar_planillas_cerradas` = $p_editar,
                `corregir_fecha_turno` = $p_fecha,
                `ver_auditoria` = $p_ver,
                `activo` = $activo,
                `actualizado_el` = '$ahora',
                `actualizado_por` = '$u0_esc'";

            if (@mysql_query($q_up, $miconex)) {
                $despues = auditoria_snapshot_fila($miconex, 'regrad_permisos_usuarios', 'usuario_id', $uid);
                auditoria_registrar($miconex, array(
                    'modulo' => 'PERMISOS',
                    'accion' => $antes ? 'EDITAR_PERMISO' : 'OTORGAR_PERMISO',
                    'tabla_afectada' => 'regrad_permisos_usuarios',
                    'id_registro' => $uid,
                    'datos_antes' => $antes,
                    'datos_despues' => $despues,
                    'motivo' => 'Gestión de permisos granulares desde Panel U0'
                ));
                $mensaje = '✔️ Permisos guardados y auditados para ' . $unom_up . ' (ID: ' . $uid . ').';
                $tipo_msg = 'exito';
            } else {
                $mensaje = '❌ Error SQL al guardar permisos: ' . mysql_error($miconex);
                $tipo_msg = 'error';
            }
        }
    } elseif (isset($_POST['accion_permiso']) && $_POST['accion_permiso'] === 'eliminar_usuario') {
        $uid = isset($_POST['usuario_id']) ? trim($_POST['usuario_id']) : '';
        if ($uid !== '' && $uid !== '0') {
            $antes = auditoria_snapshot_fila($miconex, 'regrad_permisos_usuarios', 'usuario_id', $uid);
            $uid_esc = auditoria_esc($miconex, $uid);
            if (@mysql_query("DELETE FROM `regrad_permisos_usuarios` WHERE usuario_id = '$uid_esc'", $miconex)) {
                auditoria_registrar($miconex, array(
                    'modulo' => 'PERMISOS',
                    'accion' => 'REVOCAR_PERMISO',
                    'tabla_afectada' => 'regrad_permisos_usuarios',
                    'id_registro' => $uid,
                    'datos_antes' => $antes,
                    'motivo' => 'Revocación de permisos desde Panel U0'
                ));
                $mensaje = '🗑️ Permisos revocados y registrados en auditoría para ID ' . $uid . '.';
                $tipo_msg = 'exito';
            }
        }
    } elseif (isset($_POST['accion_permiso']) && $_POST['accion_permiso'] === 'guardar_seguridad') {
        $auto_dest = !empty($_POST['permitir_autodestrabe_emergencia']);
        $ventana_h = isset($_POST['ventana_horas_autodestrabe']) ? max(1, min(72, intval($_POST['ventana_horas_autodestrabe']))) : 24;
        $alertas_on = !empty($_POST['alertas_mail_activas']);
        $dest_raw = isset($_POST['destinatarios_alertas']) ? $_POST['destinatarios_alertas'] : '';
        $parts = preg_split('/[\r\n,;]+/', $dest_raw);
        $dest_limpios = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '' && filter_var($p, FILTER_VALIDATE_EMAIL)) {
                $dest_limpios[] = $p;
            }
        }
        if (empty($dest_limpios)) {
            $dest_limpios = array('gloncar@na-sa.com.ar');
        }

        $nueva_cfg = array(
            'permitir_autodestrabe_emergencia' => $auto_dest,
            'ventana_horas_autodestrabe' => $ventana_h,
            'alertas_mail_activas' => $alertas_on,
            'destinatarios_alertas' => array_values(array_unique($dest_limpios)),
            'actualizado_el' => date('Y-m-d H:i:s'),
            'actualizado_por' => $ctx['usuario_id']
        );

        if (auditoria_guardar_config_seguridad($miconex, $nueva_cfg)) {
            $mensaje = '✔️ Política de seguridad operativa guardada y auditada.';
            $tipo_msg = 'exito';
        } else {
            $mensaje = '⚠️ Configuración guardada localmente, pero no se pudo actualizar MySQL.';
            $tipo_msg = 'warning';
        }
    }
}

// Listar permisos configurados
$permisos_lista = array();
if ($miconex && $salud['ok']) {
    $rp = @mysql_query("SELECT * FROM `regrad_permisos_usuarios` ORDER BY usuario_nombre ASC", $miconex);
    if ($rp && mysql_num_rows($rp) > 0) {
        while ($row = mysql_fetch_assoc($rp)) {
            $permisos_lista[] = $row;
        }
    }
}

// Descubrir usuarios conocidos del portal a partir de planillas históricas
$usuarios_conocidos = array();
if ($miconex) {
    $q_u = "SELECT DISTINCT usuario_portal_abierto AS uid FROM `regrad_planilla_diaria_nueva` WHERE usuario_portal_abierto IS NOT NULL AND usuario_portal_abierto <> '' AND usuario_portal_abierto <> '0'
            UNION
            SELECT DISTINCT usuario_portal_cerrado AS uid FROM `regrad_planilla_diaria_nueva` WHERE usuario_portal_cerrado IS NOT NULL AND usuario_portal_cerrado <> '' AND usuario_portal_cerrado <> '0'
            LIMIT 80";
    $ru = @mysql_query($q_u, $miconex);
    if ($ru && mysql_num_rows($ru) > 0) {
        while ($rw = mysql_fetch_assoc($ru)) {
            $uid_k = trim((string)$rw['uid']);
            if ($uid_k === '' || $uid_k === '0') continue;
            $nom_k = '';
            if (function_exists('nombre_usuario_web')) {
                $nom_k = trim((string)@nombre_usuario_web($uid_k));
            }
            $usuarios_conocidos[$uid_k] = $nom_k !== '' ? (function_exists('mb_strtoupper') ? mb_strtoupper($nom_k, 'UTF-8') : strtoupper($nom_k)) : ('USUARIO #' . $uid_k);
        }
        asort($usuarios_conocidos);
    }
}

$cfg_seg = auditoria_obtener_config_seguridad($miconex);
$dest_alertas_txt = implode("\n", !empty($cfg_seg['destinatarios_alertas']) ? (array)$cfg_seg['destinatarios_alertas'] : array('gloncar@na-sa.com.ar'));
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SyR &bull; Permisos y Seguridad Operativa (Usuario 0)</title>
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
    body { background: var(--bg-page); color: var(--text-main); font-family: var(--font-sans); font-size: 13px; padding: 20px; }
    .wrap { max-width: 1080px; margin: 0 auto; }
    .topbar {
      display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
      background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 8px;
      padding: 14px 18px; margin-bottom: 16px;
    }
    .topbar h1 { font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .nav-links { display: flex; gap: 8px; flex-wrap: wrap; }
    .btn {
      text-decoration: none; color: var(--text-main); background: var(--bg-subtle);
      border: 1px solid var(--border-card); padding: 7px 12px; border-radius: 6px;
      font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;
      cursor: pointer; transition: 0.15s;
    }
    .btn:hover { border-color: var(--accent-cyan); color: var(--accent-cyan); }
    .btn.primary { background: var(--accent-green); border-color: var(--accent-green); color: #04120e; }
    .btn.danger { background: rgba(239,68,68,0.14); border-color: rgba(239,68,68,0.45); color: #fca5a5; }
    .btn.danger:hover { background: rgba(239,68,68,0.28); color: #fff; }
    .alert {
      padding: 12px 15px; border-radius: 7px; margin-bottom: 16px; font-size: 12.5px; font-weight: 600;
    }
    .alert.exito { background: rgba(16,185,129,0.14); border: 1px solid rgba(16,185,129,0.45); color: #6ee7b7; }
    .alert.error { background: rgba(239,68,68,0.14); border: 1px solid rgba(239,68,68,0.45); color: #fca5a5; }
    .alert.warning { background: rgba(245,158,11,0.14); border: 1px solid rgba(245,158,11,0.45); color: #fde68a; }
    .card {
      background: var(--bg-card); border: 1px solid var(--border-card); border-radius: 8px;
      margin-bottom: 16px; overflow: hidden;
    }
    .card-hdr {
      padding: 12px 16px; background: rgba(0,0,0,0.25); border-bottom: 1px solid var(--border-card);
      font-weight: 800; font-size: 13px; display: flex; justify-content: space-between; align-items: center;
    }
    .card-body { padding: 16px; }
    .field-lbl { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 4px; }
    .field-ctrl {
      width: 100%; background: var(--bg-input); border: 1px solid var(--border-card);
      color: var(--text-main); padding: 8px 10px; border-radius: 5px; font-size: 12.5px;
    }
    .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
    .check-row {
      display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px;
      background: rgba(0,0,0,0.22); border: 1px solid var(--border-subtle); border-radius: 6px;
      cursor: pointer; margin-bottom: 8px;
    }
    .check-row input { margin-top: 2px; transform: scale(1.15); }
    .perm-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .perm-table th {
      text-align: left; padding: 9px 12px; background: rgba(0,0,0,0.3); color: var(--text-muted);
      font-size: 10.5px; text-transform: uppercase; border-bottom: 1px solid var(--border-card);
    }
    .perm-table td { padding: 9px 12px; border-bottom: 1px solid var(--border-subtle); }
    .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 800; }
    .pill.on { background: rgba(16,185,129,0.18); color: #34d399; border: 1px solid rgba(16,185,129,0.45); }
    .pill.off { background: rgba(255,255,255,0.06); color: var(--text-dim); border: 1px solid var(--border-subtle); }
    .mono { font-family: var(--font-mono); }
  </style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <div>
      <h1>🛡️ PERMISOS GRANULARES Y SEGURIDAD OPERATIVA <span class="pill" style="background:var(--accent-amber);color:#000;">U0</span></h1>
      <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px;">
        Todas las acciones (incluso las del Usuario 0) quedan registradas en <code>regrad_auditoria_log</code>.
      </div>
    </div>
    <div class="nav-links">
      <a class="btn" href="demo_planilla.php">📋 Consola Principal</a>
      <a class="btn" href="vista_planillas_anteriores.php">📁 Planillas Anteriores</a>
      <a class="btn" href="auditoria_logs.php" style="border-color:var(--accent-cyan);color:var(--accent-cyan);">🧾 Ver Auditoría / Logs</a>
      <a class="btn" href="configuracion_correo.php">✉️ Config. Correo</a>
    </div>
  </div>

  <?php if ($mensaje !== ''): ?>
    <div class="alert <?php echo $tipo_msg; ?>"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <!-- SEMÁFORO FAIL-CLOSED -->
  <div class="card">
    <div class="card-hdr">
      <span>Estado del Motor de Auditoría (Garantía Fail-Closed)</span>
      <span class="pill <?php echo $salud['ok'] ? 'on' : 'off'; ?>" style="<?php if (!$salud['ok']) echo 'background:rgba(239,68,68,0.2);color:#fca5a5;border-color:rgba(239,68,68,0.5);'; ?>">
        <?php echo $salud['ok'] ? '🟢 LOG OPERATIVO' : '🔴 LOG NO DISPONIBLE'; ?>
      </span>
    </div>
    <div class="card-body" style="font-size:12px;color:var(--text-muted);line-height:1.5;">
      <?php if ($salud['ok']): ?>
        El sistema confirmó acceso de lectura/escritura a <code>regrad_auditoria_log</code>. Las ediciones históricas y correcciones de fecha/turno están habilitadas únicamente para usuarios autorizados y exigen motivo obligatorio.
      <?php else: ?>
        <strong style="color:#fca5a5;">Bloqueo automático activo:</strong> <?php echo htmlspecialchars($salud['mensaje'], ENT_QUOTES, 'UTF-8'); ?>. Ningún usuario (incluido Usuario 0) podrá modificar planillas cerradas hasta que el log vuelva a estar operativo.
      <?php endif; ?>
    </div>
  </div>

  <!-- ALTA / EDICIÓN DE PERMISOS -->
  <div class="card">
    <div class="card-hdr"><span>➕ Habilitar o Modificar Permisos de Usuario</span></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="accion_permiso" value="guardar_usuario">
        <div class="grid-2" style="margin-bottom: 12px;">
          <div>
            <label class="field-lbl">Selección rápida (usuarios detectados en planillas)</label>
            <select id="sel-usuario-conocido" class="field-ctrl" onchange="seleccionarUsuarioConocido()">
              <option value="">— Seleccionar usuario conocido o ingresar ID manual —</option>
              <?php foreach ($usuarios_conocidos as $uid_c => $nom_c): ?>
                <option value="<?php echo htmlspecialchars($uid_c, ENT_QUOTES, 'UTF-8'); ?>" data-nombre="<?php echo htmlspecialchars($nom_c, ENT_QUOTES, 'UTF-8'); ?>">
                  [ID <?php echo htmlspecialchars($uid_c, ENT_QUOTES, 'UTF-8'); ?>] <?php echo htmlspecialchars($nom_c, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:grid;grid-template-columns:130px 1fr auto;gap:8px;align-items:end;">
            <div>
              <label class="field-lbl">ID Usuario Portal</label>
              <input type="text" name="usuario_id" id="inp-usuario-id" class="field-ctrl mono" placeholder="Ej: 14" required>
            </div>
            <div>
              <label class="field-lbl">Nombre / Apellido</label>
              <input type="text" name="usuario_nombre" id="inp-usuario-nombre" class="field-ctrl" placeholder="Se autocompleta o ingrese manual">
            </div>
            <button type="button" class="btn" onclick="buscarNombreUsuario()" title="Resolver nombre vía nombre_usuario_web()">🔍</button>
          </div>
        </div>

        <div class="grid-2" style="margin-bottom: 12px;">
          <label class="check-row">
            <input type="checkbox" name="editar_planillas_cerradas" id="chk-p-editar" value="1">
            <div>
              <div style="font-weight:700;color:#fff;">✏️ Editar Planillas Cerradas / Anteriores</div>
              <div style="font-size:11px;color:var(--text-muted);">Permite corregir datos en planillas cerradas (siempre con motivo obligatorio y registro POST-CIERRE).</div>
            </div>
          </label>
          <label class="check-row">
            <input type="checkbox" name="corregir_fecha_turno" id="chk-p-fecha" value="1">
            <div>
              <div style="font-weight:700;color:#fff;">📅 Corregir Fecha / Turno de Planillas</div>
              <div style="font-size:11px;color:var(--text-muted);">Permite reubicar planillas mal abiertas que bloquean al turno siguiente (sin dar edición libre de mediciones).</div>
            </div>
          </label>
          <label class="check-row">
            <input type="checkbox" name="ver_auditoria" id="chk-p-ver" value="1">
            <div>
              <div style="font-weight:700;color:#fff;">🧾 Consultar Visor de Auditoría / Logs</div>
              <div style="font-size:11px;color:var(--text-muted);">Habilita acceso de solo lectura a <code>auditoria_logs.php</code> y exportación CSV.</div>
            </div>
          </label>
          <label class="check-row">
            <input type="checkbox" name="activo" id="chk-p-activo" value="1" checked>
            <div>
              <div style="font-weight:700;color:#34d399;">✅ Permiso Activo</div>
              <div style="font-size:11px;color:var(--text-muted);">Desmarcar para suspender temporalmente sin borrar el registro.</div>
            </div>
          </label>
        </div>

        <button type="submit" class="btn primary">💾 Guardar Permisos (Auditado)</button>
      </form>
    </div>
  </div>

  <!-- TABLA DE USUARIOS HABILITADOS -->
  <div class="card">
    <div class="card-hdr">
      <span>👥 Usuarios Autorizados Actualmente</span>
      <span style="font-size:11px;color:var(--text-muted);">Usuario 0 siempre activo + <?php echo count($permisos_lista); ?> adicional(es)</span>
    </div>
    <table class="perm-table">
      <thead>
        <tr>
          <th>Usuario</th>
          <th>Editar Cerradas</th>
          <th>Corregir Fecha/Turno</th>
          <th>Ver Auditoría</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr style="background:rgba(245,158,11,0.06);">
          <td>
            <strong><?php echo htmlspecialchars($ctx['usuario_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <div class="mono" style="font-size:10.5px;color:var(--accent-amber);">ID: 0 (Superadmin — Todo auditado)</div>
          </td>
          <td><span class="pill on">SÍ</span></td>
          <td><span class="pill on">SÍ</span></td>
          <td><span class="pill on">SÍ</span></td>
          <td><span class="pill on">ACTIVO</span></td>
          <td style="color:var(--text-dim);font-size:11px;">Fijo del sistema</td>
        </tr>
        <?php foreach ($permisos_lista as $pm): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($pm['usuario_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
              <div class="mono" style="font-size:10.5px;color:var(--text-dim);">ID: <?php echo htmlspecialchars($pm['usuario_id'], ENT_QUOTES, 'UTF-8'); ?> &bull; Act: <?php echo htmlspecialchars($pm['actualizado_el'], ENT_QUOTES, 'UTF-8'); ?></div>
            </td>
            <td><span class="pill <?php echo !empty($pm['editar_planillas_cerradas']) ? 'on' : 'off'; ?>"><?php echo !empty($pm['editar_planillas_cerradas']) ? 'SÍ' : 'NO'; ?></span></td>
            <td><span class="pill <?php echo !empty($pm['corregir_fecha_turno']) ? 'on' : 'off'; ?>"><?php echo !empty($pm['corregir_fecha_turno']) ? 'SÍ' : 'NO'; ?></span></td>
            <td><span class="pill <?php echo !empty($pm['ver_auditoria']) ? 'on' : 'off'; ?>"><?php echo !empty($pm['ver_auditoria']) ? 'SÍ' : 'NO'; ?></span></td>
            <td><span class="pill <?php echo !empty($pm['activo']) ? 'on' : 'off'; ?>"><?php echo !empty($pm['activo']) ? 'ACTIVO' : 'SUSPENDIDO'; ?></span></td>
            <td style="display:flex;gap:6px;">
              <button type="button" class="btn" style="padding:4px 8px;font-size:11px;"
                onclick="cargarEnFormulario('<?php echo htmlspecialchars($pm['usuario_id'], ENT_QUOTES, 'UTF-8'); ?>','<?php echo htmlspecialchars($pm['usuario_nombre'], ENT_QUOTES, 'UTF-8'); ?>',<?php echo !empty($pm['editar_planillas_cerradas']) ? 1 : 0; ?>,<?php echo !empty($pm['corregir_fecha_turno']) ? 1 : 0; ?>,<?php echo !empty($pm['ver_auditoria']) ? 1 : 0; ?>,<?php echo !empty($pm['activo']) ? 1 : 0; ?>)">
                ✏️ Editar
              </button>
              <form method="POST" onsubmit="return confirm('¿Revocar todos los permisos de este usuario?');" style="display:inline;">
                <input type="hidden" name="accion_permiso" value="eliminar_usuario">
                <input type="hidden" name="usuario_id" value="<?php echo htmlspecialchars($pm['usuario_id'], ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="btn danger" style="padding:4px 8px;font-size:11px;">🗑️</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- POLÍTICA DE DESTRABE Y ALERTAS POR CORREO -->
  <div class="card">
    <div class="card-hdr"><span>⚙️ Política de Corrección de Fecha/Turno y Alertas por Correo</span></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="accion_permiso" value="guardar_seguridad">

        <label class="check-row" style="border-color: rgba(245,158,11,0.4);">
          <input type="checkbox" name="permitir_autodestrabe_emergencia" value="1" <?php if (!empty($cfg_seg['permitir_autodestrabe_emergencia'])) echo 'checked'; ?>>
          <div>
            <div style="font-weight:700;color:#fde68a;">⚡ Habilitar Auto-Destrabe de Emergencia para Oficial Entrante (Opción B)</div>
            <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;">
              Si está <strong>desactivado</strong> (recomendado / Opción A), solo Usuario 0 y los usuarios con permiso <code>corregir_fecha_turno</code> pueden reubicar planillas cerradas.<br>
              Si está <strong>activado</strong>, el oficial entrante también podrá reubicar <em>únicamente la última planilla cerrada</em> hacia un casillero libre adyacente (±1 día) dentro de la ventana horaria indicada, exigiendo motivo obligatorio, registro en auditoría y alerta automática por mail.
            </div>
          </div>
        </label>

        <div class="grid-2" style="margin: 12px 0;">
          <div>
            <label class="field-lbl">Ventana de auto-destrabe (horas desde el cierre)</label>
            <input type="number" name="ventana_horas_autodestrabe" min="1" max="72" class="field-ctrl mono" value="<?php echo intval(!empty($cfg_seg['ventana_horas_autodestrabe']) ? $cfg_seg['ventana_horas_autodestrabe'] : 24); ?>">
          </div>
          <div>
            <label class="field-lbl" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
              <input type="checkbox" name="alertas_mail_activas" value="1" <?php if (!empty($cfg_seg['alertas_mail_activas'])) echo 'checked'; ?>>
              Enviar alertas automáticas por correo Exchange
            </label>
            <textarea name="destinatarios_alertas" rows="2" class="field-ctrl mono" placeholder="gloncar@na-sa.com.ar"><?php echo htmlspecialchars($dest_alertas_txt, ENT_QUOTES, 'UTF-8'); ?></textarea>
          </div>
        </div>

        <button type="submit" class="btn primary">💾 Guardar Política de Seguridad</button>
      </form>
    </div>
  </div>
</div>

<script>
  function seleccionarUsuarioConocido() {
    var sel = document.getElementById('sel-usuario-conocido');
    var opt = sel.options[sel.selectedIndex];
    if (sel.value) {
      document.getElementById('inp-usuario-id').value = sel.value;
      document.getElementById('inp-usuario-nombre').value = opt.getAttribute('data-nombre') || '';
    }
  }

  function cargarEnFormulario(uid, nom, pEd, pFe, pVer, act) {
    document.getElementById('inp-usuario-id').value = uid;
    document.getElementById('inp-usuario-nombre').value = nom;
    document.getElementById('chk-p-editar').checked = !!pEd;
    document.getElementById('chk-p-fecha').checked = !!pFe;
    document.getElementById('chk-p-ver').checked = !!pVer;
    document.getElementById('chk-p-activo').checked = !!act;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function buscarNombreUsuario() {
    var uid = (document.getElementById('inp-usuario-id').value || '').trim();
    if (!uid) return;
    fetch('procesos_reg_rad.php?accion=resolver_nombre_usuario&uid=' + encodeURIComponent(uid))
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data && data.ok && data.nombre) {
          document.getElementById('inp-usuario-nombre').value = data.nombre;
        } else {
          alert('No se encontró un nombre automático para el ID ' + uid + '. Puede ingresarlo manualmente.');
        }
      })
      .catch(function() {
        alert('No se pudo consultar el servidor en este momento.');
      });
  }
</script>
</body>
</html>
