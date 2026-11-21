<?php
session_start();

// Control estricto de acceso: Solo usuario "0" (Superadmin / Desarrollador)
$usuario_id = isset($_SESSION['id_user']) ? (string)$_SESSION['id_user'] : (isset($_GET['dev']) ? '0' : null);

if ($usuario_id !== '0') {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Acceso Restringido</title>';
    echo '<style>body{background:#040d1a;color:#f87171;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;}</style></head>';
    echo '<body><div style="text-align:center;padding:24px;border:1px solid #ef4444;border-radius:8px;background:rgba(239,68,68,0.1);">';
    echo '<h2>⛔ Acceso Restringido</h2>';
    echo '<p style="color:#cbd5e1;font-size:14px;">Esta página de configuración es exclusiva para el usuario administrador del sistema (Usuario 0).</p>';
    echo '<p><a href="demo_planilla.php" style="color:#38bdf8;text-decoration:none;">&larr; Volver a la Planilla</a></p>';
    echo '</div></body></html>';
    exit;
}

require_once(__DIR__ . '/enviar_mail_planilla.php');

$miconex = null;
if (isset($_SERVER["DOCUMENT_ROOT"]) && file_exists($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php")) {
    @include_once($_SERVER["DOCUMENT_ROOT"] . "/ptr/Connections/miconex.php");
}

$ruta_json = __DIR__ . '/config_correo.json';
$mensaje_estado = '';
$tipo_alerta = 'info';

// Procesar guardado de formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_config'])) {
    $modo = isset($_POST['modo']) && $_POST['modo'] === 'pruebas' ? 'pruebas' : 'produccion';
    
    // Parsear destinatarios (separados por coma, punto y coma o saltos de línea)
    $dest_prod_raw = isset($_POST['destinatarios_produccion']) ? $_POST['destinatarios_produccion'] : '';
    $dest_pruebas_raw = isset($_POST['destinatarios_pruebas']) ? $_POST['destinatarios_pruebas'] : '';

    $fn_limpiar = function($str) {
        $parts = preg_split('/[\r\n,;]+/', $str);
        $res = array();
        foreach ($parts as $p) {
            $p = trim($p);
            if (!empty($p) && filter_var($p, FILTER_VALIDATE_EMAIL)) {
                $res[] = $p;
            }
        }
        return array_values(array_unique($res));
    };

    $dest_prod = $fn_limpiar($dest_prod_raw);
    if (empty($dest_prod)) {
        $dest_prod = array('oficiales-rp-cne@na-sa.com.ar', 'ayudantejefedeturno@na-sa.com.ar');
    }

    $dest_pruebas = $fn_limpiar($dest_pruebas_raw);
    if (empty($dest_pruebas)) {
        $dest_pruebas = array('gloncar@na-sa.com.ar');
    }

    $servidor_smtp = !empty($_POST['servidor_smtp']) ? trim($_POST['servidor_smtp']) : 'correo.na-sa.com.ar';
    $puerto_smtp = !empty($_POST['puerto_smtp']) ? intval($_POST['puerto_smtp']) : 25;
    $remitente = !empty($_POST['remitente']) ? trim($_POST['remitente']) : 'registro_radiologico@na-sa.com.ar';
    $nombre_remitente = !empty($_POST['nombre_remitente']) ? trim($_POST['nombre_remitente']) : 'Planilla Registro Radiologico';

    $nueva_config = array(
        'modo' => $modo,
        'destinatarios_produccion' => $dest_prod,
        'destinatarios_pruebas' => $dest_pruebas,
        'servidor_smtp' => $servidor_smtp,
        'puerto_smtp' => $puerto_smtp,
        'remitente' => $remitente,
        'nombre_remitente' => $nombre_remitente,
        'actualizado_el' => date('Y-m-d H:i:s'),
        'actualizado_por' => '0'
    );

    // Guardar en JSON
    $json_str = json_encode($nueva_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $ok_json = @file_put_contents($ruta_json, $json_str);

    // Guardar en MySQL si existe conexión
    if ($miconex) {
        @mysql_query("CREATE TABLE IF NOT EXISTS `regrad_config_correo` (
            `clave` VARCHAR(64) NOT NULL PRIMARY KEY,
            `valor_json` TEXT NOT NULL,
            `fecha_actualizacion` DATETIME NOT NULL
        )", $miconex);

        $json_esc = mysql_real_escape_string($json_str, $miconex);
        $fecha_ahora = date('Y-m-d H:i:s');
        @mysql_query("INSERT INTO `regrad_config_correo` (`clave`, `valor_json`, `fecha_actualizacion`)
            VALUES ('correo_exchange', '$json_esc', '$fecha_ahora')
            ON DUPLICATE KEY UPDATE `valor_json` = '$json_esc', `fecha_actualizacion` = '$fecha_ahora'", $miconex);
    }

    if ($ok_json !== false) {
        $mensaje_estado = '✔️ Configuración de correo guardada exitosamente.';
        $tipo_alerta = 'exito';
    } else {
        $mensaje_estado = '⚠️ Se actualizó en base de datos pero no se pudo escribir en disco (permisos en config_correo.json).';
        $tipo_alerta = 'warning';
    }
}

// Cargar configuración actual
$cfg = obtener_configuracion_correo($miconex);
$dest_prod_txt = implode("\n", !empty($cfg['destinatarios_produccion']) ? (array)$cfg['destinatarios_produccion'] : array());
$dest_pruebas_txt = implode("\n", !empty($cfg['destinatarios_pruebas']) ? (array)$cfg['destinatarios_pruebas'] : array());
?>
<!DOCTYPE html>
<html lang="es" data-theme="petrol">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SyR &bull; Configuración de Correo Exchange (Usuario 0)</title>
  <style>
    :root {
      --bg-page: #061412;
      --bg-card: #0e2722;
      --bg-input: #081a16;
      --border-card: #1f4d43;
      --text-main: #edfcf6;
      --text-muted: #8ec7b9;
      --text-dim: #52857a;
      --accent-cyan: #38bdf8;
      --accent-green: #10b981;
      --accent-amber: #f59e0b;
      --font-mono: 'Consolas', 'Monaco', monospace;
      --font-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg-page);
      color: var(--text-main);
      font-family: var(--font-sans);
      font-size: 13px;
      padding: 24px;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }
    .config-card {
      background: var(--bg-card);
      border: 1px solid var(--border-card);
      border-radius: 8px;
      max-width: 680px;
      width: 100%;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      overflow: hidden;
    }
    .config-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border-card);
      background: rgba(0,0,0,0.25);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .config-title {
      font-size: 14px;
      font-weight: 800;
      color: var(--accent-cyan);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .badge-user0 {
      background: rgba(245, 158, 11, 0.2);
      border: 1px solid var(--accent-amber);
      color: var(--accent-amber);
      font-size: 10px;
      padding: 2px 8px;
      border-radius: 4px;
      font-family: var(--font-mono);
      font-weight: 700;
    }
    .config-body {
      padding: 20px;
    }
    .form-group {
      margin-bottom: 16px;
    }
    .form-label {
      display: block;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 6px;
      letter-spacing: 0.5px;
    }
    .form-control {
      width: 100%;
      background: var(--bg-input);
      border: 1px solid var(--border-card);
      border-radius: 6px;
      color: var(--text-main);
      padding: 8px 12px;
      font-size: 12.5px;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-control:focus {
      border-color: var(--accent-cyan);
    }
    textarea.form-control {
      font-family: var(--font-mono);
      resize: vertical;
      min-height: 75px;
      line-height: 1.4;
    }
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
    .mode-selector {
      display: flex;
      gap: 12px;
      margin-top: 4px;
    }
    .mode-option {
      flex: 1;
      border: 1px solid var(--border-card);
      border-radius: 6px;
      padding: 10px 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(0,0,0,0.15);
      transition: all 0.2s;
    }
    .mode-option:hover {
      background: rgba(6, 182, 212, 0.08);
      border-color: var(--accent-cyan);
    }
    .mode-option input {
      accent-color: var(--accent-cyan);
    }
    .alert-box {
      padding: 10px 14px;
      border-radius: 6px;
      margin-bottom: 16px;
      font-size: 12px;
    }
    .alert-exito {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #34d399;
    }
    .alert-warning {
      background: rgba(245, 158, 11, 0.15);
      border: 1px solid rgba(245, 158, 11, 0.4);
      color: #fbbf24;
    }
    .config-footer {
      padding: 14px 20px;
      background: rgba(0,0,0,0.25);
      border-top: 1px solid var(--border-card);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn-save {
      background: var(--accent-cyan);
      color: #000;
      border: none;
      padding: 8px 20px;
      font-weight: 800;
      font-size: 12px;
      border-radius: 6px;
      cursor: pointer;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .btn-back {
      background: transparent;
      color: var(--text-muted);
      border: 1px solid var(--border-card);
      padding: 8px 16px;
      font-size: 12px;
      border-radius: 6px;
      text-decoration: none;
      cursor: pointer;
    }
    .btn-back:hover {
      color: #ffffff;
      border-color: var(--text-muted);
    }
  </style>
</head>
<body>

  <div class="config-card">
    <div class="config-header">
      <div class="config-title">
        <span>✉️</span>
        <span>Configuración de Despacho de Correo</span>
      </div>
      <span class="badge-user0">USUARIO 0 &bull; EXCLUSIVO</span>
    </div>

    <form method="POST" action="configuracion_correo.php">
      <div class="config-body">
        <?php if (!empty($mensaje_estado)): ?>
          <div class="alert-box alert-<?php echo $tipo_alerta; ?>">
            <?php echo htmlspecialchars($mensaje_estado, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <!-- MODO DE ENVÍO -->
        <div class="form-group">
          <label class="form-label">Modo Operativo del Correo</label>
          <div class="mode-selector">
            <label class="mode-option">
              <input type="radio" name="modo" value="produccion" <?php echo ($cfg['modo'] !== 'pruebas') ? 'checked' : ''; ?>>
              <div>
                <strong style="color: #34d399;">PRODUCCIÓN (Oficial)</strong>
                <div style="font-size: 10.5px; color: var(--text-dim);">Envía a la lista de oficiales y jefes de turno</div>
              </div>
            </label>
            <label class="mode-option">
              <input type="radio" name="modo" value="pruebas" <?php echo ($cfg['modo'] === 'pruebas') ? 'checked' : ''; ?>>
              <div>
                <strong style="color: var(--accent-amber);">PRUEBAS (Desarrollo)</strong>
                <div style="font-size: 10.5px; color: var(--text-dim);">Envía exclusivamente a la casilla de testing</div>
              </div>
            </label>
          </div>
        </div>

        <!-- DESTINATARIOS PRODUCCIÓN -->
        <div class="form-group">
          <label class="form-label">Destinatarios en Producción (uno por línea o separados por coma)</label>
          <textarea name="destinatarios_produccion" class="form-control" rows="3" placeholder="oficiales-rp-cne@na-sa.com.ar&#10;ayudantejefedeturno@na-sa.com.ar"><?php echo htmlspecialchars($dest_prod_txt, ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div style="font-size: 10.5px; color: var(--text-dim); margin-top: 4px;">
            Casillas institucionales que recibirán el informe final en los turnos reales.
          </div>
        </div>

        <!-- DESTINATARIOS PRUEBAS -->
        <div class="form-group">
          <label class="form-label">Destinatarios en Pruebas</label>
          <textarea name="destinatarios_pruebas" class="form-control" rows="2" placeholder="gloncar@na-sa.com.ar"><?php echo htmlspecialchars($dest_pruebas_txt, ENT_QUOTES, 'UTF-8'); ?></textarea>
          <div style="font-size: 10.5px; color: var(--text-dim); margin-top: 4px;">
            Casillas para testeo sin molestar a los receptores operativos.
          </div>
        </div>

        <!-- CONFIGURACIÓN DEL SERVIDOR EXCHANGE -->
        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Servidor SMTP Exchange</label>
            <input type="text" name="servidor_smtp" class="form-control" value="<?php echo htmlspecialchars($cfg['servidor_smtp'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Puerto SMTP</label>
            <input type="number" name="puerto_smtp" class="form-control" value="<?php echo htmlspecialchars($cfg['puerto_smtp'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Remitente (Mail)</label>
            <input type="email" name="remitente" class="form-control" value="<?php echo htmlspecialchars($cfg['remitente'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nombre del Remitente</label>
            <input type="text" name="nombre_remitente" class="form-control" value="<?php echo htmlspecialchars($cfg['nombre_remitente'], ENT_QUOTES, 'UTF-8'); ?>">
          </div>
        </div>

        <div style="background: rgba(6, 182, 212, 0.06); border: 1px solid rgba(6, 182, 212, 0.2); border-radius: 6px; padding: 10px 12px; font-size: 11px; color: var(--text-muted); font-family: var(--font-mono);">
          Última actualización: <strong><?php echo isset($cfg['actualizado_el']) ? htmlspecialchars($cfg['actualizado_el'], ENT_QUOTES, 'UTF-8') : 'Predeterminada'; ?></strong>
        </div>
      </div>

      <div class="config-footer">
        <a href="demo_planilla.php" class="btn-back">&larr; Volver a la Planilla</a>
        <button type="submit" name="guardar_config" value="1" class="btn-save">💾 Guardar Configuración</button>
      </div>
    </form>
  </div>

</body>
</html>
